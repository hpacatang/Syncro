@props([
    'submission',
    'title' => null,
    'body' => null,
    'live' => false,
])

@php
    use App\Support\SubmissionMedia;

    $isArticle = $submission->isWebsiteArticle();
    $previewTitle = $title ?? ($submission->enhanced_title ?: $submission->article_title);
    $previewBody = $body ?? ($submission->enhanced_caption ?: $submission->original_caption);
    $imagePath = collect($submission->media_paths ?? [])
        ->first(fn ($path) => is_string($path) && SubmissionMedia::isImage($path));
    $imageUrl = $imagePath ? SubmissionMedia::url($imagePath) : null;
    $pageName = config('services.meta.page_name', 'USJR');
@endphp

<section class="card border shadow-sm channel-preview" aria-label="{{ $isArticle ? 'Website article preview' : 'Facebook Page preview' }}">
    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
        <strong>{{ $isArticle ? 'Website article preview' : 'Facebook Page preview' }}</strong>
        <span class="badge text-bg-light border">Preview only</span>
    </div>
    <div class="card-body p-0">
        @if($isArticle)
            <article class="p-4">
                <div class="small text-uppercase text-muted fw-semibold mb-2">University news</div>
                <h2
                    class="h4 fw-bold mb-3"
                    @if($live) data-preview-title-for="reviewTitle" @endif
                >{{ $previewTitle }}</h2>
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" class="img-fluid rounded mb-3 w-100" alt="Submission image preview">
                @endif
                <div
                    class="channel-preview__body"
                    @if($live) data-preview-body-for="reviewBody" @endif
                >{{ $previewBody }}</div>
            </article>
        @else
            <div class="p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="channel-preview__avatar" aria-hidden="true">SJ</span>
                    <div class="lh-sm">
                        <div class="fw-semibold">{{ $pageName }}</div>
                        <div class="small text-muted">Facebook Page · Preview</div>
                    </div>
                </div>
                <div
                    class="channel-preview__body mb-3"
                    @if($live) data-preview-body-for="reviewBody" @endif
                >{{ $previewBody }}</div>
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" class="img-fluid rounded w-100" alt="Submission image preview">
                @elseif(!empty($submission->links[0]))
                    <div class="border rounded p-3 bg-light">
                        <div class="small text-uppercase text-muted">Link preview</div>
                        <div class="fw-semibold text-break">{{ $submission->links[0] }}</div>
                    </div>
                @endif
                <div class="d-flex justify-content-around border-top mt-3 pt-2 small text-muted">
                    <span><i class="bi bi-hand-thumbs-up me-1"></i>Like</span>
                    <span><i class="bi bi-chat me-1"></i>Comment</span>
                    <span><i class="bi bi-share me-1"></i>Share</span>
                </div>
            </div>
        @endif
    </div>
</section>

@once
    <style>
        .channel-preview {
            max-width: 640px;
            overflow: hidden;
            border-radius: .75rem;
        }

        .channel-preview__avatar {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #e7f0ff;
            color: #1551a1;
            font-size: .8rem;
            font-weight: 700;
        }

        .channel-preview__body {
            white-space: pre-line;
            overflow-wrap: anywhere;
        }
    </style>
@endonce

@if($live)
    @push('scripts')
        <script>
            (() => {
                document.querySelectorAll('.channel-preview [data-preview-body-for], .channel-preview [data-preview-title-for]').forEach((previewField) => {
                    const input = document.getElementById(
                        previewField.dataset.previewBodyFor || previewField.dataset.previewTitleFor
                    );
                    if (!input) return;
                    const update = () => { previewField.textContent = input.value; };
                    input.addEventListener('input', update);
                    update();
                });
            })();
        </script>
    @endpush
@endif
