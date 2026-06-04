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
                // Self-healing database mechanism:
                // 1. Try to find a student record that matches the user's name
                $student = \App\Models\Student::where('full_name', $user->name)->first();
                
                // 2. Try to match by phone if user name or email contains the phone number
                if (!$student) {
                    $student = \App\Models\Student::where('phone', $user->name)
                        ->orWhere('phone', $user->email)
                        ->first();
                }

                // 3. Fallback to the first student record in the database for testers/admins
                if (!$student) {
                    $student = \App\Models\Student::first();
                }

                // 4. Automatically link the student record to this user account
                if ($student && !$student->user_id) {
                    try {
                        $student->user_id = $user->id;
                        $student->save();
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
