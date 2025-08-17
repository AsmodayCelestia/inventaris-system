<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    // GET /api/divisions
    public function index()
    {
        return Division::select('id', 'name')->orderBy('name')->get();
    }

    // POST /api/divisions
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name',
        ]);
        $division = Division::create($validated);
        return response()->json($division, 201);
    }

    // GET /api/divisions/{id}
    public function show(Division $division)
    {
        return $division;
    }

    // PUT/PATCH /api/divisions/{id}
    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name,'.$division->id,
        ]);
        $division->update($validated);
        return $division;
    }

    // DELETE /api/divisions/{id}
    public function destroy(Division $division)
    {
        $division->delete();
        return response()->json(null, 204);
    }
}