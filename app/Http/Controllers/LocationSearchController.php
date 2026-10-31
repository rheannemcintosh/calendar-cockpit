<?php

namespace App\Http\Controllers;

use App\Support\LocationSearch;
use App\Support\LocationSearchFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationSearchController extends Controller
{
    /**
     * Search for real places by name or address and return a short list of matches.
     */
    public function __invoke(Request $request, LocationSearch $locationSearch): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'max:256']]);

        try {
            $matches = $locationSearch->search($request->string('q')->trim()->toString());
        } catch (LocationSearchFailed $exception) {
            report($exception);

            return response()->json(['message' => __('Location search is not available right now. Try again later.')], 503);
        }

        return response()->json(['matches' => $matches]);
    }
}
