@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold">AI caption from media</h1>
        <p class="text-muted mb-0">Upload reference files (same rules as org submissions). Default tone: <a href="{{ route('settings.tone') }}">tone settings</a>.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card syncro-card-elevated border-0">
                <div class="card-body p-4">
                    <form id="captionAssistForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Provider</label>
                            <select name="llm_provider" class="form-select" id="llm_provider">
                                <option value="openai">OpenAI</option>
                                <option value="gemini">Gemini</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes / context</label>
                            <textarea name="notes" class="form-control" rows="4" placeholder="Event name, audience, key facts…"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Files (optional)</label>
                            <input type="file" name="media[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.pdf,.txt">
                            <small class="text-muted">Max 5&nbsp;MB each.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" id="capSubmit">Generate caption</button>
                    </form>
                    <div id="capResult" class="mt-4 d-none">
                        <label class="form-label fw-semibold">Suggested caption</label>
                        <textarea id="capText" class="form-control" rows="8" readonly></textarea>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="navigator.clipboard.writeText(document.getElementById('capText').value)">Copy</button>
                    </div>
                    <div id="capError" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('captionAssistForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('capSubmit');
    const err = document.getElementById('capError');
    const resBox = document.getElementById('capResult');
    const capText = document.getElementById('capText');
    err.classList.add('d-none');
    resBox.classList.add('d-none');
    btn.disabled = true;

    const fd = new FormData(this);
    try {
        const r = await fetch(@json(route('api.pair.caption-from-media')), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await r.json();
        if (data.success && data.data?.caption) {
            capText.value = data.data.caption;
            resBox.classList.remove('d-none');
        } else {
            err.textContent = data.message || 'Generation failed';
            err.classList.remove('d-none');
        }
    } catch (x) {
        err.textContent = x.message || 'Request failed';
        err.classList.remove('d-none');
    }
    btn.disabled = false;
});
</script>
@endpush
