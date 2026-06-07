<?php

declare(strict_types=1);

namespace SajidUlIslam\CrudGenerator\Http\Controllers;

use Illuminate\Routing\Controller;

class LandingController extends Controller
{
    public function index()
    {
        return view('crud-generator::landing');
    }
}
