<?php

namespace App\Http\Controllers;

use App\Models\FoodItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->get('search', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $items = FoodItem::where('name', 'like', '%' . $search . '%')
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$search . '%'])
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'calories', 'unit']);

        return response()->json($items);
    }
}
