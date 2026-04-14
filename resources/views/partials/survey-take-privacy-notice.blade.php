{{--
    Riassunto privacy per il compilatore. $notice: array{heading: string, body: string} da SurveyTakePrivacyNotice::forMode.
--}}
@php
    $notice = $notice ?? null;
@endphp
@if(is_array($notice) && ($notice['heading'] ?? '') !== '' && ($notice['body'] ?? '') !== '')
    <div
        id="survey-take-privacy-region"
        class="alert alert-info d-flex gap-3 mb-4 border-0 shadow-sm site-take-privacy-notice"
        role="region"
        aria-labelledby="survey-take-privacy-heading"
    >
        <i class="bi bi-info-circle flex-shrink-0 fs-5" aria-hidden="true"></i>
        <div class="min-w-0">
            <h2 id="survey-take-privacy-heading" class="h6 fw-bold mb-2">{{ $notice['heading'] }}</h2>
            <p class="mb-0 small">{{ $notice['body'] }}</p>
        </div>
    </div>
@endif
