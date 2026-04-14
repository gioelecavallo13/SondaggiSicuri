@extends('layouts.app')

@section('title', $survey ? 'Modifica sondaggio' : 'Nuovo sondaggio')

@section('content')
@php
    $allTags = $allTags ?? collect();
    $selectedTagIds = old('tag_ids', $survey['tag_ids'] ?? []);
    $selectedTagIds = is_array($selectedTagIds) ? array_map('intval', $selectedTagIds) : [];
    $privacySelected = old('privacy_mode', $survey['privacy_mode'] ?? 'anonymous');
@endphp
<div class="page-app">
    <div class="site-builder-shell">
        <div class="text-center text-md-start mb-4 mb-md-5">
            <span class="site-builder-hero-pill">Editor</span>
            <h1 class="site-builder-title mb-3">{{ $survey ? 'Modifica sondaggio' : 'Crea nuovo sondaggio' }}</h1>
            <p class="site-builder-lead mb-0">
                Imposta titolo, visibilità e domande: ogni partecipante vedrà un flusso chiaro e ordinato.
            </p>
        </div>

        @foreach($formErrors ?? [] as $err)
            <div class="alert alert-danger" role="alert">{{ $err }}</div>
        @endforeach
        @foreach($errors->all() as $message)
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @endforeach

        <form method="post" action="{{ $survey ? route('surveys.update', $survey['id']) : route('surveys.store') }}" id="survey-builder" data-sm-form-loading>
            @csrf
            <section class="site-builder-panel mb-4" aria-labelledby="builder-details-heading">
                <p class="section-label mb-1" id="builder-details-heading">Sondaggio</p>
                <h2 class="site-headline-md h5 mb-3">Dettagli</h2>
                <label class="site-auth-label" for="survey-title">Titolo</label>
                <input
                    class="form-control site-input site-font-headline fw-bold fs-5 mb-3"
                    id="survey-title"
                    type="text"
                    name="title"
                    required
                    placeholder="Es. Preferenze sul prodotto"
                    value="{{ old('title', $survey['titolo'] ?? '') }}"
                >

                <label class="site-auth-label" for="survey-desc">Descrizione</label>
                <textarea
                    class="form-control site-input mb-3"
                    id="survey-desc"
                    name="description"
                    rows="3"
                    placeholder="Spiega brevemente lo scopo del sondaggio (facoltativo)"
                >{{ old('description', $survey['descrizione'] ?? '') }}</textarea>

                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_public" id="is_public" value="1" {{ old('is_public', !isset($survey) || (int)($survey['is_pubblico'] ?? 1) === 1) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="is_public">Pubblico</label>
                </div>
                <p class="form-text small text-muted">Se attivo, il sondaggio compare nella home e nell’elenco pubblico; per rispondere serve comunque registrarsi e accedere.</p>

                <p class="section-label mt-4 mb-2" id="privacy-mode-label">Privacy e partecipanti</p>
                <p class="small text-muted mb-3">Definisce se le risposte sono collegate all’account e cosa vedi nelle statistiche. Dopo la prima risposta non potrai cambiare questa impostazione.</p>
                <div class="d-flex flex-column gap-3 mb-3" role="radiogroup" aria-labelledby="privacy-mode-label">
                    <div class="form-check site-builder-privacy-option">
                        <input class="form-check-input" type="radio" name="privacy_mode" id="privacy_anonymous" value="anonymous" {{ $privacySelected === 'anonymous' ? 'checked' : '' }} required>
                        <label class="form-check-label fw-semibold" for="privacy_anonymous">Anonimo</label>
                        <p class="form-text small text-muted mb-0">Le risposte non sono associate all’utente in archivio. Nelle statistiche vedi solo totali e distribuzioni, non l’elenco dei partecipanti.</p>
                    </div>
                    <div class="form-check site-builder-privacy-option">
                        <input class="form-check-input" type="radio" name="privacy_mode" id="privacy_identified_hidden" value="identified_hidden_answers" {{ $privacySelected === 'identified_hidden_answers' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="privacy_identified_hidden">Identificato — solo elenco partecipanti</label>
                        <p class="form-text small text-muted mb-0">Ogni compilazione è legata all’account. Nelle statistiche puoi vedere chi ha risposto, ma non il dettaglio delle opzioni scelte.</p>
                    </div>
                    <div class="form-check site-builder-privacy-option">
                        <input class="form-check-input" type="radio" name="privacy_mode" id="privacy_identified_full" value="identified_full" {{ $privacySelected === 'identified_full' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="privacy_identified_full">Identificato — elenco e risposte</label>
                        <p class="form-text small text-muted mb-0">Vedi partecipanti e, per ciascuno, le risposte date a ogni domanda (anche nel report PDF).</p>
                    </div>
                </div>

                <label class="site-auth-label mt-3" for="survey-expires">Scadenza (facoltativa)</label>
                <input
                    class="form-control site-input"
                    id="survey-expires"
                    type="datetime-local"
                    name="data_scadenza"
                    value="{{ old('data_scadenza', $survey['data_scadenza'] ?? '') }}"
                >
                <p class="form-text mb-0 small text-muted">Lascia vuoto se il sondaggio non ha una data di chiusura.</p>

                <p class="section-label mt-4 mb-2">Tag</p>
                <p class="small text-muted mb-2">Categorie per filtrare il sondaggio nell’elenco pubblico (facoltativo).</p>
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Tag sondaggio">
                    @foreach($allTags as $tag)
                        <input
                            class="btn-check"
                            type="checkbox"
                            name="tag_ids[]"
                            value="{{ $tag->id }}"
                            id="tag-{{ $tag->id }}"
                            {{ in_array((int) $tag->id, $selectedTagIds, true) ? 'checked' : '' }}
                        >
                        <label class="btn btn-outline-primary btn-sm rounded-pill" for="tag-{{ $tag->id }}">{{ $tag->nome }}</label>
                    @endforeach
                </div>
            </section>

            <section class="site-builder-panel site-builder-panel--questions mb-4" aria-labelledby="builder-questions-heading">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 px-1">
                    <p class="section-label mb-0" id="builder-questions-heading">Contenuto</p>
                    <h2 class="site-headline-md h5 mb-0">Domande</h2>
                </div>
                <div id="questions-container"></div>
                <div class="mt-3">
                    <button type="button" class="btn site-builder-add-dashed w-100 py-4 d-flex flex-column align-items-center gap-2" id="add-question">
                        <i class="bi bi-plus-circle fs-2" aria-hidden="true"></i>
                        <span class="fw-bold">Aggiungi un'altra domanda</span>
                        <span class="small fw-normal text-muted">Scelta singola o multipla, con opzioni personalizzabili</span>
                    </button>
                </div>
            </section>

            <section class="site-builder-panel site-builder-footer mb-4" aria-labelledby="builder-actions-heading">
                <p class="section-label mb-1" id="builder-actions-heading">Pubblicazione</p>
                <h2 class="visually-hidden">Azioni</h2>
                <div class="d-flex flex-column flex-sm-row flex-wrap gap-3 align-items-stretch align-items-sm-center justify-content-sm-between">
                    <div class="d-flex align-items-center gap-2 text-muted small fst-italic d-none d-md-flex">
                        <i class="bi bi-cloud-check" aria-hidden="true"></i>
                        <span>Salva per aggiornare il sondaggio e le domande.</span>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="site-btn-pill-primary site-btn-pill-primary--lg order-sm-2">Salva sondaggio</button>
                        <a class="btn btn-outline-secondary rounded-pill btn-lg order-sm-1" href="{{ route('dashboard') }}">Annulla</a>
                    </div>
                </div>
            </section>
        </form>
    </div>
</div>

<script>
window.__initialQuestions = @json($survey['questions'] ?? [], JSON_UNESCAPED_UNICODE);
</script>
@endsection
