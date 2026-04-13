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
            
            $templates = \App\Models\StudentTestTemplate::all();
            
            if ($student) {
                foreach ($templates as $tpl) {
                    // Manually find the latest result for THIS template for THIS student
                    $latestResult = \App\Models\TestResult::where('student_id', $student->id)
                        ->where('student_test_template_id', $tpl->id)
                        ->latest()
                        ->first();
                    
                    // Attach it to the template object
                    $tpl->latest_result = $latestResult;
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
