@extends('layouts.app')

@section('page-title', 'Evaluate submission')
@section('page-subtitle', 'Review the source, prepare an edited draft, and return it for submitter review.')

@section('content')
@php
    use App\Services\SubmissionLifecycleService;

    $isArticle = $submission->isWebsiteArticle();
    $canReject = ! auth()->user()->isPair()
        && collect(app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user()))
            ->contains(fn ($status) => $status->value === 'rejected');
@endphp

<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <span class="badge bg-light text-dark border">{{ $submission->contentTypeLabel() }}</span>
                <x-submission-workflow-badge :submission="$submission" size="sm" />
            </div>
            <h2 class="h4 fw-bold mb-1">Evaluate submission #{{ $submission->id }}</h2>
            <p class="text-muted mb-0">
                {{ $submission->user?->displayName() ?? 'Unknown submitter' }}
                @if($submission->user?->department)
                    · {{ $submission->user->department->displayName() }}
                @endif
                · Routed to {{ $submission->reviewOwnerLabel() }}
            </p>
        </div>
        <a href="{{ route('dashboard.submissions') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to queue
        </a>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xl-7">
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 fw-bold mb-0">Submitted source</h3>
                </div>
                <div class="card-body">
                    @if($isArticle)
                        <div class="mb-3">
                            <span class="small text-muted text-uppercase fw-semibold">Submitted headline</span>
                            <h4 class="h5 mt-1 mb-0">{{ $submission->article_title }}</h4>
                        </div>
                        <span class="small text-muted text-uppercase fw-semibold">Article body</span>
                    @else
                        <span class="small text-muted text-uppercase fw-semibold">Social media post</span>
                    @endif
                    <div class="mt-2 text-break" style="white-space: pre-line">{{ $submission->original_caption }}</div>

                    @if($submission->links && count($submission->links))
                        <div class="mt-4">
                            <h4 class="small text-muted text-uppercase fw-semibold">Related links</h4>
                            <ul class="mb-0">
                                @foreach($submission->links as $link)
                                    <li><a href="{{ $link }}" target="_blank" rel="noopener noreferrer">{{ $link }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </section>

            @if($submission->media_paths && count($submission->media_paths))
                <section class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h3 class="h6 fw-bold mb-0">Attachments</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($submission->media_paths as $path)
                                <div class="col-sm-6 col-lg-4">
                                    <x-submission-media-tile :path="$path" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <div class="col-xl-5">
            <section class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h3 class="h6 fw-bold mb-1">Prepare reviewed draft</h3>
                    <p class="small text-muted mb-0">AI suggestions are drafts. Verify names, dates, and claims before saving.</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="tone" class="form-label fw-semibold">Writing tone</label>
                        <select class="form-select" id="tone">
                            @foreach(['formal' => 'Formal', 'friendly' => 'Friendly', 'enthusiastic' => 'Enthusiastic', 'urgent' => 'Urgent', 'professional' => 'Professional'] as $value => $label)
                                <option value="{{ $value }}" @selected($defaultTone === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="provider" class="form-label fw-semibold">Optional AI writing assistant</label>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="provider">
                                <option value="openai" @disabled(! $configuredProviders['openai'])>ChatGPT / OpenAI{{ $configuredProviders['openai'] ? '' : ' — no key configured' }}</option>
                                <option value="gemini" @disabled(! $configuredProviders['gemini'])>Gemini{{ $configuredProviders['gemini'] ? '' : ' — no key configured' }}</option>
                                <option value="deepseek" @disabled(! $configuredProviders['deepseek'])>DeepSeek{{ $configuredProviders['deepseek'] ? '' : ' — no key configured' }}</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary flex-shrink-0" id="generateButton">
                                <i class="bi bi-magic me-1"></i> Suggest
                            </button>
                        </div>
                        @if(! collect($configuredProviders)->contains(true))
                            <div class="form-text text-warning">No provider keys are configured. Manual editing is still available.</div>
                        @else
                            <div class="form-text">The selected provider receives this submission's text. Review its suggestion before saving.</div>
                        @endif
                    </div>

                    @if($isArticle)
                        <div class="mb-3">
                            <label for="reviewTitle" class="form-label fw-semibold">Reviewed headline</label>
                            <input
                                type="text"
                                class="form-control"
                                id="reviewTitle"
                                maxlength="255"
                                value="{{ old('reviewTitle', $submission->enhanced_title ?: $submission->article_title) }}"
                                required>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="reviewBody" class="form-label fw-semibold">{{ $isArticle ? 'Reviewed article body' : 'Reviewed social media post' }}</label>
                        <textarea
                            class="form-control"
                            id="reviewBody"
                            rows="{{ $isArticle ? 18 : 10 }}"
                            minlength="{{ $isArticle ? 100 : 10 }}"
                            maxlength="{{ $isArticle ? 12000 : 2200 }}"
                            required>{{ old('reviewBody', $submission->enhanced_caption ?: $submission->original_caption) }}</textarea>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>{{ $isArticle ? '100–12,000 characters' : '10–2,200 characters' }}</span>
                            <span id="bodyCount"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reviewNotes" class="form-label fw-semibold">Feedback for submitter <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea
                            class="form-control"
                            id="reviewNotes"
                            rows="3"
                            maxlength="5000"
                            placeholder="Explain important edits or details the submitter should confirm.">{{ old('reviewNotes') }}</textarea>
                        <div class="form-text">This note is shared with the submitter along with the edited draft.</div>
                    </div>

                    <div id="evaluationMessage" class="alert d-none" role="status" aria-live="polite"></div>
                    <div class="d-flex flex-wrap justify-content-between gap-2 border-top pt-3">
                        @if($canReject)
                            <button type="button" class="btn btn-outline-danger" id="rejectButton">
                                <i class="bi bi-x-circle me-1"></i> Reject submission
                            </button>
                        @else
                            <span></span>
                        @endif
                        <button type="button" class="btn btn-success" id="saveButton">
                            <i class="bi bi-send-check me-1"></i> Save & send for review
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(() => {
    const submissionId = @json($submission->id);
    const isArticle = @json($isArticle);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const body = document.getElementById('reviewBody');
    const bodyCount = document.getElementById('bodyCount');
    const message = document.getElementById('evaluationMessage');
    const saveButton = document.getElementById('saveButton');
    const generateButton = document.getElementById('generateButton');
    const minimum = isArticle ? 100 : 10;
    const maximum = isArticle ? 12000 : 2200;

    function showMessage(kind, text) {
        message.className = 'alert alert-' + kind;
        message.textContent = text;
    }

    function updateCount() {
        bodyCount.textContent = `${body.value.length.toLocaleString()} / ${maximum.toLocaleString()}`;
    }

    async function readResponse(response) {
        const data = await response.json();
        if (!response.ok && data.errors) {
            throw new Error(Object.values(data.errors).flat().join(' '));
        }
        return data;
    }

    body.addEventListener('input', updateCount);
    updateCount();

    generateButton.addEventListener('click', async () => {
        generateButton.disabled = true;
        showMessage('info', 'Requesting an AI suggestion. This can take a little while.');

        try {
            const response = await fetch(`/api/submissions/${submissionId}/enhance`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    llm_provider: document.getElementById('provider').value,
                    tone: document.getElementById('tone').value,
                }),
            });
            const data = await readResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'The AI provider could not generate a suggestion.');
            }

            body.value = data.data.enhanced_caption;
            if (isArticle && data.data.enhanced_title) {
                document.getElementById('reviewTitle').value = data.data.enhanced_title;
            }
            updateCount();
            showMessage('success', 'Suggestion ready. Check its facts and make any edits before saving.');
        } catch (error) {
            showMessage('warning', error.message || 'AI assistance is unavailable. You can continue editing manually.');
        } finally {
            generateButton.disabled = false;
        }
    });

    saveButton.addEventListener('click', async () => {
        const value = body.value.trim();
        const title = isArticle ? document.getElementById('reviewTitle').value.trim() : null;

        if (value.length < minimum || value.length > maximum) {
            showMessage('danger', `Content must be between ${minimum} and ${maximum} characters.`);
            body.focus();
            return;
        }
        if (isArticle && !title) {
            showMessage('danger', 'Please provide an article headline.');
            document.getElementById('reviewTitle').focus();
            return;
        }

        saveButton.disabled = true;
        try {
            const response = await fetch(`/api/submissions/${submissionId}/save-manual-caption`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    manual_caption: value,
                    manual_title: title,
                    pair_feedback: document.getElementById('reviewNotes').value.trim(),
                }),
            });
            const data = await readResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'The reviewed draft could not be saved.');
            }

            showMessage('success', 'Saved and sent to the submitter for review.');
            window.setTimeout(() => {
                window.location.assign(@json(route('dashboard.submissions.review', $submission)));
            }, 900);
        } catch (error) {
            showMessage('danger', error.message || 'The reviewed draft could not be saved.');
            saveButton.disabled = false;
        }
    });

    const rejectButton = document.getElementById('rejectButton');
    if (rejectButton) {
        rejectButton.addEventListener('click', async () => {
            const notes = window.prompt('Provide a reason for rejecting this submission (at least 10 characters):');
            if (notes === null) return;
            if (notes.trim().length < 10) {
                showMessage('danger', 'A rejection reason of at least 10 characters is required.');
                return;
            }
            if (!window.confirm('Reject this submission permanently? This action cannot be undone.')) return;

            rejectButton.disabled = true;
            try {
                const response = await fetch(`/api/submissions/${submissionId}/transition`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ status: 'rejected', notes: notes.trim() }),
                });
                const data = await readResponse(response);

                if (!data.success) {
                    throw new Error(data.message || 'The submission could not be rejected.');
                }

                showMessage('success', 'Submission rejected.');
                window.setTimeout(() => window.location.assign(@json(route('dashboard.submissions'))), 900);
            } catch (error) {
                showMessage('danger', error.message || 'The submission could not be rejected.');
                rejectButton.disabled = false;
            }
        });
    }
})();
</script>
@endsection
