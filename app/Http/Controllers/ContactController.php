<?php

namespace App\Http\Controllers;

use App\Models\Contatto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        return view('contacts.index', [
            'sent' => $request->session()->pull('contact_sent', false),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'messaggio' => ['required', 'string', 'min:10'],
        ]);

        Contatto::query()->create($validated);

        return redirect()->route('contacts.index')->with('contact_sent', true);
    }
}
