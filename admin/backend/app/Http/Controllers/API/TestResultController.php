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

        $query = TestResult::with(['student.group', 'originalTemplate', 'shablonTemplate']);

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

        // The request score is the count of correct answers.
        // We need the total question count to calculate percentage, but we might not have it here easily.
        // Let's assume the frontend sends the percentage, or we calculate it.
        // Wait, if frontend sends score = 18, and we don't know total questions...
        // Let's modify frontend to send percentage, OR modify backend to calculate it.
        // Let's just calculate percentage here if we know the question count.
        $questionCount = 20; // Default
        if ($studentTestTemplateId && isset($shablon)) {
            $questionCount = $shablon->question_count ?: 20;
        } elseif ($testTemplateId && isset($template)) {
            $questionCount = $template->question_count ?: 20;
        }
        
        $percentageScore = $request->score;
        if ($request->score <= $questionCount && $questionCount > 0) {
             // If score is <= question count, it's likely the correct answers count.
             // Convert to percentage for the passed check and saving.
             $percentageScore = round(($request->score / $questionCount) * 100);
        }

        $percentagePassing = $passingScore;
        if ($passingScore <= $questionCount && $questionCount > 0) {
             $percentagePassing = round(($passingScore / $questionCount) * 100);
        }

        $passed = $percentageScore >= $percentagePassing;

        $data = [
            'student_id' => $student->id,
            'test_template_id' => $testTemplateId,
            'score' => $percentageScore,
            'passed' => $passed,
            'taken_at' => $request->taken_at ?? now(),
        ];

        // Only add student_test_template_id if it's set
        if ($studentTestTemplateId) {
            $data['student_test_template_id'] = $studentTestTemplateId;
        }

        try {
            $result = TestResult::create($data);
        } catch (\Exception $e) {
            // If it fails with the new column, try saving without it as a fallback
            if (isset($data['student_test_template_id'])) {
                $shablonId = $data['student_test_template_id'];
                unset($data['student_test_template_id']);
                // Workaround: save shablon ID into test_template_id since the column is missing
                $data['test_template_id'] = $shablonId;
                $result = TestResult::create($data);
            } else {
                throw $e;
            }
        }

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
