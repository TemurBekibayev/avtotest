<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RoadSign;
use App\Models\RoadSignType;
use Illuminate\Http\Request;

class RoadSignController extends Controller
{
    public function getTypes()
    {
        return response()->json(RoadSignType::all());
    }

    public function getSigns(Request $request)
    {
        $query = RoadSign::query();

        if ($request->has('type_id')) {
            $query->where('road_sign_type_id', $request->type_id);
        }

        return response()->json($query->orderBy('order_column', 'asc')->get());
    }
}
