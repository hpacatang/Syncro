@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 fw-bold">Submit Content</h1>
        <p class="text-muted">Share your content with the PAIR office for review and enhancement</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Submission Form Card -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="submissionForm">
                        @csrf
                        
                        <!-- Caption Input -->
                        <div class="mb-4">
                            <label for="caption" class="form-label fw-bold">
                                <i class="fas fa-file-alt text-primary"></i> Caption/Description
                            </label>
                            <textarea 
                                id="caption" 
                                name="original_caption" 
                                class="form-control" 
                                rows="5" 
                                placeholder="Write your post caption here. Be clear and descriptive..."
                                required></textarea>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Your caption will be reviewed and enhanced by PAIR staff
                            </small>
                        </div>

                        <!-- Link Input (Optional) -->
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

                        <!-- Media Upload (Optional) -->
                        <div class="mb-4">
                            <label for="media" class="form-label fw-bold">
                                <i class="fas fa-image text-primary"></i> Upload Images (Optional)
                            </label>
                            <div class="form-control p-3 text-center" style="border: 2px dashed #dee2e6; cursor: pointer;" id="dropZone">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem;" class="text-muted"></i>
                                <p class="text-muted mt-2 mb-0">Drag and drop images here or click to browse</p>
                                <small class="text-muted">JPEG, PNG, GIF (Max 2MB each)</small>
                                <input 
                                    type="file" 
                                    id="media" 
                                    name="media[]" 
                                    class="form-control d-none" 
                                    multiple 
                                    accept="image/*">
                            </div>
                            <div id="mediaPreview" class="mt-3"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="fas fa-paper-plane"></i> Submit for Review
                            </button>
                            <a href="{{ route('org.dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mt-4 border-0">
                <i class="fas fa-lightbulb"></i>
                <strong>Pro Tip:</strong> The more detailed your caption, the better PAIR can enhance it. Include key details like dates, event names, and main points.
            </div>
        </div>
    </div>
</div>

<!-- Script for Form Handling -->
<script>
// Add link input
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

// Drag and drop for media
const dropZone = document.getElementById('dropZone');
const mediaInput = document.getElementById('media');
const mediaPreview = document.getElementById('mediaPreview');

dropZone.addEventListener('click', () => mediaInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#0d6efd';
    dropZone.style.backgroundColor = '#f8f9fa';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#dee2e6';
    dropZone.style.backgroundColor = 'white';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#dee2e6';
    mediaInput.files = e.dataTransfer.files;
    updateMediaPreview();
});

mediaInput.addEventListener('change', updateMediaPreview);

function updateMediaPreview() {
    mediaPreview.innerHTML = '';
    const files = mediaInput.files;
    
    if (files.length === 0) return;

    const preview = document.createElement('div');
    preview.className = 'row g-2';
    
    Array.from(files).forEach((file, index) => {
        const col = document.createElement('div');
        col.className = 'col-md-3';
        
        const reader = new FileReader();
        reader.onload = (e) => {
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" class="img-fluid rounded" style="height: 120px; object-fit: cover; width: 100%;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeMedia(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
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

// Form submission
document.getElementById('submissionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    try {
        const response = await fetch('/api/submissions', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <i class="fas fa-check-circle"></i> <strong>Success!</strong> Your submission has been sent to PAIR for review.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            form.parentElement.insertBefore(alert, form);
            
            // Reset form
            form.reset();
            mediaPreview.innerHTML = '';
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = "{{ route('org.dashboard') }}";
            }, 2000);
        } else {
            alert('Error: ' + data.message);
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

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@endsection
