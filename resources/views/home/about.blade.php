@extends('layouts.app')

@section('title', 'Chi siamo')

@section('content')
<div class="page-app">
    <section class="site-about-hero mb-5">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-lg-6">
                <span class="site-pill-secondary mb-3 d-inline-block">Il progetto</span>
                <h1 class="site-display-lg mb-4">
                    Trasformiamo le <span class="text-primary fst-italic">opinioni</span> in dati.
                </h1>
                <p class="lead text-muted mb-4 site-body-lg">
                    Una piattaforma di sondaggi pensata per essere chiara, moderna e facile da estendere.
                </p>
                <p class="site-body-md text-muted mb-4">
                    Questo progetto è stato ideato e realizzato da un gruppo di studenti di quinta informatica con
                    l'obiettivo di creare uno strumento intuitivo per creare questionari, raccogliere risposte e analizzare i risultati.
                </p>
                <p class="site-body-md text-muted mb-0">
                    Abbiamo progettato il sistema per offrire un'esperienza fluida sia a chi crea i sondaggi sia a chi li compila,
                    con attenzione a usabilità, sicurezza dei dati e qualità del codice.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="site-about-media">
                    <img
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDn7zJ6guhMp7K2EAOdH_KqP0rUwcwjWNMaVDIpuTJPKRnlr2bwoNtPRmRQb0NufcBPtl-8ow0IGz3k89B2RcFUModxWZ3oTyF-l0XXfnnMQUdshL2vGwv-LBWtO4NxAgTW9LCxACsHJrTMImUQCDszlQOtz8lwECZm9EibA1XGjEAvcerJFtcYVRGjcUx3E1njGgYV6CRGgAWEzRMKxwodTPC18f1Z7tyPfXOAHlZl-RjVpUNf-imWsqnNRweM_vd79xzmHEQ-kRuv"
                        alt=""
                        width="640"
                        height="500"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="site-section-surface-low mb-5">
        <div class="mb-5">
            <h2 class="site-headline-md mb-3">Perché scegliere noi</h2>
            <div class="site-how-title-bar site-how-title-bar--start" aria-hidden="true"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="site-feature-bento">
                    <div class="site-step-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></div>
                    <h3 class="site-font-headline fw-bold h5 mb-3">Affidabilità</h3>
                    <p class="text-muted small mb-0">
                        Struttura chiara e buone pratiche per integrità dei dati e privacy dei partecipanti.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="site-feature-bento">
                    <div class="site-step-icon"><i class="bi bi-hand-index-thumb" aria-hidden="true"></i></div>
                    <h3 class="site-font-headline fw-bold h5 mb-3">Facilità d'uso</h3>
                    <p class="text-muted small mb-0">
                        Interfaccia semplice per creare sondaggi in pochi passaggi e raccogliere risposte senza attrito.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="site-feature-bento">
                    <div class="site-step-icon"><i class="bi bi-palette" aria-hidden="true"></i></div>
                    <h3 class="site-font-headline fw-bold h5 mb-3">Stile editoriale</h3>
                    <p class="text-muted small mb-0">
                        Layout coerente e leggibile, pensato per presentare domande e risultati in modo ordinato.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-4">
        <div class="site-card p-4 p-lg-5 border-0">
            <h2 class="site-font-headline fw-bold h5 text-primary mb-2">Missione</h2>
            <p class="mb-0 text-muted">
                Rendere la raccolta di feedback accessibile a tutti, con un'interfaccia coerente e strumenti di analisi chiari.
            </p>
        </div>
    </section>
</div>
@endsection
