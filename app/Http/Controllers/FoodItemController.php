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

        $query = FoodItem::orderBy('name');

        if (strlen($search) >= 2) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$search . '%']);
        }

        $items = $query->select(['id', 'name', 'category', 'type', 'calories', 'unit'])->limit(50)->get();

        return response()->json($items);
    }
}
