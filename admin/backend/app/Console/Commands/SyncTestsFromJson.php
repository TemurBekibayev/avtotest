<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\OptionTranslation;
use App\Models\QuestionTranslation;
use App\Models\TestOption;
use App\Models\TestQuestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SyncTestsFromJson extends Command
{
    protected $signature = 'tests:sync-json {--limit= : Limit the number of questions to sync}';
    protected $description = 'Sync test questions and translations from JSON files in resources/tests/all questions/';

    public function handle()
    {
        $pathUz = base_path('resources/tests/all questions/uz.json');
        $pathRu = base_path('resources/tests/all questions/ru.json');
        $pathKiril = base_path('resources/tests/all questions/kiril.json');

        if (!File::exists($pathUz) || !File::exists($pathRu) || !File::exists($pathKiril)) {
            $this->error('JSON files not found in resources/tests/all questions/');
            return 1;
        }

        $this->info('Reading JSON files...');
        $dataUz = json_decode(File::get($pathUz), true)['data']['data'];
        $dataRu = json_decode(File::get($pathRu), true)['data']['data'];
        $dataKiril = json_decode(File::get($pathKiril), true)['data']['data'];

        $this->info('Indexing data...');
        $ruMap = collect($dataRu)->keyBy('id');
        $kirilMap = collect($dataKiril)->keyBy('id');

        $limit = $this->option('limit');
        $count = 0;
        $total = count($dataUz);

        $this->info("Starting sync for $total questions...");
        $bar = $this->output->createProgressBar($limit ? min($limit, $total) : $total);
        $bar->start();

        foreach ($dataUz as $qUz) {
            if ($limit && $count >= $limit) break;

            $newId = $qUz['id'];
            $qRu = $ruMap[$newId] ?? null;
            $qKiril = $kirilMap[$newId] ?? null;

            DB::transaction(function () use ($qUz, $qRu, $qKiril, $newId) {
                // 1. Find or Create TestQuestion
                $testQuestion = TestQuestion::updateOrCreate(
                    ['new_question_id' => $newId],
                    ['question_file' => $this->extractType2($qUz['body'])]
                );

                // 2. Sync Question Translations
                $this->syncQuestionTranslation($testQuestion->id, 'uz', $this->extractType1($qUz['body']));
                if ($qRu) {
                    $this->syncQuestionTranslation($testQuestion->id, 'ru', $this->extractType1($qRu['body']));
                }
                if ($qKiril) {
                    $this->syncQuestionTranslation($testQuestion->id, 'kiril', $this->extractType1($qKiril['body']));
                }

                // 3. Sync Options (Answers)
                $existingOptions = $testQuestion->options()->orderBy('id')->get();
                foreach ($qUz['answers'] as $index => $ansUz) {
                    $isCorrect = $ansUz['check'] == 1;
                    $optionTextUz = $this->extractType1($ansUz['body']);

                    if (isset($existingOptions[$index])) {
                        $option = $existingOptions[$index];
                        $option->update(['is_correct' => $isCorrect]);
                    } else {
                        $option = TestOption::create([
                            'test_question_id' => $testQuestion->id,
                            'option' => $optionTextUz,
                            'is_correct' => $isCorrect,
                        ]);
                    }

                    // Answer Translations
                    $this->syncOptionTranslation($option->id, 'uz', $optionTextUz);
                    if ($qRu && isset($qRu['answers'][$index])) {
                        $this->syncOptionTranslation($option->id, 'ru', $this->extractType1($qRu['answers'][$index]['body']));
                    }
                    if ($qKiril && isset($qKiril['answers'][$index])) {
                        $this->syncOptionTranslation($option->id, 'kiril', $this->extractType1($qKiril['answers'][$index]['body']));
                    }
                }

                // 4. Update Answer (Explanation)
                Answer::updateOrCreate(
                    ['test_question_id' => $testQuestion->id],
                    [
                        'answer_description' => $qUz['answer_description'] ?? '',
                        'answer_resource' => $qUz['answer_video'] ?? ''
                    ]
                );
            });

            $bar->advance();
            $count++;
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully synced $count questions.");
        return 0;
    }

    private function extractType1($body)
    {
        foreach ($body as $item) {
            if ($item['type'] == 1) return $item['value'];
        }
        return '';
    }

    private function extractType2($body)
    {
        foreach ($body as $item) {
            if ($item['type'] == 2) return $item['value'];
        }
        return null;
    }

    private function syncQuestionTranslation($questionId, $lang, $text)
    {
        QuestionTranslation::updateOrCreate(
            ['question_id' => $questionId, 'language' => $lang],
            ['question' => $text]
        );
    }

    private function syncOptionTranslation($optionId, $lang, $text)
    {
        OptionTranslation::updateOrCreate(
            ['option_id' => $optionId, 'language' => $lang],
            ['option' => $text]
        );
    }
}
