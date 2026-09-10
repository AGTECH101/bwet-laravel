<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Sector;

class SectorController extends Controller
{
    public function index()
    {
        $sectors = Sector::all();
        return view('sectors.index', compact('sectors'));
    }
}