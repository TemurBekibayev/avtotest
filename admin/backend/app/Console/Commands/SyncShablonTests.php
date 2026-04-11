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
        $this->info("Starting Segregated Multi-Lang Shablon Synchronization...");
        
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

        // Load all data
        $this->info("Loading JSON data for all languages...");
        $data = [];
        foreach ($files as $lang => $path) {
            $data[$lang] = json_decode(File::get($path), true);
        }

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

        // Build question map for test_questions
        $this->info("Fetching existing test_questions for image fallbacks...");
        $testQuestionsMap = DB::table('test_questions')->pluck('question_file', 'new_question_id')->toArray();

        // Collect all unique questions across templates
        $this->info("Processing unique questions...");
        $questionsCollection = [];
        foreach ($data['uz'] as $templateObj) {
            $qs = $templateObj['data']['data']['questions'] ?? [];
            foreach ($qs as $q) {
                if (!isset($questionsCollection[$q['id']])) {
                    $questionsCollection[$q['id']] = $q;
                }
            }
        }

        // We also need to build a template sequence for the 20-chunk logic
        $allOrderedJsonIds = [];
        foreach ($data['uz'] as $templateObj) {
            $qs = $templateObj['data']['data']['questions'] ?? [];
            foreach ($qs as $q) {
                $allOrderedJsonIds[] = $q['id'];
            }
        }

        // Create a map for RU and KR questions for easy lookup
        $langMaps = ['ru' => [], 'kiril' => []];
        foreach (['ru', 'kiril'] as $lang) {
            foreach ($data[$lang] as $templateObj) {
                $qs = $templateObj['data']['data']['questions'] ?? [];
                foreach ($qs as $q) {
                    $langMaps[$lang][$q['id']] = $q;
                }
            }
        }

        $this->info("Inserting " . count($questionsCollection) . " unique questions into shablon tables...");
        $jsonIdToDbId = [];
        $bar = $this->output->createProgressBar(count($questionsCollection));

        foreach ($questionsCollection as $jsonId => $uzQ) {
            // 1. Determine image path
            $imagePath = $testQuestionsMap[$jsonId] ?? null;
            if (!$imagePath) {
                // Fallback to JSON path
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
            $jsonIdToDbId[$jsonId] = $sq->id;

            // 2. Question Translations
            foreach (['uz', 'ru', 'kiril'] as $lang) {
                $qData = ($lang === 'uz') ? $uzQ : ($langMaps[$lang][$jsonId] ?? null);
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
                }
            }

            // 3. Options (Multi-lang)
            // We assume Uz options order matches Ru/Kr
            if (isset($uzQ['answers'])) {
                foreach ($uzQ['answers'] as $index => $uzOpt) {
                    $so = ShablonOption::create([
                        'shablon_question_id' => $sq->id,
                        'is_correct' => ($uzOpt['check'] == 1)
                    ]);

                    foreach (['uz', 'ru', 'kiril'] as $lang) {
                        $qData = ($lang === 'uz') ? $uzQ : ($langMaps[$lang][$jsonId] ?? null);
                        $optData = $qData['answers'][$index] ?? null;
                        if ($optData) {
                            $optText = '';
                            foreach ($optData['body'] as $part) {
                                if ($part['type'] == 1) $optText = $part['value'];
                            }
                            ShablonOptionTranslation::create([
                                'shablon_option_id' => $so->id,
                                'language' => $lang,
                                'option_text' => $optText
                            ]);
                        }
                    }
                }
            }
            $bar->advance();
        }
        $bar->finish();

        // 4. Create templates and sync (Orderly logic)
        $this->info("\nGenerating 62 templates (20 questions each)...");
        $allOrderedDbIds = [];
        foreach ($allOrderedJsonIds as $jid) {
            if (isset($jsonIdToDbId[$jid])) {
                $allOrderedDbIds[] = $jsonIdToDbId[$jid];
            }
        }

        $chunks = array_chunk($allOrderedDbIds, 20);
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
