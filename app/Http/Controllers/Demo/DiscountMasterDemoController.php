<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiscountMasterDemoController extends Controller
{
    public function index(Request $request)
    {
        return view('demo.discount-master-demo');
    }
}
