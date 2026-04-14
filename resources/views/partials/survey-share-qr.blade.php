{{-- `access_token` è obbligatorio: fornito da `SurveyService::toTakeViewArray` per la vista take. --}}
@php
    $surveyId = (int) ($survey['id'] ?? 0);
    $isSurveyPublic = (int) ($survey['is_pubblico'] ?? 0) === 1;
    $shareUrlInputId = "sm-qr-url-{$surveyId}";
    $shareStatusId = "sm-qr-status-{$surveyId}";
    $shareUrl = route('surveys.show', ['sondaggio' => $survey['access_token']]);
@endphp

<section
    class="sm-share-qr site-elevated-panel p-4 mb-4"
    data-sm-qr-share
    data-survey-id="{{ $surveyId }}"
    data-share-url="{{ $shareUrl }}"
>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <p class="section-label mb-1">Condividi il sondaggio</p>
            <h2 class="h5 mb-1">QR Code + link</h2>
            <p class="text-muted small mb-0">Scansiona o copia il link per inviare il sondaggio.</p>
        </div>
    </div>

    <div class="sm-share-qr__grid mt-3">
        <div class="sm-share-qr__canvas-wrap" aria-hidden="true">
            <canvas class="sm-share-qr__canvas" width="220" height="220"></canvas>
        </div>

        <div class="sm-share-qr__link">
            <label class="form-label mb-2" for="{{ $shareUrlInputId }}">{{ $isSurveyPublic ? 'Link pubblico' : 'Link al sondaggio' }}</label>
            <div class="input-group">
                <input
                    id="{{ $shareUrlInputId }}"
                    type="text"
                    class="form-control site-input"
                    readonly
                    value=""
                    data-sm-qr-url-input
                >
                <button
                    class="btn btn-outline-primary"
                    type="button"
                    data-sm-qr-copy
                    aria-controls="{{ $shareStatusId }}"
                >
                    <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copia link
                </button>
            </div>
            <div id="{{ $shareStatusId }}" class="sm-share-qr__status small mt-2" role="status" aria-live="polite"></div>
        </div>
    </div>
</section>
