<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return response()->json(Group::with('organization')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
        ]);

        $group = Group::create($validated);
        return response()->json($group, 201);
    }

    public function show(Group $group)
    {
        return response()->json($group->load('organization'));
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'organization_id' => 'sometimes|exists:organizations,id',
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
        ]);

        $group->update($validated);
        return response()->json($group);
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return response()->noContent();
    }
}
