@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    
    <div class="mb-4">
        <h1 class="h3 fw-bold">Submit Content</h1>
        <p class="text-muted">
            @if(auth()->user()->isDepartment())
                Submit university-wide content to PAIR for publication.
            @else
                Choose whether this submission is for your Department or the PAIR office.
            @endif
        </p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="submissionForm">
                        @csrf
                        
                        <fieldset class="mb-4">
                            <legend class="form-label fw-bold fs-6">What are you submitting?</legend>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input class="btn-check" type="radio" name="submission_type" id="typeSocial" value="social_media" checked>
                                    <label class="card content-type-card h-100 p-3" for="typeSocial" role="button">
                                        <span class="fw-semibold"><i class="fas fa-share-nodes text-primary me-2"></i>Social media post</span>
                                        <span class="small text-muted mt-2">A concise, informational post for university social channels.</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input class="btn-check" type="radio" name="submission_type" id="typeArticle" value="website_article">
                                    <label class="card content-type-card h-100 p-3" for="typeArticle" role="button">
                                        <span class="fw-semibold"><i class="fas fa-newspaper text-primary me-2"></i>USJR website article</span>
                                        <span class="small text-muted mt-2">A detailed article draft for the official university website.</span>
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">This submits content for review only. Publishing integrations are not enabled.</small>
                        </fieldset>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Send this submission to</label>
                            @if(auth()->user()->department_id && !auth()->user()->isDepartment())
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="review_destination" id="destinationDepartment" value="department" checked>
                                <label class="form-check-label" for="destinationDepartment">
                                    {{ auth()->user()->department?->displayName() ?? 'My Department' }}
                                </label>
                            </div>
                            @endif
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="review_destination" id="destinationPair" value="pair" @checked(auth()->user()->isDepartment() || !auth()->user()->department_id) @if(auth()->user()->isDepartment()) required @endif>
                                <label class="form-check-label" for="destinationPair">PAIR — school-wide publication</label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                @if(auth()->user()->isDepartment())
                                    PAIR oversees official university publications on the website and social media.
                                @else
                                    PAIR can oversee all submissions. Department submissions are reviewed by your Department.
                                @endif
                            </small>
                        </div>

                        <div class="mb-4">
                            <div id="articleTitleGroup" class="mb-3 d-none">
                                <label for="articleTitle" class="form-label fw-bold">Article headline</label>
                                <input
                                    id="articleTitle"
                                    name="article_title"
                                    class="form-control"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Write a clear, factual headline">
                            </div>
                            <label for="caption" id="contentLabel" class="form-label fw-bold">
                                <i class="fas fa-file-alt text-primary"></i> Social media post
                            </label>
                            <textarea 
                                id="caption" 
                                name="original_caption" 
                                class="form-control" 
                                rows="5"
                                minlength="10"
                                maxlength="2200"
                                placeholder="Write a concise, informational post. Include confirmed dates, locations, and key details."
                                required></textarea>
                            <small id="contentHelp" class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Keep it concise. Maximum 2,200 characters.
                            </small>
                        </div>

                        
                        <div class="mb-4">
                            <label for="links" class="form-label fw-bold">
                                <i class="fas fa-link text-primary"></i> Related Links (Optional)
                            </label>
                            <input 
                                type="url" 
                                id="links" 
                                name="links[]" 
                                class="form-control mb-2" 
                                placeholder="https://example.com">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addLinkInput()">
                                + Add Another Link
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Add links to related articles, events, or resources
                            </small>
                        </div>

                        
                        <div class="mb-4">
                            <label for="media" class="form-label fw-bold">
                                <i class="fas fa-paperclip text-primary"></i> Attach files (optional)
                            </label>
                            <div class="form-control p-3 text-center" style="border: 2px dashed #dee2e6; cursor: pointer;" id="dropZone">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem;" class="text-muted"></i>
                                <p class="text-muted mt-2 mb-0">Drag and drop files here or click to browse</p>
                                <small class="text-muted">JPG, PNG, WebP, GIF, DOC/DOCX, PDF, TXT — max 5&nbsp;MB each</small>
                                <input 
                                    type="file" 
                                    id="media" 
                                    name="media[]" 
                                    class="form-control d-none" 
                                    multiple 
                                    accept=".jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.pdf,.txt,image/jpeg,image/png,image/webp,image/gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">
                            </div>
                            <div id="mediaPreview" class="mt-3"></div>
                        </div>

                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="fas fa-paper-plane"></i> Submit for Review
                            </button>
                            <a href="{{ route(auth()->user()->isDepartment() ? 'dashboard' : 'org.dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="alert alert-info mt-4 border-0">
                <i class="fas fa-lightbulb"></i>
                <strong>Pro Tip:</strong> Include key details like dates, event names, and main points to help reviewers prepare your publication.
            </div>
        </div>
    </div>
</div>

<style>
    .content-type-card {
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .btn-check:checked + .content-type-card {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 .15rem rgba(var(--bs-primary-rgb), .15);
    }

    .btn-check:focus-visible + .content-type-card {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }
</style>

<script>
function addLinkInput() {
    const linksContainer = document.getElementById('linksContainer') || createLinksContainer();
    const input = document.createElement('input');
    input.type = 'url';
    input.name = 'links[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'https://example.com';
    linksContainer.appendChild(input);
}

function createLinksContainer() {
    const container = document.createElement('div');
    container.id = 'linksContainer';
    document.getElementById('links').parentElement.parentElement.insertBefore(container, document.getElementById('links').parentElement.nextElementSibling);
    return container;
}

const dropZone = document.getElementById('dropZone');
const mediaInput = document.getElementById('media');
const mediaPreview = document.getElementById('mediaPreview');
const contentTypeInputs = document.querySelectorAll('input[name="submission_type"]');
const articleTitleGroup = document.getElementById('articleTitleGroup');
const articleTitleInput = document.getElementById('articleTitle');
const contentInput = document.getElementById('caption');
const contentLabel = document.getElementById('contentLabel');
const contentHelp = document.getElementById('contentHelp');

function updateContentTypeFields() {
    const isArticle = document.querySelector('input[name="submission_type"]:checked').value === 'website_article';
    articleTitleGroup.classList.toggle('d-none', !isArticle);
    articleTitleInput.disabled = !isArticle;
    articleTitleInput.required = isArticle;
    contentLabel.innerHTML = isArticle
        ? '<i class="fas fa-file-alt text-primary"></i> Article body'
        : '<i class="fas fa-file-alt text-primary"></i> Social media post';
    contentInput.rows = isArticle ? 14 : 5;
    contentInput.minLength = isArticle ? 100 : 10;
    contentInput.maxLength = isArticle ? 12000 : 2200;
    contentInput.placeholder = isArticle
        ? 'Write the complete article body. Include confirmed context, details, and relevant links.'
        : 'Write a concise, informational post. Include confirmed dates, locations, and key details.';
    contentHelp.innerHTML = isArticle
        ? '<i class="fas fa-info-circle"></i> Write a detailed article body of 100 to 12,000 characters.'
        : '<i class="fas fa-info-circle"></i> Keep it concise. Maximum 2,200 characters.';
}

contentTypeInputs.forEach((input) => input.addEventListener('change', updateContentTypeFields));
updateContentTypeFields();

dropZone.addEventListener('click', () => mediaInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#2d7a4a';
    dropZone.style.backgroundColor = '#f8f9fa';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#dee2e6';
    dropZone.style.backgroundColor = 'white';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#dee2e6';
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).forEach((f) => dt.items.add(f));
    mediaInput.files = dt.files;
    updateMediaPreview();
});

mediaInput.addEventListener('change', updateMediaPreview);

const ALLOWED_MEDIA_EXT = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif', 'doc', 'docx', 'pdf', 'txt']);
const MAX_MEDIA_BYTES = 5 * 1024 * 1024;

function fileExtension(name) {
    const i = name.lastIndexOf('.');
    return i === -1 ? '' : name.slice(i + 1).toLowerCase();
}

function formatBytes(n) {
    if (n < 1024) return n + ' B';
    if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
    return (n / (1024 * 1024)).toFixed(1) + ' MB';
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function updateMediaPreview() {
    mediaPreview.innerHTML = '';
    const files = mediaInput.files;
    
    if (files.length === 0) return;

    const preview = document.createElement('div');
    preview.className = 'row g-2';
    
    const imageExts = new Set(['jpg', 'jpeg', 'png', 'webp', 'gif']);

    Array.from(files).forEach((file, index) => {
        const col = document.createElement('div');
        col.className = 'col-md-3';
        const ext = fileExtension(file.name);

        const removeBtn = `
            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeMedia(${index})">
                <i class="fas fa-times"></i>
            </button>`;

        if (imageExts.has(ext)) {
            const reader = new FileReader();
            reader.onload = (e) => {
                col.innerHTML = `
                <div class="position-relative border rounded overflow-hidden bg-light" style="height: 120px;">
                    <img src="${e.target.result}" class="img-fluid w-100 h-100" style="object-fit: cover;">
                    ${removeBtn}
                </div>
                <small class="text-muted text-truncate d-block mt-1" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</small>`;
            };
            reader.readAsDataURL(file);
        } else {
            col.innerHTML = `
                <div class="position-relative border rounded p-2 bg-light d-flex flex-column justify-content-center" style="height: 120px;">
                    <i class="fas fa-file-alt text-primary fs-3 text-center mb-1"></i>
                    <small class="text-truncate text-center d-block" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</small>
                    <small class="text-muted text-center">${ext.toUpperCase()} · ${formatBytes(file.size)}</small>
                    ${removeBtn}
                </div>`;
        }
        preview.appendChild(col);
    });
    
    mediaPreview.appendChild(preview);
}

function removeMedia(index) {
    const files = Array.from(mediaInput.files);
    files.splice(index, 1);
    const dt = new DataTransfer();
    files.forEach(f => dt.items.add(f));
    mediaInput.files = dt.files;
    updateMediaPreview();
}

document.getElementById('submissionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);

    const mediaFiles = mediaInput.files ? Array.from(mediaInput.files) : [];
    for (const f of mediaFiles) {
        const ext = fileExtension(f.name);
        if (!ALLOWED_MEDIA_EXT.has(ext)) {
            alert('Each file must be one of: JPG, PNG, WebP, GIF, DOC, DOCX, PDF, TXT. Rejected: ' + f.name);
            return;
        }
        if (f.size > MAX_MEDIA_BYTES) {
            alert('Each file must be at most 5 MB. Too large: ' + f.name + ' (' + formatBytes(f.size) + ')');
            return;
        }
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    try {
        const response = await fetch('/api/submissions', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="fas fa-check-circle"></i> <strong>Success!</strong> Your submission has been sent to the selected team for review.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            form.parentElement.insertBefore(alert, form);

            form.reset();
            mediaPreview.innerHTML = '';
            updateContentTypeFields();

            setTimeout(() => {
                window.location.href = "{{ route(auth()->user()->isDepartment() ? 'dashboard' : 'org.dashboard') }}";
            }, 2000);
        } else {
            const validationErrors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
            alert('Error: ' + (validationErrors || data.message || 'Submission failed.'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit for Review';
        }
    } catch (error) {
        alert('Error submitting form: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit for Review';
    }
});
</script>

@endsection
