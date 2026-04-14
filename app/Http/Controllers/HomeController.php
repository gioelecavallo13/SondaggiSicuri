<?php

namespace App\Http\Controllers;

use App\Models\Sondaggio;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $surveys = Sondaggio::query()
            ->pubblici()
            ->nonScaduti()
            ->with(['tags', 'autore'])
            ->withCount('risposte')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        return view('home.index', [
            'surveys' => $surveys,
        ]);
    }

    public function about(): View
    {
        return view('home.about');
    }
}
