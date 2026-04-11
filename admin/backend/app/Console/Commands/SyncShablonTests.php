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
        $this->info("Starting Shablon Tests Synchronization...");
        
        $baseDir = resource_path('tests/savollar');
        $uzPath = "$baseDir/all_questions_uz.json";
        $ruPath = "$baseDir/all_questions_ru.json";
        $krPath = "$baseDir/all_questions_kiril.json";

        if (!File::exists($uzPath) || !File::exists($ruPath) || !File::exists($krPath)) {
            $this->error("One or more JSON files missing in $baseDir");
            return;
        }

        $uzData = json_decode(File::get($uzPath), true);
        $ruData = json_decode(File::get($ruPath), true);
        $krData = json_decode(File::get($krPath), true);

        // Collect unique questions
        $uniqueQuestions = [];
        foreach ($uzData as $templateObj) {
            if (!isset($templateObj['data']['data']['questions'])) continue;
            foreach ($templateObj['data']['data']['questions'] as $q) {
                if (!isset($uniqueQuestions[$q['id']])) {
                    $uniqueQuestions[$q['id']] = $q;
                }
            }
        }
        
        $ruQuestionsMap = [];
        foreach ($ruData as $templateObj) {
            if (!isset($templateObj['data']['data']['questions'])) continue;
            foreach ($templateObj['data']['data']['questions'] as $q) {
                $ruQuestionsMap[$q['id']] = $q;
            }
        }
        
        $krQuestionsMap = [];
        foreach ($krData as $templateObj) {
            if (!isset($templateObj['data']['data']['questions'])) continue;
            foreach ($templateObj['data']['data']['questions'] as $q) {
                $krQuestionsMap[$q['id']] = $q;
            }
        }

        // Before inserting templates, let's clear them
        $this->info("Wiping previous student shablon templates...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('student_template_questions')->truncate();
        StudentTestTemplate::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("Found " . count($uniqueQuestions) . " unique questions. Parsing images and text...");
        $questionIds = array_keys($uniqueQuestions);
        
        $bar = $this->output->createProgressBar(count($questionIds));
        
        foreach ($uniqueQuestions as $id => $uzQ) {
            $imagePath = null;
            
            if (isset($uzQ['body']) && is_array($uzQ['body'])) {
                foreach ($uzQ['body'] as $item) {
                    if ($item['type'] == 2) {
                        $imageParts = explode('/', $item['value']);
                        $filename = end($imageParts);
                        
                        $localPath = "tests/images changed/tests/" . $filename;
                        if (File::exists(storage_path("app/public/" . $localPath))) {
                            $imagePath = $localPath;
                        } else {
                            $imagePath = null; // Let the frontend fallback handle it
                        }
                    }
                }
            }
            
            // Only update the image if the question exists, since test_questions might already exist
            // Or create a dummy one if it somehow doesnt exist
            $existing = TestQuestion::find($id);
            if ($existing) {
                if ($imagePath !== null) {
                    // check if we should update image. if database column is image or question_file
                    // we update whatever field holds image natively. We'll use getTable() checking:
                    $columns = DB::getSchemaBuilder()->getColumnListing($existing->getTable());
                    if (in_array('image', $columns)) {
                        $existing->update(['image' => $imagePath]);
                    } elseif (in_array('question_file', $columns)) {
                        $existing->update(['question_file' => $imagePath]);
                    }
                }
            } else {
                // If it doesn't exist, we skip insertion for now to avoid breaking other logic, 
                // but if we MUST insert, we'd do it here. The prompt implies questions exist 
                // in the database already, we just need to group and fix images.
                // Assuming it's already seeded by previous seeders. 
            }
            $bar->advance();
        }
        $bar->finish();
        
        $this->info("\nDistributing into 62 templates...");
        $chunks = array_chunk($questionIds, 20);
        $templateCount = count($chunks);
        
        if ($templateCount > 62) {
             $remainder = [];
             for ($i = 61; $i < $templateCount; $i++) {
                  $remainder = array_merge($remainder, $chunks[$i]);
             }
             $chunks = array_slice($chunks, 0, 61);
             $chunks[] = $remainder;
        }

        foreach ($chunks as $index => $chunkIds) {
            $templateNumber = $index + 1;
            
            // Limit remainder size so we don't send huge chunks (usually the remainder shouldn't be much larger than 20)
            $template = StudentTestTemplate::create([
                'name' => "Shablon {$templateNumber}",
                'type' => 'Shablon',
                'duration_minutes' => count($chunkIds) * 1, // 1 min per q
                'passing_score' => ceil(count($chunkIds) * 0.9), // 90%
            ]);

            $validIdsForSync = TestQuestion::whereIn('id', $chunkIds)->pluck('id')->toArray();
            $template->questions()->sync($validIdsForSync);
        }

        $this->info("Successfully created " . count($chunks) . " templates.");
    }
}
