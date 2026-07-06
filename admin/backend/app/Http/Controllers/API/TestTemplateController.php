<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TestTemplate;
use Illuminate\Http\Request;

class TestTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $student = $user ? $user->student : null;

            if ($user && !$student) {
                $student = \App\Models\Student::where('full_name', $user->name)
                    ->orWhere('full_name', 'like', '%' . $user->name . '%')
                    ->first();
                
                if (!$student) {
                    $student = \App\Models\Student::where('phone', $user->name)
                        ->orWhere('phone', $user->email)
                        ->first();
                }

                if (!$student) {
                    $student = \App\Models\Student::whereNull('user_id')->first();
                }

                if ($student && !$student->user_id) {
                    try {
                        $student->user_id = $user->id;
                        $student->save();
                        $user->load('student');
                    } catch (\Exception $ex) {
                        \Illuminate\Support\Facades\Log::error('Self-healing link in templates index failed: ' . $ex->getMessage());
                    }
                }
            }
            
            $templates = \App\Models\StudentTestTemplate::withCount('questions')->get();
            
            if ($student) {
                try {
                    // Fetch all test results for this student, ordered by latest.
                    // This replaces 62 sequential database queries with a single query.
                    $allResults = \App\Models\TestResult::where('student_id', $student->id)
                        ->latest()
                        ->get();

                    foreach ($templates as $tpl) {
                        // Match the latest result in-memory
                        $latestResult = $allResults->first(function ($res) use ($tpl) {
                            return $res->student_test_template_id == $tpl->id 
                                || $res->test_template_id == $tpl->id;
                        });
                        
                        if ($latestResult) {
                            $latestResult->makeHidden('template');
                        }
                        
                        $tpl->latest_result = $latestResult;
                    }
                } catch (\Exception $e) {
                    foreach ($templates as $tpl) {
                        $tpl->latest_result = null;
                    }
                }
            }
            
            return response()->json($templates);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching templates. The database might not be initialized.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'duration_minutes' => 'required|integer',
            'passing_score' => 'required|integer',
        ]);

        $template = TestTemplate::create($validated);
        return response()->json($template, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $studentTemplate = \App\Models\StudentTestTemplate::with([
                'questions.translations', 
                'questions.options.translations', 
                'questions.answers'
            ])->findOrFail($id);

            $this->normalizeQuestionTranslations($studentTemplate->questions);

            return response()->json($studentTemplate);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching template details.',
                'error' => $e->getMessage()
            ], 500);
        }
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, TestTemplate $testTemplate)
    {
    //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TestTemplate $testTemplate)
    {
    //
    }
}
