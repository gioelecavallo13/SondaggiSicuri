@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    $createSurveyHref = auth()->check() ? route('surveys.create') : route('register');
    $createSurveyNote = auth()->check() ? '' : 'Serve un account gratuito per creare e gestire i sondaggi.';
@endphp

<section class="site-home-hero mb-4 mb-lg-5">
    <div class="site-home-hero__glow" aria-hidden="true"></div>
    <div class="row align-items-center g-4 g-lg-5 position-relative">
        <div class="col-lg-6">
            <p class="site-label-md text-primary mb-3">Per creator, team e ricerca</p>
            <h1 class="site-display-lg mb-3">
                Crea <span class="text-primary">sondaggi moderni</span> in pochi minuti
            </h1>
            <p class="lead mb-4">
                Progetta domande a risposta singola o multipla, condividi il link e analizza i risultati in tempo reale.
            </p>
            <div class="d-flex flex-wrap gap-3 pt-1">
                <a class="site-btn-pill-primary site-btn-pill-primary--lg" href="{{ $createSurveyHref }}">Crea il tuo sondaggio</a>
                <a class="site-home-hero-secondary" href="{{ route('surveys.public.index') }}">Esplora sondaggi pubblici</a>
            </div>
            @if($createSurveyNote !== '')
                <p class="small mt-3 mb-0 text-muted">{{ $createSurveyNote }}</p>
            @endif
            <div class="trust-strip pt-4" role="list" aria-label="Caratteristiche principali">
                <span class="trust-pill" role="listitem"><i class="bi bi-gift" aria-hidden="true"></i> Gratis</span>
                <span class="trust-pill" role="listitem"><i class="bi bi-ui-checks-grid" aria-hidden="true"></i> Singola e multipla</span>
                <span class="trust-pill" role="listitem"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> Statistiche</span>
                <span class="trust-pill" role="listitem"><i class="bi bi-link-45deg" aria-hidden="true"></i> Link da condividere</span>
            </div>
        </div>
        <div class="col-lg-6 d-none d-md-block">
            <div class="site-home-visual-card position-relative">
                <img
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuA7sGAocg36o6IWsGrLB07KlrZTV1yoe-nc3IJmjUhUbSIHOOMMBIvaNcylAklLu2et_y1OvnxVbwFGA7GiYfdnaai-gbGRoc8gIV7baLvyXm9xbi1Zk0SuqK_aaO6oaQrvf_6QOWzWJdpC0OOMMOVGtiXlLfOykMr3PaF7yRFHTqv0EbBzUfKbgg3AkwpTpmeSyyHv9fNK979LOYFWV-qAj-OCbrvB5JQC-tN-AobWMQMWv9eG97tVUD20xtfTTpSe7TEOCbJsC2Wn"
                    alt=""
                    width="640"
                    height="400"
                    loading="lazy"
                    decoding="async"
                >
                <div class="site-home-visual-float">
                    <i class="bi bi-bar-chart-fill" aria-hidden="true"></i>
                    <span class="site-font-headline fw-bold small d-block">Analisi in tempo reale</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="home-section py-5" aria-labelledby="how-heading">
    <div class="site-how-title-wrap">
        <p class="section-eyebrow mb-2" data-reveal>Come funziona</p>
        <h2 id="how-heading" class="site-headline-md mb-0" data-reveal>Tre passaggi per raccogliere opinioni</h2>
        <div class="site-how-title-bar" data-reveal aria-hidden="true"></div>
    </div>
    <div class="row g-4">
        <div class="col-md-4" data-reveal data-reveal-stagger style="--stagger-index: 0">
            <div class="site-step-tile h-100">
                <div class="site-step-icon"><i class="bi bi-person-plus" aria-hidden="true"></i></div>
                <h3>Registrati</h3>
                <p>Crea un account gratuito e accedi alla dashboard.</p>
            </div>
        </div>
        <div class="col-md-4" data-reveal data-reveal-stagger style="--stagger-index: 1">
            <div class="site-step-tile h-100">
                <div class="site-step-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></div>
                <h3>Costruisci il sondaggio</h3>
                <p>Usa il builder dinamico con domande singola o multipla.</p>
            </div>
        </div>
        <div class="col-md-4" data-reveal data-reveal-stagger style="--stagger-index: 2">
            <div class="site-step-tile h-100">
                <div class="site-step-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></div>
                <h3>Condividi e analizza</h3>
                <p>Raccogli risposte e consulta statistiche con grafici.</p>
            </div>
        </div>
    </div>
</section>

<section class="home-section pt-0 site-section-surface-low" id="sondaggi" aria-labelledby="public-surveys-heading">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4" data-reveal>
        <div>
            <p class="section-eyebrow mb-2">Sondaggi aperti</p>
            <h2 id="public-surveys-heading" class="site-headline-md mb-2">Sondaggi pubblici in evidenza</h2>
            <p class="text-muted mb-0 site-body-lg">Partecipa ai sondaggi condivisi dalla community.</p>
        </div>
        <a class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-2 site-font-headline" href="{{ route('surveys.public.index') }}">
            Vedi tutti i sondaggi
            <i class="bi bi-arrow-right site-icon-muted" aria-hidden="true"></i>
        </a>
    </div>
    @if($surveys->isEmpty())
        <div class="site-card p-4 p-md-5 text-center" data-reveal>
            <p class="mb-3 text-muted">Nessun sondaggio pubblico disponibile al momento.</p>
            <a class="site-btn-pill-primary" href="{{ $createSurveyHref }}">Crea il primo sondaggio</a>
        </div>
    @else
        @include('surveys.partials.public-survey-cards', ['surveys' => $surveys, 'staggerReveal' => true, 'gridClass' => 'col-md-6 col-lg-4', 'rowGutterClass' => 'g-4'])
        <p class="text-center mt-4 mb-0" data-reveal>
            <a class="site-home-hero-secondary" href="{{ route('surveys.public.index') }}">Vedi tutti i sondaggi pubblici</a>
        </p>
    @endif
</section>

<section class="home-section pt-0 pb-5" aria-labelledby="footer-cta-heading">
    <div class="site-home-cta-panel" data-reveal>
        <div class="position-relative">
            <h2 id="footer-cta-heading">Pronto a raccogliere opinioni?</h2>
            <p class="lead mb-4">Inizia in pochi minuti: crea il sondaggio e condividi il link con chi vuoi.</p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a class="site-btn-pill-primary site-btn-pill-primary--lg" href="{{ $createSurveyHref }}">Crea il tuo sondaggio</a>
                <a class="site-home-hero-secondary" href="{{ route('contacts.index') }}">Contattaci</a>
            </div>
        </div>
    </div>
</section>
@endsection
