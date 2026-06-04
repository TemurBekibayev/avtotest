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

        // Ensure the student_test_template_id column exists
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('test_results', 'student_test_template_id')) {
                \Illuminate\Support\Facades\Schema::table('test_results', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedBigInteger('student_test_template_id')->nullable()->after('test_template_id');
                });
            }
        } catch (\Exception $ex) {
            \Illuminate\Support\Facades\Log::error('Dynamic column creation in index failed: ' . $ex->getMessage());
        }

        try {
            $query = TestResult::with(['student.group', 'originalTemplate', 'shablonTemplate']);

            if ($user->role === 'admin') {
                return response()->json($query->latest()->paginate(20));
            }

            $student = $user->student;
            if (!$student) {
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
                        \Illuminate\Support\Facades\Log::error('Self-healing link in results index failed: ' . $ex->getMessage());
                    }
                }
            }

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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching test results with relations: ' . $e->getMessage());

            // Fallback to fetch results without broken relations if DB column is missing
            try {
                $query = TestResult::with(['student.group', 'originalTemplate']);
                if ($user->role === 'admin') {
                    return response()->json($query->latest()->paginate(20));
                }

                $student = $user->student;
                if ($student) {
                    return response()->json($query->where('student_id', $student->id)
                        ->latest()
                        ->paginate(15));
                }
            } catch (\Exception $fallbackException) {
                return response()->json([
                    'message' => 'Natijalarni yuklashda xatolik yuz berdi.',
                    'error' => $fallbackException->getMessage()
                ], 500);
            }

            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ]);
        }
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
                    \Illuminate\Support\Facades\Log::error('Self-healing link failed: ' . $ex->getMessage());
                }
            }
        }

        if (!$student) {
            return response()->json(['message' => 'Tizimda hech qanday talaba topilmadi.'], 403);
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

        $questionCount = 20; // Default
        if ($studentTestTemplateId && isset($shablon)) {
            $questionCount = $shablon->question_count ?: 20;
        } elseif ($testTemplateId && isset($template)) {
            $questionCount = $template->question_count ?: 20;
        }
        
        // The Web app sends 'taken_at' and sends 'score' as a percentage (e.g. 100, 20).
        // The Mobile app does not send 'taken_at' and sends 'score' as the count of correct answers.
        if ($request->has('taken_at')) {
            $percentageScore = $request->score;
        } else {
            $percentageScore = $request->score;
            if ($questionCount > 0) {
                $percentageScore = round(($request->score / $questionCount) * 100);
            }
        }

        // Passing score is usually stored as a raw count or a percentage.
        // If passing_score <= questionCount, we assume it's a raw count.
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

        // Ensure the student_test_template_id column exists
        if ($studentTestTemplateId && !\Illuminate\Support\Facades\Schema::hasColumn('test_results', 'student_test_template_id')) {
            \Illuminate\Support\Facades\Schema::table('test_results', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->unsignedBigInteger('student_test_template_id')->nullable();
                // Optionally add foreign key, but since user had issues with migrations, we just add the column
            });
        }

        try {
            $result = TestResult::create($data);
        } catch (\Exception $e) {
            // If it still fails, log it and return error instead of wrong fallback
            \Illuminate\Support\Facades\Log::error('Error saving test result: ' . $e->getMessage());
            return response()->json(['message' => 'Natijani saqlashda xatolik yuz berdi.', 'error' => $e->getMessage()], 500);
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
