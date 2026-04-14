@extends('layouts.app')

@section('title', 'Contatti')

@section('content')
<div class="page-app">
    <section class="site-contact-hero">
        <div class="site-contact-hero__blobs" aria-hidden="true"></div>
        <div class="position-relative">
            <span class="site-pill-primary-fixed">Supporto &amp; relazioni</span>
            <h1 class="site-display-lg mb-3">
                Siamo qui per <span class="text-primary fst-italic">ascoltarti</span>.
            </h1>
            <p class="lead text-muted site-body-lg mb-0 site-readable-width">
                Hai domande sulla piattaforma o sul progetto? Scrivici dal modulo: il team risponderà appena possibile.
            </p>
        </div>
    </section>

    <section class="row g-4 g-lg-5 pb-4">
        <article class="col-lg-7">
            <div class="site-contact-form-card h-100">
                <h2 class="site-headline-md mb-4">Invia un messaggio</h2>
                @if($sent ?? false)
                    <div class="alert alert-success" role="alert">Messaggio inviato con successo.</div>
                @endif
                @if($errors->any())
                    @foreach($errors->all() as $error)
                        <div class="alert alert-danger" role="alert">{{ $error }}</div>
                    @endforeach
                @endif
                <form id="contact-form" method="post" action="{{ route('contacts.submit') }}" novalidate>
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label" for="contact-nome">Nome</label>
                            <input class="form-control site-input" id="contact-nome" type="text" name="nome" value="{{ old('nome') }}" required autocomplete="name" placeholder="Mario Rossi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contact-email">Email</label>
                            <input class="form-control site-input" id="contact-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="mario@esempio.it">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="form-label" for="contact-msg">Messaggio</label>
                        <textarea class="form-control site-input" id="contact-msg" name="messaggio" rows="6" minlength="10" required placeholder="Descrivi la tua richiesta…">{{ old('messaggio') }}</textarea>
                    </div>
                    <button class="site-btn-pill-primary site-btn-pill-primary--lg mt-4 d-inline-flex align-items-center gap-2" type="submit">
                        <span>Invia messaggio</span>
                        <i class="bi bi-send" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </article>
        <article class="col-lg-5">
            <div class="d-flex flex-column gap-5">
                <div>
                    <h3 class="site-font-headline fw-bold h5 mb-4">Dettagli di contatto</h3>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex gap-3">
                            <span class="site-icon-tile site-icon-tile--pf"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <div>
                                <p class="fw-bold mb-1 small">Email</p>
                                <p class="text-muted small mb-0">Contattaci su assistenza@sondaggisicuri.it</p>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="site-icon-tile site-icon-tile--sec"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
                            <div>
                                <p class="fw-bold mb-1 small">Messaggi</p>
                                <p class="text-muted small mb-0">Usa il modulo accanto per richieste e feedback sul progetto.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="site-faq-wrap site-card border-0 p-4">
                    <h3 class="site-font-headline fw-bold h6 mb-3">FAQ veloci</h3>
                    <details class="site-faq-details">
                        <summary>
                            <span>Come funziona la registrazione?</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="site-faq-body">
                            Crea un account gratuito dalla pagina Registrati per accedere alla dashboard e creare sondaggi.
                        </div>
                    </details>
                    <details class="site-faq-details">
                        <summary>
                            <span>I sondaggi possono essere privati?</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="site-faq-body">
                            Sì: puoi impostare un sondaggio come privato e condividere l’accesso solo con chi ha il link o è autenticato, secondo le opzioni disponibili nell’app.
                        </div>
                    </details>
                </div>

                <div>
                    <h3 class="site-font-headline fw-bold h6 mb-3">Seguici</h3>
                    <div class="d-flex gap-2">
                        <a class="site-social-circle" href="#" aria-label="Facebook (placeholder)"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                        <a class="site-social-circle" href="#" aria-label="LinkedIn (placeholder)"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
                        <a class="site-social-circle" href="#" aria-label="Twitter (placeholder)"><i class="bi bi-twitter" aria-hidden="true"></i></a>
                    </div>
                </div>

                <div class="site-card border-0 overflow-hidden p-0">
                    <iframe
                        class="sm-map-embed site-map-frame"
                        title="Mappa sede"
                        src="https://maps.google.com/maps?q=41.9028,12.4964&z=12&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </article>
    </section>
</div>
@endsection
