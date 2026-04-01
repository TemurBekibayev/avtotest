<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficSignCategory;
use Illuminate\Http\Request;

class TrafficSignController extends Controller
{
    /**
     * Display a listing of traffic sign categories with their signs.
     */
    public function index()
    {
        $categories = TrafficSignCategory::with('signs')->get();
        return response()->json($categories);
    }
}
