<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\TestQuestion;
use App\Models\TestOption;
use App\Models\QuestionTranslation;
use App\Models\OptionTranslation;

class TestTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uzPath = resource_path('tests/all questions/uz.json');
        $ruPath = resource_path('tests/all questions/ru.json');
        $kirilPath = resource_path('tests/all questions/kiril.json');

        if (!File::exists($uzPath) || !File::exists($ruPath) || !File::exists($kirilPath)) {
            $this->command->error("One or more translation files are missing.");
            return;
        }

        $this->command->info("Loading translations...");
        $uzData = json_decode(File::get($uzPath), true)['data']['data'];
        $ruData = json_decode(File::get($ruPath), true)['data']['data'];
        $kirilData = json_decode(File::get($kirilPath), true)['data']['data'];

        $this->command->info("Creating index map from UZ data...");
        $uzMap = [];
        foreach ($uzData as $index => $item) {
            $uzMap[$item['id']] = $index;
        }

        $questions = TestQuestion::all();
        $this->command->info("Seeding translations for " . $questions->count() . " questions...");

        foreach ($questions as $question) {
            $newQuestionId = $question->new_question_id;
            
            if (!isset($uzMap[$newQuestionId])) {
                $this->command->warn("Question ID {$newQuestionId} not found in UZ JSON.");
                continue;
            }

            $index = $uzMap[$newQuestionId];
            
            $languages = [
                'ru' => $ruData[$index] ?? null,
                'kiril' => $kirilData[$index] ?? null,
                'uz' => $uzData[$index] ?? null,
            ];

            foreach ($languages as $lang => $langData) {
                if (!$langData) continue;

                // Question Translation
                $questionText = "";
                foreach ($langData['body'] as $bodyItem) {
                    if ($bodyItem['type'] == 1) {
                        $questionText = $bodyItem['value'];
                        break;
                    }
                }

                if ($questionText) {
                    QuestionTranslation::updateOrCreate(
                        ['question_id' => $question->id, 'language' => $lang],
                        ['question' => $questionText]
                    );
                }

                // Option Translations
                if (isset($langData['answers'])) {
                    $dbOptions = $question->options()->orderBy('id')->get();
                    foreach ($langData['answers'] as $aIndex => $aData) {
                        if (isset($dbOptions[$aIndex])) {
                            $optionText = "";
                            foreach ($aData['body'] as $aBodyItem) {
                                if ($aBodyItem['type'] == 1) {
                                    $optionText = $aBodyItem['value'];
                                    break;
                                }
                            }

                            if ($optionText) {
                                OptionTranslation::updateOrCreate(
                                    ['option_id' => $dbOptions[$aIndex]->id, 'language' => $lang],
                                    ['option' => $optionText]
                                );
                            }
                        }
                    }
                }
            }
        }
        $this->command->info("Translation seeding completed.");
    }
}
