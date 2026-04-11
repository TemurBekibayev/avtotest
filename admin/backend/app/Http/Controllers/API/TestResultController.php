<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TestResult;
use Illuminate\Http\Request;

class TestResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = TestResult::with(['student.group']);

        if ($user->role === 'admin') {
            return response()->json($query->latest()->paginate(20));
        }

        $student = $user->student;
        if (!$student) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ]);
        }

        return response()->json($query->where('student_id', $student->id)
            ->latest()
            ->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $id = $request->test_template_id;
        if ($id === 'mixed') {
            $id = null;
        }

        $request->validate([
            'score' => 'required|integer|min:0',
            'taken_at' => 'nullable|date',
        ]);

        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Foydalanuvchi talaba emas.'], 403);
        }

        $testTemplateId = null;
        $studentTestTemplateId = null;
        $passingScore = 90;

        if ($id) {
            // Check if it's a Shablon test (StudentTestTemplate)
            $shablon = \App\Models\StudentTestTemplate::find($id);
            if ($shablon) {
                $studentTestTemplateId = $shablon->id;
                $passingScore = $shablon->passing_score;
            } else {
                // Check if it's an original test
                $template = \App\Models\TestTemplate::find($id);
                if ($template) {
                    $testTemplateId = $template->id;
                    $passingScore = $template->passing_score;
                }
            }
        }

        $passed = $request->score >= $passingScore;

        $result = TestResult::create([
            'student_id' => $student->id,
            'test_template_id' => $testTemplateId,
            'student_test_template_id' => $studentTestTemplateId,
            'score' => $request->score,
            'passed' => $passed,
            'taken_at' => $request->taken_at ?? now(),
        ]);

        return response()->json($result, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TestResult $testResult)
    {
    //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TestResult $testResult)
    {
    //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TestResult $testResult)
    {
    //
    }
}
