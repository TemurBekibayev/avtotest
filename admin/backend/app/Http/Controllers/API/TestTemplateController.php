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
    public function index()
    {
        return response()->json(\App\Models\StudentTestTemplate::all());
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
        // Fetch from StudentTestTemplate manually since route model binding is bound to TestTemplate
        $studentTemplate = \App\Models\StudentTestTemplate::with('questions.translations', 'questions.options.translations', 'questions.answer')->findOrFail($id);
        return response()->json($studentTemplate);
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
