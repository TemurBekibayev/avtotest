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
        
        // Before inserting templates, let's clear them
        $this->info("Wiping previous student shablon templates...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('student_template_questions')->truncate();
        StudentTestTemplate::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("Building question ID mapping from database...");
        // Map original JSON ID (new_question_id) to database primary key (id)
        $jsonIdToDbId = DB::table('test_questions')->pluck('id', 'new_question_id')->toArray();

        $this->info("Processing " . count($uzData) . " templates from JSON...");
        $bar = $this->output->createProgressBar(count($uzData));
        
        foreach ($uzData as $index => $templateObj) {
            if (!isset($templateObj['data']['data']['questions'])) {
                $bar->advance();
                continue;
            }

            $templateNumber = $index + 1;
            $jsonQuestions = $templateObj['data']['data']['questions'];
            
            // 1. Create the template record
            $template = StudentTestTemplate::create([
                'name' => "Shablon {$templateNumber}",
                'type' => 'Shablon',
                'duration_minutes' => count($jsonQuestions), // default to 1 min per q
                'passing_score' => ceil(count($jsonQuestions) * 0.9), // 90%
            ]);

            $dbIdsToSync = [];
            
            foreach ($jsonQuestions as $q) {
                $jsonId = $q['id'];
                
                // Get the database primary key for this original JSON ID
                $dbId = $jsonIdToDbId[$jsonId] ?? null;
                
                if ($dbId) {
                    $dbIdsToSync[] = $dbId;
                    
                    // 2. Update image/file if needed (Optional, but good for data consistency)
                    $imagePath = null;
                    if (isset($q['body']) && is_array($q['body'])) {
                        foreach ($q['body'] as $item) {
                            if ($item['type'] == 2) {
                                $imageParts = explode('/', $item['value']);
                                $filename = end($imageParts);
                                $localPath = "tests/images changed/tests/" . $filename;
                                
                                if (File::exists(storage_path("app/public/" . $localPath))) {
                                    $imagePath = $localPath;
                                }
                            }
                        }
                    }
                    
                    if ($imagePath) {
                        // Check columns and update
                        $columns = DB::getSchemaBuilder()->getColumnListing('test_questions');
                        if (in_array('image', $columns)) {
                            DB::table('test_questions')->where('id', $dbId)->update(['image' => $imagePath]);
                        } elseif (in_array('question_file', $columns)) {
                            DB::table('test_questions')->where('id', $dbId)->update(['question_file' => $imagePath]);
                        }
                    }
                }
            }
            
            // 3. Sync questions to template
            if (!empty($dbIdsToSync)) {
                $template->questions()->sync($dbIdsToSync);
            }
            
            $bar->advance();
        }
        $bar->finish();
        
        $this->info("\nSuccessfully processed " . count($uzData) . " templates.");
    }
}
