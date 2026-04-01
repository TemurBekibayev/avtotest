<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Instructor::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $instructor = Instructor::create($validated);
        return response()->json($instructor, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Instructor $instructor)
    {
        return response()->json($instructor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Instructor $instructor)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $instructor->update($validated);
        return response()->json($instructor);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Instructor $instructor)
    {
        $instructor->delete();
        return response()->noContent();
    }
}
