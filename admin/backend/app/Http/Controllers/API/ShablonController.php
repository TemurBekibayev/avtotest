<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StudentTestTemplate;
use App\Models\ShablonQuestion;
use App\Models\ShablonQuestionTranslation;
use App\Models\ShablonOption;
use App\Models\ShablonOptionTranslation;
use App\Models\ShablonAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShablonController extends Controller
{
    /**
     * Display a listing of the shablons.
     */
    public function index(Request $request)
    {
        try {
            $templates = StudentTestTemplate::withCount('questions')->get();
            return response()->json($templates);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching templates.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified shablon with its questions and translations.
     */
    public function show($id)
    {
        try {
            $template = StudentTestTemplate::with([
                'questions.translations',
                'questions.options.translations',
                'questions.answers'
            ])->findOrFail($id);

            return response()->json($template);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching template details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified shablon details (name, duration, passing score).
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'passing_score' => 'required|integer|min:0',
        ]);

        try {
            $template = StudentTestTemplate::findOrFail($id);
            $template->update($validated);
            return response()->json($template);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating template.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a single shablon question, including translations, options, and explanation.
     */
    public function updateQuestion(Request $request, $id)
    {
        $validated = $request->validate([
            'image_path' => 'nullable|string',
            'translations' => 'required|array',
            'translations.*.language' => 'required|string',
            'translations.*.question_text' => 'required|string',
            'options' => 'required|array',
            'options.*.id' => 'required|integer',
            'options.*.is_correct' => 'required|boolean',
            'options.*.translations' => 'required|array',
            'options.*.translations.*.language' => 'required|string',
            'options.*.translations.*.option_text' => 'required|string',
            'answers' => 'nullable|array', // optional explanation per language
            'answers.*.language' => 'required|string',
            'answers.*.description' => 'nullable|string',
            'answers.*.video_path' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $question = ShablonQuestion::findOrFail($id);
            $question->update([
                'image_path' => $request->input('image_path')
            ]);

            // Update translations
            foreach ($request->input('translations') as $trans) {
                ShablonQuestionTranslation::updateOrCreate(
                    [
                        'shablon_question_id' => $question->id,
                        'language' => $trans['language']
                    ],
                    [
                        'question_text' => $trans['question_text']
                    ]
                );
            }

            // Update explanations
            if ($request->has('answers')) {
                foreach ($request->input('answers') as $ans) {
                    ShablonAnswer::updateOrCreate(
                        [
                            'shablon_question_id' => $question->id,
                            'language' => $ans['language']
                        ],
                        [
                            'description' => $ans['description'] ?? '',
                            'video_path' => $ans['video_path'] ?? null
                        ]
                    );
                }
            }

            // Update options and their translations
            foreach ($request->input('options') as $opt) {
                $option = ShablonOption::findOrFail($opt['id']);
                $option->update([
                    'is_correct' => $opt['is_correct']
                ]);

                foreach ($opt['translations'] as $optTrans) {
                    ShablonOptionTranslation::updateOrCreate(
                        [
                            'shablon_option_id' => $option->id,
                            'language' => $optTrans['language']
                        ],
                        [
                            'option_text' => $optTrans['option_text']
                        ]
                    );
                }
            }

            DB::commit();

            // Reload relationships
            $question->load(['translations', 'options.translations', 'answers']);

            return response()->json([
                'message' => 'Question updated successfully',
                'question' => $question
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating question.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import JSON files (UZ, RU, Kiril) and append to existing templates or create new ones.
     */
    public function importJson(Request $request)
    {
        $request->validate([
            'uz_file' => 'nullable|file|mimes:json,txt',
            'ru_file' => 'nullable|file|mimes:json,txt',
            'kiril_file' => 'nullable|file|mimes:json,txt',
        ]);

        try {
            $uzData = $this->parseUploadedJson($request->file('uz_file'));
            $ruData = $this->parseUploadedJson($request->file('ru_file'));
            $kirilData = $this->parseUploadedJson($request->file('kiril_file'));

            if (empty($uzData) && empty($ruData) && empty($kirilData)) {
                return response()->json(['message' => 'No valid data uploaded. Please check file format.'], 400);
            }

            // Use whichever is uploaded to align questions
            $totalCount = max(count($uzData), count($ruData), count($kirilData));
            $newQuestionIds = [];

            DB::beginTransaction();

            for ($i = 0; $i < $totalCount; $i++) {
                $uzQ = $uzData[$i] ?? null;
                $ruQ = $ruData[$i] ?? null;
                $krQ = $kirilData[$i] ?? null;

                // Determine JSON ID (prefer UZ ID)
                $jsonId = null;
                if ($uzQ) $jsonId = $uzQ['id'];
                elseif ($ruQ) $jsonId = $ruQ['id'];
                elseif ($krQ) $jsonId = $krQ['id'];

                if (!$jsonId) continue;

                // Check duplicate
                $existing = ShablonQuestion::where('json_id', $jsonId)->first();
                if ($existing) {
                    // Question already exists in database, skip creating
                    continue;
                }

                // Extract image path and format as /storage/tests/extra-images/filename.jpg
                $imagePath = null;
                $masterQ = $uzQ ?? $ruQ ?? $krQ;
                if (isset($masterQ['body'])) {
                    foreach ($masterQ['body'] as $part) {
                        if (isset($part['type']) && $part['type'] == 2) {
                            $rawPath = $part['value'];
                            $filename = basename($rawPath);
                            $imagePath = "/storage/tests/extra-images/" . $filename;
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

                // Create Options once (using UZ as master, fallback to RU or Kiril)
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

                // Create Translations for all 3 languages
                $languagesMapping = [
                    'uz-lat' => $uzQ,
                    'ru' => $ruQ,
                    'uz-cyr' => $krQ
                ];

                foreach ($languagesMapping as $lang => $qData) {
                    if (!$qData) continue;

                    // Question Text
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

                    // Explanation / Answer Details
                    if (isset($qData['answer_description']) || isset($qData['answer_video'])) {
                        ShablonAnswer::create([
                            'shablon_question_id' => $question->id,
                            'language' => $lang,
                            'description' => $qData['answer_description'] ?? null,
                            'video_path' => $qData['answer_video'] ?? null
                        ]);
                    }

                    // Option Translations
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
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'No new questions imported. All questions in the uploaded file already exist in the database.',
                    'imported_count' => 0
                ]);
            }

            // Sync/Append questions to StudentTestTemplates
            // Find all shablon templates, ordered by name/id
            $templates = StudentTestTemplate::all();
            $shablonTemplates = [];
            foreach ($templates as $tpl) {
                if (stripos($tpl->name, 'Shablon') !== false || $tpl->type === 'Shablon') {
                    $shablonTemplates[] = $tpl;
                }
            }

            // Extract numeric indexes of shablons
            usort($shablonTemplates, function($a, $b) {
                preg_match('/\d+/', $a->name, $matchesA);
                preg_match('/\d+/', $b->name, $matchesB);
                $numA = isset($matchesA[0]) ? (int)$matchesA[0] : 0;
                $numB = isset($matchesB[0]) ? (int)$matchesB[0] : 0;
                return $numA <=> $numB;
            });

            $lastTemplate = end($shablonTemplates);
            $lastTemplateNum = 0;
            if ($lastTemplate) {
                preg_match('/\d+/', $lastTemplate->name, $matches);
                $lastTemplateNum = isset($matches[0]) ? (int)$matches[0] : 0;
            }

            $remainingQuestionIds = $newQuestionIds;

            // Fill last template if it has less than 20 questions
            if ($lastTemplate) {
                $currentCount = $lastTemplate->questions()->count();
                if ($currentCount < 20) {
                    $fillCount = 20 - $currentCount;
                    $slice = array_splice($remainingQuestionIds, 0, $fillCount);
                    $lastTemplate->questions()->attach($slice);

                    // Re-calculate stats
                    $newCount = $lastTemplate->questions()->count();
                    $lastTemplate->update([
                        'duration_minutes' => $newCount,
                        'passing_score' => ceil($newCount * 0.9)
                    ]);
                }
            }

            // Create new templates for remaining questions (20 each)
            $newTemplatesCount = 0;
            if (!empty($remainingQuestionIds)) {
                $chunks = array_chunk($remainingQuestionIds, 20);
                foreach ($chunks as $chunk) {
                    $lastTemplateNum++;
                    $newTpl = StudentTestTemplate::create([
                        'name' => "Shablon {$lastTemplateNum}",
                        'type' => 'Shablon',
                        'duration_minutes' => count($chunk),
                        'passing_score' => ceil(count($chunk) * 0.9)
                    ]);
                    $newTpl->questions()->attach($chunk);
                    $newTemplatesCount++;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'JSON files imported successfully!',
                'imported_questions' => count($newQuestionIds),
                'new_templates_created' => $newTemplatesCount,
                'total_templates_now' => $lastTemplateNum
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error importing JSON: " . $e->getMessage());
            return response()->json([
                'message' => 'Error importing JSON.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to parse the uploaded JSON files.
     */
    private function parseUploadedJson($file)
    {
        if (!$file) return [];
        $content = file_get_contents($file->getRealPath());
        $decoded = json_decode($content, true);

        if (!$decoded) return [];

        // Check if it's the new_test format: {"data" => {"questions" => [...]}}
        if (isset($decoded['data']['questions'])) {
            return $decoded['data']['questions'];
        }

        // Check if it's the raw questions array directly
        if (isset($decoded['questions'])) {
            return $decoded['questions'];
        }

        // Check if it's the savollar format (array of template objects)
        if (is_array($decoded) && isset($decoded[0]['data']['data']['questions'])) {
            $flattened = [];
            foreach ($decoded as $tplObj) {
                $qs = $tplObj['data']['data']['questions'] ?? [];
                foreach ($qs as $q) {
                    $flattened[] = $q;
                }
            }
            return $flattened;
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }
}
