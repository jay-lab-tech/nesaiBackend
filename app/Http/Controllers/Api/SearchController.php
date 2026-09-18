<?php
namespace App\Http\Controllers\Api;
use App\Services\SearchService; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class SearchController { public function __invoke(Request $request, SearchService $search): JsonResponse { $validated = $request->validate(['q' => ['required','string','min:2','max:100']]); return response()->json(['data' => $search->search($validated['q']), 'meta' => ['query' => $validated['q']], 'message' => null]); } }
