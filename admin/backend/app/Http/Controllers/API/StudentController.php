<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(Student::with(['organization', 'group', 'user'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'group_id' => 'required|exists:groups,id',
            'organization_id' => 'nullable|integer',
            'category' => 'required|string|max:50',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,graduated,debtor',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:4',
        ]);

        // Create user account for student
        $plainPassword = $request->password ?: str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $email = $request->email ?: $validated['phone'] . '@eavtotalim.uz';
        
        $user = \App\Models\User::create([
            'name' => $validated['full_name'],
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
            'plain_password' => $plainPassword,
            'role' => 'student',
            'access_expires_at' => now()->addMonths((int)($request->duration_months ?? 1)),
        ]);
        
        unset($validated['email'], $validated['password']);

        $validated['user_id'] = $user->id;

        $student = Student::create($validated);
        
        return response()->json($student->load('user'), 201);
    }

    public function show(Student $student)
    {
        return response()->json($student->load(['organization', 'group']));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'group_id' => 'sometimes|required|exists:groups,id',
            'organization_id' => 'nullable|integer',
            'category' => 'sometimes|required|string|max:50',
            'phone' => 'sometimes|required|string|max:50',
            'address' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,graduated,debtor',
            'email' => 'nullable|email|unique:users,email,' . ($student->user_id ?: 0),
            'password' => 'nullable|string|min:4',
        ]);

        if (!empty($request->email) || !empty($request->password) || !empty($request->duration_months)) {
            $user = $student->user;
            if ($user) {
                if (!empty($request->email)) $user->email = $request->email;
                if (!empty($request->password)) {
                    $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
                    $user->plain_password = $request->password;
                }
                if (!empty($request->duration_months)) {
                    $user->access_expires_at = now()->addMonths((int)$request->duration_months);
                }
                $user->save();
            }
        }

        unset($validated['email'], $validated['password']);

        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->noContent();
    }
}
