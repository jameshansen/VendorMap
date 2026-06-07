<?php

namespace App\Http\Controllers;

use App\Models\Preset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresetController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Preset::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => 'required|in:table,door,power',
            'name' => 'required|string|max:255',
            'data' => 'required|array',
        ]);

        return response()->json(Preset::create($data), 201);
    }

    public function destroy(Preset $preset): JsonResponse
    {
        $preset->delete();

        return response()->json(['deleted' => true]);
    }
}
