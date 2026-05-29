<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\StudentTestTemplate;
use App\Models\ShablonQuestion;
use App\Models\ShablonQuestionTranslation;
use App\Models\ShablonOption;
use App\Models\ShablonOptionTranslation;
use App\Models\ShablonAnswer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $uzPath = base_path('resources/tests/new_test/uz.json');
        $ruPath = base_path('resources/tests/new_test/ru.json');
        $kirilPath = base_path('resources/tests/new_test/kiril.json');

        if (!File::exists($uzPath) || !File::exists($ruPath) || !File::exists($kirilPath)) {
            return;
        }

        $uzData = json_decode(File::get($uzPath), true)['data']['questions'] ?? [];
        $ruData = json_decode(File::get($ruPath), true)['data']['questions'] ?? [];
        $kirilData = json_decode(File::get($kirilPath), true)['data']['questions'] ?? [];

        $totalCount = max(count($uzData), count($ruData), count($kirilData));
        $newQuestionIds = [];

        DB::transaction(function () use ($uzData, $ruData, $kirilData, $totalCount, &$newQuestionIds) {
            // Import questions from index 5 to 14 (questions 6 to 15)
            for ($i = 5; $i < min($totalCount, 15); $i++) {
                $uzQ = $uzData[$i] ?? null;
                $ruQ = $ruData[$i] ?? null;
                $krQ = $kirilData[$i] ?? null;

                $jsonId = null;
                if ($uzQ) $jsonId = $uzQ['id'];
                elseif ($ruQ) $jsonId = $ruQ['id'];
                elseif ($krQ) $jsonId = $krQ['id'];

                if (!$jsonId) continue;

                // Skip duplicate
                if (ShablonQuestion::where('json_id', $jsonId)->exists()) {
                    continue;
                }

                // Extract image path
                $imagePath = null;
                $masterQ = $uzQ ?? $ruQ ?? $krQ;
                if (isset($masterQ['body'])) {
                    foreach ($masterQ['body'] as $part) {
                        if (isset($part['type']) && $part['type'] == 2) {
                            $imagePath = $part['value'];
                            break;
                        }
                    }
                }

                // Create Question
                $question = ShablonQuestion::create([
                    'json_id' => $jsonId,
                    'image_path' => $imagePath
                ]);
                $newQuestionIds[] = $question->id;

                // Create Options using UZ as master
                $optionsData = [];
                if ($uzQ && isset($uzQ['answers'])) $optionsData = $uzQ['answers'];
                elseif ($ruQ && isset($ruQ['answers'])) $optionsData = $ruQ['answers'];
                elseif ($krQ && isset($krQ['answers'])) $optionsData = $krQ['answers'];

                $createdOptionIds = [];
                foreach ($optionsData as $opt) {
                    $so = ShablonOption::create([
                        'shablon_question_id' => $question->id,
                        'is_correct' => isset($opt['check']) && $opt['check'] == 1
                    ]);
                    $createdOptionIds[] = $so->id;
                }

                // Create Translations
                $languagesMapping = [
                    'uz-lat' => $uzQ,
                    'ru' => $ruQ,
                    'uz-cyr' => $krQ
                ];

                foreach ($languagesMapping as $lang => $qData) {
                    if (!$qData) continue;

                    $qText = '';
                    if (isset($qData['body'])) {
                        foreach ($qData['body'] as $part) {
                            if (isset($part['type']) && $part['type'] == 1) {
                                $qText = $part['value'];
                                break;
                            }
                        }
                    }

                    if ($qText) {
                        ShablonQuestionTranslation::create([
                            'shablon_question_id' => $question->id,
                            'language' => $lang,
                            'question_text' => $qText
                        ]);
                    }

                    if (isset($qData['answer_description']) || isset($qData['answer_video'])) {
                        ShablonAnswer::create([
                            'shablon_question_id' => $question->id,
                            'language' => $lang,
                            'description' => $qData['answer_description'] ?? null,
                            'video_path' => $qData['answer_video'] ?? null
                        ]);
                    }

                    if (isset($qData['answers'])) {
                        foreach ($qData['answers'] as $optIndex => $optVal) {
                            $optText = '';
                            if (isset($optVal['body'])) {
                                foreach ($optVal['body'] as $part) {
                                    if (isset($part['type']) && $part['type'] == 1) {
                                        $optText = $part['value'];
                                        break;
                                    }
                                }
                            }

                            $optionId = $createdOptionIds[$optIndex] ?? null;
                            if ($optionId && $optText) {
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

            if (empty($newQuestionIds)) {
                return;
            }

            // Sync/Append questions to Shablon 62
            $template = StudentTestTemplate::where('name', 'Shablon 62')
                ->orWhere('id', 62)
                ->first();

            if ($template) {
                $template->questions()->attach($newQuestionIds);

                // Update duration and passing score
                $newCount = $template->questions()->count();
                $template->update([
                    'duration_minutes' => $newCount,
                    'passing_score' => ceil($newCount * 0.9)
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
