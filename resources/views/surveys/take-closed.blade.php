@extends('layouts.app')

@section('title', 'Sondaggio chiuso')

@section('content')
@php
    $tags = $closed['tags'] ?? [];
@endphp
<div class="page-app site-thanks text-center py-5 px-3">
    <div class="sm-survey-closed-icon mx-auto" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
    <h1 class="site-headline-md mb-3">Sondaggio chiuso</h1>
    <p class="fw-semibold mb-2">{{ $closed['titolo'] }}</p>
    @if(!empty($closed['data_scadenza_label']))
        <p class="text-muted mb-4">Non è più possibile inviare risposte. Il termine era il <strong>{{ $closed['data_scadenza_label'] }}</strong>.</p>
    @else
        <p class="text-muted mb-4">Non è più possibile inviare risposte per questo sondaggio.</p>
    @endif
    @if(count($tags) > 0)
        <div class="d-flex flex-wrap gap-1 justify-content-center mb-4" aria-label="Tag del sondaggio">
            @foreach($tags as $tag)
                <span class="badge rounded-pill sm-tag-pill">{{ $tag['nome'] ?? '' }}</span>
            @endforeach
        </div>
    @endif
    @foreach($closedErrors ?? [] as $message)
        <div class="alert alert-danger text-start mx-auto mb-3" style="max-width: 28rem" role="alert">{{ $message }}</div>
    @endforeach
    {{-- Stesso riepilogo privacy della pagina take (solo informativo: nessun invio possibile). --}}
    <div class="text-start mx-auto mb-4" style="max-width: 28rem">
        @include('partials.survey-take-privacy-notice', ['notice' => $closed['privacy_notice'] ?? null])
    </div>
    <a class="btn site-btn-pill-primary" href="{{ route('home') }}">Torna alla home</a>
</div>
@endsection
