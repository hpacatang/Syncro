@extends('layouts.app')

@section('page-title', 'Evaluate submission')
@section('page-subtitle', 'Review the source, prepare an edited draft, and return it for submitter review.')

@section('content')
@php
    use App\Services\SubmissionLifecycleService;

    $isArticle = $submission->isWebsiteArticle();
    $canReject = collect(app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user()))
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
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('dashboard.submissions.resolution-letter', $submission) }}" class="btn btn-outline-primary" id="resolutionLetterLink" target="_blank" rel="noopener">
                <i class="bi bi-file-earmark-text me-1"></i> Resolution letter
            </a>
            <a href="{{ route('dashboard.submissions') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to queue
            </a>
        </div>
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
                    <div class="d-flex flex-wrap gap-2 border-top pt-3">
                        <button type="button" class="btn btn-success" id="saveButton">
                            <i class="bi bi-send-check me-1"></i> Save & send for review
                        </button>
                        @if($canReject)
                            <button type="button" class="btn btn-outline-danger" id="rejectButton" data-bs-toggle="modal" data-bs-target="#evaluateRejectModal">
                                <i class="bi bi-x-circle me-1"></i> Reject submission
                            </button>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@if($canReject)
<div class="modal fade" id="evaluateRejectModal" tabindex="-1" aria-labelledby="evaluateRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; border: 1.5px solid #143a22;">
            <div class="modal-header border-0" style="background-color: #143a22; padding: 1.1rem 1.4rem;">
                <h5 class="modal-title fw-bold text-white mb-0" id="evaluateRejectModalLabel" style="font-size: 1.25rem;">
                    Reject Submission #{{ $submission->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white" style="padding: 1.4rem 1.4rem 1.2rem 1.4rem;">
                <p class="text-secondary mb-3" style="font-size: 0.96rem; line-height: 1.45;">
                    Are you sure you want to reject this submission? Once rejected, this action CANNOT BE UNDONE.
                </p>
                <label for="evaluateRejectReason" class="form-label fw-bold text-dark mb-2" style="font-size: 1.05rem;">
                    Rejection reason
                </label>
                <textarea
                    id="evaluateRejectReason"
                    class="form-control"
                    rows="4"
                    placeholder="Provide a clear explanation of why it is rejected..."
                    style="border: 1.5px solid #28a745; border-radius: 8px; padding: 0.75rem; box-shadow: none; font-size: 0.95rem;"
                    maxlength="5000"></textarea>
                <div id="evaluateRejectReasonError" class="text-danger small mt-2 fw-semibold" style="display:none;">
                    A rejection reason of at least 10 characters is required.
                </div>
            </div>
            <div class="modal-footer border-0" style="background-color: #143a22; padding: 0.9rem 1.4rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn" data-bs-dismiss="modal" style="background: transparent; border: 1.5px solid #2f6b43; color: #ffffff; border-radius: 6px; padding: 0.42rem 1.35rem; font-weight: 500;">
                    Cancel
                </button>
                <button type="button" class="btn" id="evaluateConfirmRejectBtn" style="background-color: #dc3545; border: none; color: #ffffff; border-radius: 6px; padding: 0.42rem 1.35rem; font-weight: 500;">
                    Confirm Rejection
                </button>
            </div>
        </div>
    </div>
</div>
@endif

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
    const rejectButton = document.getElementById('rejectButton');
    const rejectModalEl = document.getElementById('evaluateRejectModal');
    const rejectReasonInput = document.getElementById('evaluateRejectReason');
    const rejectReasonError = document.getElementById('evaluateRejectReasonError');
    const confirmRejectBtn = document.getElementById('evaluateConfirmRejectBtn');
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

    function getRejectModal() {
        if (!rejectModalEl) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal) {
            try {
                return bootstrap.Modal.getOrCreateInstance(rejectModalEl);
            } catch (e) {
                return null;
            }
        }
        return null;
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

    function showRejectModal() {
        if (rejectReasonInput) rejectReasonInput.value = '';
        if (rejectReasonError) rejectReasonError.style.display = 'none';
        if (confirmRejectBtn) {
            confirmRejectBtn.disabled = false;
            confirmRejectBtn.textContent = 'Confirm Rejection';
        }

        const bsModal = getRejectModal();
        if (bsModal) {
            bsModal.show();
            return;
        }

        // Direct DOM fallback if bootstrap modal instance cannot be initialized
        if (rejectModalEl) {
            rejectModalEl.classList.add('show');
            rejectModalEl.style.display = 'block';
            rejectModalEl.removeAttribute('aria-hidden');
            rejectModalEl.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');

            let backdrop = document.getElementById('evaluateRejectBackdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'evaluateRejectBackdrop';
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                backdrop.addEventListener('click', hideRejectModal);
            }
        }
    }

    function hideRejectModal() {
        const bsModal = getRejectModal();
        if (bsModal) {
            try { bsModal.hide(); } catch (e) {}
        }
        if (rejectModalEl) {
            rejectModalEl.classList.remove('show');
            rejectModalEl.style.display = 'none';
            rejectModalEl.setAttribute('aria-hidden', 'true');
            rejectModalEl.removeAttribute('aria-modal');
        }
        document.body.classList.remove('modal-open');
        const backdrop = document.getElementById('evaluateRejectBackdrop');
        if (backdrop) backdrop.remove();
    }

    if (rejectModalEl) {
        rejectModalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', hideRejectModal);
        });
    }

    if (rejectButton) {
        rejectButton.addEventListener('click', (e) => {
            e.preventDefault();
            showRejectModal();
        });
    }

    if (confirmRejectBtn) {
        confirmRejectBtn.addEventListener('click', () => {
            const notes = rejectReasonInput ? rejectReasonInput.value.trim() : '';
            if (notes.length < 10) {
                if (rejectReasonError) rejectReasonError.style.display = 'block';
                if (rejectReasonInput) rejectReasonInput.focus();
                return;
            }

            if (rejectReasonError) rejectReasonError.style.display = 'none';
            confirmRejectBtn.disabled = true;
            confirmRejectBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Submitting…';

            hideRejectModal();
            executeRejection(notes);
        });

        if (rejectReasonInput) {
            rejectReasonInput.addEventListener('input', function () {
                if (this.value.trim().length >= 10) {
                    if (rejectReasonError) rejectReasonError.style.display = 'none';
                }
            });
        }
    }

    async function executeRejection(notes) {
        if (rejectButton) rejectButton.disabled = true;
        if (saveButton) saveButton.disabled = true;

        showMessage('info', 'Submitting rejection…');

        try {
            const response = await fetch(`/api/submissions/${submissionId}/transition`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ status: 'rejected', notes: notes }),
            });
            const data = await readResponse(response);

            if (!data.success) {
                throw new Error(data.message || 'The submission could not be rejected.');
            }

            showMessage('success', 'Submission rejected.');
            window.setTimeout(() => window.location.assign(@json(route('dashboard.submissions'))), 800);
        } catch (error) {
            showMessage('danger', error.message || 'The submission could not be rejected.');
            if (rejectButton) rejectButton.disabled = false;
            if (saveButton) saveButton.disabled = false;
            if (confirmRejectBtn) {
                confirmRejectBtn.disabled = false;
                confirmRejectBtn.textContent = 'Confirm Rejection';
            }
        }
    }
})();
</script>
@endsection
