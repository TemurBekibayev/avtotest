<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTestsCommand extends Command
{
    protected $signature = 'app:import-tests {dir}';
    protected $description = 'Import tests from JSON files in a directory (uz.json, ru.json, kiril.json)';

    public function handle()
    {
        $dir = rtrim($this->argument('dir'), '/');

        $files = [
            'uz' => "{$dir}/uz.json",
            'ru' => "{$dir}/ru.json",
            'kiril' => "{$dir}/kiril.json",
        ];

        foreach ($files as $lang => $path) {
            if (!file_exists($path)) {
                $this->error("File not found: {$path}");
                return;
            }
        }

        $this->info("Loading all language files...");
        $data = [];
        foreach ($files as $lang => $path) {
            $json = json_decode(file_get_contents($path), true);
            if (!$json || !isset($json['data']['data'])) {
                $this->error("Invalid JSON format in {$path}");
                return;
            }
            $data[$lang] = $json['data']['data'];
        }

        $count = count($data['uz']);
        $this->info("Found {$count} questions to process.");

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $count; $i++) {
                $q_uz = $data['uz'][$i];
                $q_ru = $data['ru'][$i] ?? null;
                $q_kiril = $data['kiril'][$i] ?? null;

                // Create base test question
                // We use the UZ ID as the main 'new_question_id' for tracking
                $questionFile = '';
                foreach ($q_uz['body'] as $b) {
                    if ($b['type'] == 2) {
                        $questionFile = $b['value'];
                    }
                }

                $tqId = DB::table('test_questions')->insertGetId([
                    'new_question_id' => $q_uz['id'],
                    'question_file' => $questionFile ?: '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Translations
                $this->addTranslation($tqId, 'uz', $q_uz);
                if ($q_ru)
                    $this->addTranslation($tqId, 'ru', $q_ru);
                if ($q_kiril)
                    $this->addTranslation($tqId, 'kiril', $q_kiril);

                // For answers (descriptions/videos), we take from UZ or first available
                $this->addAnswerDetails($tqId, $q_uz);

                // Options (Answers in JSON)
                // We'll import options from UZ. 
                // Since our schema 'test_options' doesn't have a 'language' column,
                // we might only store one language or we need to rethink.
                // Re-checking schema... Oh, test_options doesn't have translation support in current migration.
                // I will add UZ options for now.
                if (isset($q_uz['answers']) && is_array($q_uz['answers'])) {
                    foreach ($q_uz['answers'] as $opt) {
                        $optText = '';
                        foreach ($opt['body'] as $ob) {
                            if ($ob['type'] == 1) {
                                $optText = $ob['value'];
                            }
                        }

                        DB::table('test_options')->insert([
                            'test_question_id' => $tqId,
                            'option' => $optText,
                            'is_correct' => $opt['check'] == 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($i > 0 && $i % 100 == 0) {
                    $this->info("Processed {$i} questions...");
                }
            }

            DB::commit();
            $this->info("Successfully imported all {$count} questions with translations.");
        }
        catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage() . " at index " . ($i ?? 'unknown'));
        }
    }

    private function addTranslation($tqId, $lang, $q)
    {
        $text = '';
        foreach ($q['body'] as $b) {
            if ($b['type'] == 1) {
                $text = $b['value'];
            }
        }

        DB::table('question_translations')->insert([
            'question_id' => $tqId,
            'language' => $lang,
            'question' => $text,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function addAnswerDetails($tqId, $q)
    {
        $desc = $q['answer_description'] ?? '';
        $video = $q['answer_video'] ?? '';

        if ($desc || $video) {
            DB::table('answers')->insert([
                'test_question_id' => $tqId,
                'answer_description' => $desc,
                'answer_resource' => $video,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
