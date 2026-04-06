<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Group;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $totalStudents = Student::count();
        $activeGroupsCount = Group::count();
        
        $prevMonthStudents = Student::where('created_at', '<', Carbon::now()->subMonth())->count();
        $studentGrowth = $prevMonthStudents > 0 
            ? round((($totalStudents - $prevMonthStudents) / $prevMonthStudents) * 100) 
            : 0;

        $newGroupsThisWeek = Group::where('created_at', '>=', Carbon::now()->startOfWeek())->count();

        return response()->json([
            'total_students' => $totalStudents,
            'active_groups_count' => $activeGroupsCount,
            'student_growth_percentage' => $studentGrowth,
            'new_groups_this_week' => $newGroupsThisWeek,
        ]);
    }
}
