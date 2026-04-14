@extends('layouts.app')

@section('title', 'Grazie')

@section('content')
<div class="page-app site-thanks text-center py-5">
    <div class="sm-thanks-icon mx-auto"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
    <h1 class="site-headline-md mb-3">Grazie per la partecipazione</h1>
    <p class="text-muted mb-4">Le tue risposte sono state registrate.</p>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 justify-content-center align-items-center">
        <a class="btn site-btn-pill-primary" href="{{ route('home') }}">Torna alla home</a>
        <a class="btn btn-outline-primary rounded-pill fw-semibold px-4" href="{{ route('surveys.public.index') }}">Torna ai sondaggi pubblici</a>
    </div>
</div>
@endsection
