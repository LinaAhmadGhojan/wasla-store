<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SectionController extends Controller
{
    public function show(string $section)
    {
        return view('admin.section', ['section' => $section]);
    }
}
