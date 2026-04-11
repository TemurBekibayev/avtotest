<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\TestQuestion;
use App\Models\QuestionTranslation;
use App\Models\TestOption;
use App\Models\OptionTranslation;
use App\Models\StudentTestTemplate;

class SyncShablonTests extends Command
{
    protected $signature = 'tests:sync-shablons';
    protected $description = 'Extract unique questions from 3 JSONs, map images, and distribute into 62 separate Shablon student templates';

    public function handle()
    {
        $this->info("Starting Orderly Shablon Tests Synchronization...");
        
        $baseDir = resource_path('tests/savollar');
        $uzPath = "$baseDir/all_questions_uz.json";

        if (!File::exists($uzPath)) {
            $this->error("JSON file missing: $uzPath");
            return;
        }

        $uzData = json_decode(File::get($uzPath), true);
        
        // Before inserting templates, let's clear them
        $this->info("Wiping previous student shablon templates...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('student_template_questions')->truncate();
        StudentTestTemplate::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("Building question ID mapping from database...");
        // Map original JSON ID (new_question_id) to database primary key (id)
        $jsonIdToDbId = DB::table('test_questions')->pluck('id', 'new_question_id')->toArray();

        // 1. Collect ALL DB IDs in the order they appear in the JSON
        $this->info("Extracting all question IDs in order...");
        $allOrderedDbIds = [];
        $uniqueJsonQuestions = [];

        foreach ($uzData as $templateObj) {
            $jsonQuestions = $templateObj['data']['data']['questions'] ?? [];
            foreach ($jsonQuestions as $q) {
                $dbId = $jsonIdToDbId[$q['id']] ?? null;
                if ($dbId) {
                    $allOrderedDbIds[] = $dbId;
                    // For image updates later
                    if (!isset($uniqueJsonQuestions[$q['id']])) {
                        $uniqueJsonQuestions[$q['id']] = ['dbId' => $dbId, 'data' => $q];
                    }
                }
            }
        }

        // 2. Chunk the flat list by exactly 20
        $chunks = array_chunk($allOrderedDbIds, 20);
        $this->info("Total valid question slots: " . count($allOrderedDbIds));
        $this->info("Creating " . count($chunks) . " templates of 20 questions each...");

        $bar = $this->output->createProgressBar(count($chunks));
        foreach ($chunks as $index => $dbIdsInChunk) {
            $templateNumber = $index + 1;
            
            // Create the template record
            $template = StudentTestTemplate::create([
                'name' => "Shablon {$templateNumber}",
                'type' => 'Shablon',
                'duration_minutes' => count($dbIdsInChunk), 
                'passing_score' => ceil(count($dbIdsInChunk) * 0.9), // 90%
            ]);

            // Sync questions to template (Laravel sync will handle unique IDs within the same template)
            if (!empty($dbIdsInChunk)) {
                $template->questions()->sync($dbIdsInChunk);
            }
            $bar->advance();
        }
        $bar->finish();

        // 3. Update images for unique questions (Optional but helps data quality)
        $this->info("\nUpdating image paths for unique questions...");
        $imgBar = $this->output->createProgressBar(count($uniqueJsonQuestions));
        foreach ($uniqueJsonQuestions as $jsonId => $item) {
            $dbId = $item['dbId'];
            $qData = $item['data'];
            
            $imagePath = null;
            if (isset($qData['body']) && is_array($qData['body'])) {
                foreach ($qData['body'] as $bodyPart) {
                    if ($bodyPart['type'] == 2) {
                        $imageParts = explode('/', $bodyPart['value']);
                        $filename = end($imageParts);
                        $localPath = "tests/images changed/tests/" . $filename;
                        
                        if (File::exists(storage_path("app/public/" . $localPath))) {
                            $imagePath = $localPath;
                        }
                    }
                }
            }

            if ($imagePath) {
                $columns = DB::getSchemaBuilder()->getColumnListing('test_questions');
                if (in_array('image', $columns)) {
                    DB::table('test_questions')->where('id', $dbId)->update(['image' => $imagePath]);
                } elseif (in_array('question_file', $columns)) {
                    DB::table('test_questions')->where('id', $dbId)->update(['question_file' => $imagePath]);
                }
            }
            $imgBar->advance();
        }
        $imgBar->finish();
        
        $this->info("\nSync completed. " . count($chunks) . " templates initialized.");
    }
}
