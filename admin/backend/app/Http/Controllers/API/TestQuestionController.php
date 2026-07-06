<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TestQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestQuestionController extends Controller
{
    /**
     * Display a listing of the test questions.
     */
    public function index(Request $request)
    {
        $query = TestQuestion::with(['translations', 'options', 'answer']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Get 20 random questions for "Aralash" test.
     */
    public function random(Request $request)
    {
        $limit = $request->get('limit', 20);
        $lang = $request->get('lang');

        $query = \App\Models\ShablonQuestion::query();

        if ($lang) {
            $mappedLangs = [$lang];
            if ($lang === 'uz' || $lang === 'uz-lat') {
                $mappedLangs = ['uz', 'uz-lat'];
            } elseif ($lang === 'uz-cyr' || $lang === 'kiril') {
                $mappedLangs = ['uz-cyr', 'kiril'];
            }

            $query->whereHas('translations', function ($q) use ($mappedLangs) {
                $q->whereIn('language', $mappedLangs);
            });
        }

        $questions = $query->with([
            'translations',
            'options',
            'options.translations',
            'answers'
        ])
        ->inRandomOrder()
        ->limit($limit)
        ->get();

        $this->normalizeQuestionTranslations($questions);

        return response()->json($questions);
    }

    /**
     * Helper to duplicate translations for frontend language code compatibility.
     */
    private function normalizeQuestionTranslations($questions)
    {
        $questions->each(function($question) {
            if ($question->translations) {
                $newTranslations = [];
                foreach ($question->translations as $trans) {
                    $newTranslations[] = $trans;
                    if ($trans->language === 'uz-lat') {
                        $clone = clone $trans;
                        $clone->language = 'uz';
                        $newTranslations[] = $clone;
                    } elseif ($trans->language === 'uz-cyr') {
                        $clone = clone $trans;
                        $clone->language = 'kiril';
                        $newTranslations[] = $clone;
                    } elseif ($trans->language === 'uz') {
                        $clone = clone $trans;
                        $clone->language = 'uz-lat';
                        $newTranslations[] = $clone;
                    } elseif ($trans->language === 'kiril') {
                        $clone = clone $trans;
                        $clone->language = 'uz-cyr';
                        $newTranslations[] = $clone;
                    }
                }
                $question->setRelation('translations', collect($newTranslations));
            }

            if ($question->options) {
                foreach ($question->options as $option) {
                    if ($option->translations) {
                        $newOptTranslations = [];
                        foreach ($option->translations as $trans) {
                            $newOptTranslations[] = $trans;
                            if ($trans->language === 'uz-lat') {
                                $clone = clone $trans;
                                $clone->language = 'uz';
                                $newOptTranslations[] = $clone;
                            } elseif ($trans->language === 'uz-cyr') {
                                $clone = clone $trans;
                                $clone->language = 'kiril';
                                $newOptTranslations[] = $clone;
                            } elseif ($trans->language === 'uz') {
                                $clone = clone $trans;
                                $clone->language = 'uz-lat';
                                $newOptTranslations[] = $clone;
                            } elseif ($trans->language === 'kiril') {
                                $clone = clone $trans;
                                $clone->language = 'uz-cyr';
                                $newOptTranslations[] = $clone;
                            }
                        }
                        $option->setRelation('translations', collect($newOptTranslations));
                    }
                }
            }
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question_uz' => 'required|string',
            'options' => 'required|string', // JSON string for options since it might come as multipart form data
        ]);

        try {
            DB::beginTransaction();

            $questionFilePath = null;
            if ($request->hasFile('question_file')) {
                $path = $request->file('question_file')->store('tests/images changed', 'public');
                $questionFilePath = '/storage/' . $path;
            }

            $maxId = TestQuestion::max('new_question_id') ?? 10000;
            $newId = $maxId + 1;

            $question = TestQuestion::create([
                'new_question_id' => $newId,
                'question_file' => $questionFilePath,
            ]);

            $langs = ['uz', 'ru', 'kiril'];
            foreach ($langs as $lang) {
                if ($request->has("question_{$lang}")) {
                    \App\Models\QuestionTranslation::create([
                        'question_id' => $question->id,
                        'language' => $lang,
                        'question' => $request->input("question_{$lang}"),
                    ]);
                }
            }

            $optionsData = json_decode($request->input('options'), true);
            if ($optionsData && is_array($optionsData)) {
                foreach ($optionsData as $opt) {
                    $testOption = \App\Models\TestOption::create([
                        'test_question_id' => $question->id,
                        'is_correct' => isset($opt['is_correct']) ? filter_var($opt['is_correct'], FILTER_VALIDATE_BOOLEAN) : false,
                    ]);

                    foreach ($langs as $lang) {
                        if (isset($opt["text_{$lang}"]) && !empty($opt["text_{$lang}"])) {
                            \App\Models\OptionTranslation::create([
                                'option_id' => $testOption->id,
                                'language' => $lang,
                                'option' => $opt["text_{$lang}"],
                            ]);
                        }
                    }
                }
            }

            if ($request->filled('answer_description') || $request->filled('answer_resource')) {
                \App\Models\Answer::create([
                    'test_question_id' => $question->id,
                    'answer_description' => $request->input('answer_description'),
                    'answer_resource' => $request->input('answer_resource'),
                ]);
            }

            DB::commit();

            return response()->json($question->load(['translations', 'options.translations', 'answer']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Savolni saqlashda xatolik: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified test question from storage.
     */
    public function destroy(TestQuestion $testQuestion)
    {
        $testQuestion->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }
}
