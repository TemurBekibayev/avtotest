<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\ShablonQuestion;
use App\Models\ShablonQuestionTranslation;
use App\Models\ShablonOption;
use App\Models\ShablonOptionTranslation;
use App\Models\ShablonAnswer;
use App\Models\StudentTestTemplate;

class SyncShablonTests extends Command
{
    protected $signature = 'tests:sync-shablons';
    protected $description = 'Populate separate shablon_* tables with 3-language data and sync 62 templates';

    public function handle()
    {
        $this->info("Starting Segregated Index-Aligned Multi-Lang Shablon Synchronization...");
        
        $baseDir = resource_path('tests/savollar');
        $files = [
            'uz' => "$baseDir/all_questions_uz.json",
            'ru' => "$baseDir/all_questions_ru.json",
            'kiril' => "$baseDir/all_questions_kiril.json"
        ];

        foreach ($files as $lang => $path) {
            if (!File::exists($path)) {
                $this->error("JSON file missing: $path");
                return;
            }
        }

        // 1. Load and Flatten data per language
        $this->info("Loading and flattening JSON data...");
        $flattened = ['uz' => [], 'ru' => [], 'kiril' => []];
        foreach ($files as $lang => $path) {
            $data = json_decode(File::get($path), true);
            foreach ($data as $templateObj) {
                $qs = $templateObj['data']['data']['questions'] ?? [];
                foreach ($qs as $q) {
                    $flattened[$lang][] = $q;
                }
            }
        }

        $totalQuestions = count($flattened['uz']);
        $this->info("Found $totalQuestions questions to process.");

        // Wipe tables
        $this->info("Wiping shablon and student template tables...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('shablon_answers')->truncate();
        DB::table('shablon_option_translations')->truncate();
        DB::table('shablon_options')->truncate();
        DB::table('shablon_question_translations')->truncate();
        DB::table('shablon_questions')->truncate();
        DB::table('student_template_questions')->truncate();
        DB::table('student_test_templates')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Build question map for test_questions using UZ IDs as master
        $this->info("Fetching existing test_questions for image fallbacks (using UZ IDs)...");
        $testQuestionsMap = DB::table('test_questions')->pluck('question_file', 'new_question_id')->toArray();

        // 2. Process unique questions BY INDEX to ensure cross-lang alignment
        $this->info("Processing aligned questions and translations...");
        $bar = $this->output->createProgressBar($totalQuestions);
        $sqIds = [];
        
        for ($i = 0; $i < $totalQuestions; $i++) {
            $uzQ = $flattened['uz'][$i];
            $ruQ = $flattened['ru'][$i] ?? null;
            $krQ = $flattened['kiril'][$i] ?? null;
            $jsonId = $uzQ['id'];

            // Determine image path (Master UZ ID first)
            $imagePath = $testQuestionsMap[$jsonId] ?? null;
            if (!$imagePath) {
                // Fallback to UZ JSON path
                foreach ($uzQ['body'] as $part) {
                    if ($part['type'] == 2) {
                        $imagePath = $part['value'];
                        break;
                    }
                }
            }

            $sq = ShablonQuestion::create([
                'json_id' => $jsonId,
                'image_path' => $imagePath
            ]);
            $sqIds[] = $sq->id;

            // 2. Create Options once (using UZ as master)
            $createdOptionIds = [];
            if (isset($uzQ['answers'])) {
                foreach ($uzQ['answers'] as $optData) {
                    $so = ShablonOption::create([
                        'shablon_question_id' => $sq->id,
                        'is_correct' => ($optData['check'] == 1)
                    ]);
                    $createdOptionIds[] = $so->id;
                }
            }

            // 3. Question & Option Translations (3 languages)
            foreach (['uz', 'ru', 'kiril'] as $lang) {
                $qData = ($lang === 'uz') ? $uzQ : (($lang === 'ru') ? $ruQ : $krQ);
                if ($qData) {
                    $qText = '';
                    foreach ($qData['body'] as $part) {
                        if ($part['type'] == 1) $qText = $part['value'];
                    }
                    ShablonQuestionTranslation::create([
                        'shablon_question_id' => $sq->id,
                        'language' => $lang,
                        'question_text' => $qText
                    ]);

                    // Answer / Explanation details
                    if (isset($qData['answer_description']) || isset($qData['answer_video'])) {
                        ShablonAnswer::create([
                            'shablon_question_id' => $sq->id,
                            'language' => $lang,
                            'description' => $qData['answer_description'] ?? null,
                            'video_path' => $qData['answer_video'] ?? null
                        ]);
                    }

                    // Option Translations
                    if (isset($qData['answers'])) {
                        foreach ($qData['answers'] as $optIndex => $optData) {
                            $optText = '';
                            foreach ($optData['body'] as $part) {
                                if ($part['type'] == 1) $optText = $part['value'];
                            }

                            $optionId = $createdOptionIds[$optIndex] ?? null;
                            if ($optionId) {
                                ShablonOptionTranslation::create([
                                    'shablon_option_id' => $optionId,
                                    'language' => $lang,
                                    'option_text' => $optText
                                ]);
                            }
                        }
                    }
                }
            }
            $bar->advance();
        }
        $bar->finish();

        // 3. Create templates and sync (Orderly logic - 62 templates)
        $this->info("\nGenerating 62 templates (20 questions each)...");
        $chunks = array_chunk($sqIds, 20);
        foreach ($chunks as $index => $chunk) {
            $num = $index + 1;
            $template = StudentTestTemplate::create([
                'name' => "Shablon {$num}",
                'type' => 'Shablon',
                'duration_minutes' => count($chunk),
                'passing_score' => ceil(count($chunk) * 0.9)
            ]);
            $template->questions()->sync($chunk);
        }

        $this->info("\nSync finished. " . count($chunks) . " templates created.");
    }
}
