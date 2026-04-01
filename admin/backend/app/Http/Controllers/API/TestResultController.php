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

        if ($user->role === 'admin') {
            return response()->json(TestResult::with(['student.group', 'template'])->latest()->paginate(20));
        }

        $student = $user->student;
        if (!$student) {
            return response()->json([], 404);
        }

        return response()->json(TestResult::with('template')
            ->where('student_id', $student->id)
            ->latest()
            ->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $templateId = $request->test_template_id;
        if ($templateId === 'mixed') {
            $templateId = null;
        }

        $request->merge(['test_template_id' => $templateId]);

        $request->validate([
            'test_template_id' => 'nullable|exists:test_templates,id',
            'score' => 'required|integer|min:0',
            'taken_at' => 'nullable|date',
        ]);

        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['message' => 'Foydalanuvchi talaba emas.'], 403);
        }

        if ($templateId) {
            $template = \App\Models\TestTemplate::findOrFail($templateId);
            $passed = $request->score >= $template->passing_score;
        } else {
            $passed = $request->score >= 90; // Default passing score for mixed tests
        }

        $result = TestResult::create([
            'student_id' => $student->id,
            'test_template_id' => $templateId,
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
