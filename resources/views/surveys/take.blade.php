@extends('layouts.app')

@section('title', 'Compila sondaggio')

@section('content')
@php
    $scadenzaLabel = $survey['data_scadenza_label'] ?? null;
@endphp
<div class="page-survey-take">
    @include('partials.survey-share-qr')
    <section class="site-elevated-panel p-4">
        <p class="section-label mb-1">Compilazione</p>
        <h1 class="site-headline-md mb-2">{{ $survey['titolo'] }}</h1>
        @php
            $takeTags = $survey['tags'] ?? [];
        @endphp
        <div class="d-flex flex-wrap gap-1 mb-3" aria-label="Tag del sondaggio">
            @forelse($takeTags as $tag)
                <span class="badge rounded-pill sm-tag-pill">{{ $tag['nome'] ?? '' }}</span>
            @empty
                <span class="text-muted small">Nessun tag</span>
            @endforelse
        </div>
        @if($scadenzaLabel)
            <p class="d-flex align-items-center gap-2 small text-muted mb-3 mb-md-4">
                <i class="bi bi-calendar-event flex-shrink-0" aria-hidden="true"></i>
                <span>Scadenza: <strong>{{ $scadenzaLabel }}</strong></span>
            </p>
        @endif
        @if(!empty($survey['descrizione']))
            <p class="text-muted mb-4">{{ $survey['descrizione'] }}</p>
        @endif

        <div id="survey-validation-alert" class="alert alert-danger d-none" role="alert" aria-live="polite"></div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold">Progresso compilazione</span>
                <span id="survey-progress-text" class="text-muted small"></span>
            </div>
            <div class="progress sm-progress-take" aria-label="Progresso compilazione sondaggio">
                <div id="survey-progress-bar" class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%"></div>
            </div>
        </div>

        @foreach($takeErrors ?? [] as $error)
            <div class="alert alert-danger" role="alert">{{ $error }}</div>
        @endforeach

        <form
            method="post"
            action="{{ route('surveys.submit', ['sondaggio' => $survey['access_token']]) }}"
            class="mt-2"
            id="survey-take-form"
            data-sm-form-loading
        >
            @csrf
            @php $qi = 0; @endphp
            @foreach($survey['questions'] as $question)
                @php $qi++; @endphp
                <fieldset class="site-take-question" data-question-id="{{ (int) $question['id'] }}">
                    <legend class="h6 mb-3">
                        <span class="text-muted">Domanda {{ $qi }}</span><br>
                        {{ $question['testo'] }}
                    </legend>
                    @foreach($question['options'] as $option)
                        <label class="form-check d-block mb-2">
                            @if($question['tipo'] === 'singola')
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="answers[{{ (int) $question['id'] }}]"
                                    value="{{ (int) $option['id'] }}"
                                    required
                                >
                            @else
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="answers[{{ (int) $question['id'] }}][]"
                                    value="{{ (int) $option['id'] }}"
                                >
                            @endif
                            <span class="form-check-label">{{ $option['testo'] }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @endforeach

            <div class="survey-take-footer mt-4 pt-md-1">
                @include('partials.survey-take-privacy-notice', ['notice' => $survey['privacy_notice'] ?? null])

                <button type="submit" class="btn site-btn-pill-primary btn-lg">Invia risposte</button>
            </div>
        </form>
    </section>
</div>
@endsection
