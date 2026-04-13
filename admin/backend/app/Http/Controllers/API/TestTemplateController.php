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
            $templates = \App\Models\StudentTestTemplate::all();
            $student = $request->user()->student;

            if ($student) {
                try {
                    // Fetch all results for this student for these templates
                    // We check if student_test_template_id exists to avoid crashes on older DB versions
                    $results = \App\Models\TestResult::where('student_id', $student->id)
                        ->whereNotNull('student_test_template_id')
                        ->latest()
                        ->get()
                        ->groupBy('student_test_template_id');

                    // Attach the latest result to each template
                    $templates->each(function ($tpl) use ($results) {
                        $tpl->latest_result = $results->get($tpl->id)?->first();
                    });
                } catch (\Exception $e) {
                    // If the column is missing, we just ignore the results part to prevent 500 error
                    \Log::info('TestResults missing student_test_template_id column. Run migrations.');
                }
            }

            return response()->json($templates);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching templates.',
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
            return response()->json($studentTemplate);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching template details.',
                'error' => $e->getMessage()
            ], 500);
        }
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
