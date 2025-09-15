@extends('layouts.master')

@section('styles')
<style>
.modal-backdrop {
    z-index: 1040;
}
.modal {
    z-index: 1050;
}
.rating-stars {
    font-size: 1.2rem;
    color: #ffc107;
}
.rating-stars .star {
    cursor: pointer;
    transition: color 0.2s;
}
.rating-stars .star:hover,
.rating-stars .star.active {
    color: #ffc107;
}
.rating-stars .star.inactive {
    color: #e4e5e7;
}
</style>
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Client Reviews</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainer.dashboard') }}">Trainer</a></li>
            <li class="breadcrumb-item active" aria-current="page">Reviews</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Alert Messages -->
<div id="alert-container"></div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $testimonials->total() }}</h3>
                        <span class="d-block text-muted">Total Reviews</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-chat-3-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ number_format($testimonials->avg('rate') ?: 0, 1) }}</h3>
                        <span class="d-block text-muted">Average Rating</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-star-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $testimonials->sum('likes') }}</h3>
                        <span class="d-block text-muted">Total Likes</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                            <i class="ri-thumb-up-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $testimonials->sum('dislikes') }}</h3>
                        <span class="d-block text-muted">Total Dislikes</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-danger-transparent">
                            <i class="ri-thumb-down-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials List -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Client Reviews
                </div>
                <div class="prism-toggle">
                    <button class="btn btn-sm btn-primary-light" onclick="openTestimonialModal()">
                        <i class="ri-add-line me-1"></i>Add Review
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table text-nowrap table-striped" id="testimonialsTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Client Name</th>
                                <th scope="col">Rating</th>
                                <th scope="col">Comments</th>
                                <th scope="col">Engagement</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($testimonials as $testimonial)
                            <tr id="testimonial-{{ $testimonial->id }}">
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td>{{ $testimonial->name }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $testimonial->rate)
                                                <i class="ri-star-fill text-warning fs-14"></i>
                                            @else
                                                <i class="ri-star-line text-muted fs-14"></i>
                                            @endif
                                        @endfor
                                        <span class="ms-1 fw-semibold fs-12">{{ $testimonial->rate }}/5</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $testimonial->comments }}">
                                        {{ Str::limit($testimonial->comments, 50) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-success-transparent">
                                            <i class="ri-thumb-up-line me-1"></i>{{ $testimonial->likes }}
                                        </span>
                                        <span class="badge bg-danger-transparent">
                                            <i class="ri-thumb-down-line me-1"></i>{{ $testimonial->dislikes }}
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $testimonial->created_at->format('d-m-Y') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-wave waves-effect waves-light" onclick="viewTestimonial({{ $testimonial->id }})">
                                        <i class="ri-eye-line align-middle me-2 d-inline-block"></i>View
                                    </button>
                                    <button class="btn btn-sm btn-success btn-wave waves-effect waves-light" onclick="editTestimonial({{ $testimonial->id }})">
                                        <i class="ri-edit-2-line align-middle me-2 d-inline-block"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light" onclick="deleteTestimonial({{ $testimonial->id }})">
                                        <i class="ri-delete-bin-5-line align-middle me-2 d-inline-block"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="ri-chat-3-line fs-48 text-muted mb-3"></i>
                                    <h5 class="fw-semibold mb-2">No Reviews Yet</h5>
                                    <p class="text-muted mb-3">You haven't received any client reviews yet.</p>
                                    <button class="btn btn-primary" onclick="openTestimonialModal()">
                                        <i class="ri-add-line me-1"></i>Add Your First Review
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if($testimonials->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $testimonials->appends(request()->query())->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Testimonial Modal -->
<div class="modal fade" id="testimonialModal" tabindex="-1" aria-labelledby="testimonialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testimonialModalLabel">Add New Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="testimonialForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter client name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rating <span class="text-danger">*</span></label>
                            <div class="rating-stars" id="rating-stars">
                                <i class="ri-star-line star" data-rating="1"></i>
                                <i class="ri-star-line star" data-rating="2"></i>
                                <i class="ri-star-line star" data-rating="3"></i>
                                <i class="ri-star-line star" data-rating="4"></i>
                                <i class="ri-star-line star" data-rating="5"></i>
                            </div>
                            <input type="hidden" id="rate" name="rate" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Comments <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Enter review comments" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Likes</label>
                            <input type="number" class="form-control" id="likes" name="likes" value="0" min="0">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dislikes</label>
                            <input type="number" class="form-control" id="dislikes" name="dislikes" value="0" min="0">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>
                        Save Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Testimonial Modal -->
<div class="modal fade" id="viewTestimonialModal" tabindex="-1" aria-labelledby="viewTestimonialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTestimonialModalLabel">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewTestimonialContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this review? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <span class="spinner-border spinner-border-sm me-2" id="deleteSpinner" style="display: none;"></span>
                    Delete Review
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentTestimonialId = null;
let isEditMode = false;
let selectedRating = 0;

// CSRF Token Setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Rating Stars Functionality
$('.star').click(function() {
    selectedRating = $(this).data('rating');
    $('#rate').val(selectedRating);
    updateStars(selectedRating);
});

$('.star').hover(function() {
    const hoverRating = $(this).data('rating');
    updateStars(hoverRating);
}, function() {
    updateStars(selectedRating);
});

function updateStars(rating) {
    $('.star').each(function(index) {
        const starRating = index + 1;
        if (starRating <= rating) {
            $(this).removeClass('ri-star-line inactive').addClass('ri-star-fill active');
        } else {
            $(this).removeClass('ri-star-fill active').addClass('ri-star-line inactive');
        }
    });
}

// Open Modal for Add/Edit
function openTestimonialModal(id = null) {
    isEditMode = id !== null;
    currentTestimonialId = id;
    
    // Reset form
    $('#testimonialForm')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    selectedRating = 0;
    updateStars(0);
    
    if (isEditMode) {
        $('#testimonialModalLabel').text('Edit Review');
        $('#submitBtn').html('<span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>Update Review');
        loadTestimonialData(id);
    } else {
        $('#testimonialModalLabel').text('Add New Review');
        $('#submitBtn').html('<span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>Save Review');
    }
    
    $('#testimonialModal').modal('show');
}

// Load Testimonial Data for Edit
function loadTestimonialData(id) {
    $.ajax({
        url: `/api/trainer/testimonials/${id}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const testimonial = response.data;
                $('#name').val(testimonial.name);
                $('#comments').val(testimonial.comments);
                $('#likes').val(testimonial.likes);
                $('#dislikes').val(testimonial.dislikes);
                selectedRating = testimonial.rate;
                $('#rate').val(selectedRating);
                updateStars(selectedRating);
            }
        },
        error: function() {
            showAlert('Error loading testimonial data', 'danger');
        }
    });
}

// View Testimonial
function viewTestimonial(id) {
    $.ajax({
        url: `/api/trainer/testimonials/${id}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const testimonial = response.data;
                const starsHtml = generateStarsHtml(testimonial.rate);
                
                const content = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Client Name:</strong>
                            <p>${testimonial.name}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Rating:</strong>
                            <div class="d-flex align-items-center gap-2">
                                ${starsHtml}
                                <span class="fw-semibold">${testimonial.rate}/5</span>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong>Comments:</strong>
                            <p>${testimonial.comments}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Likes:</strong>
                            <span class="badge bg-success-transparent">
                                <i class="ri-thumb-up-line me-1"></i>${testimonial.likes}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Dislikes:</strong>
                            <span class="badge bg-danger-transparent">
                                <i class="ri-thumb-down-line me-1"></i>${testimonial.dislikes}
                            </span>
                        </div>
                        <div class="col-md-12">
                            <strong>Created:</strong>
                            <p>${new Date(testimonial.created_at).toLocaleDateString('en-GB')} at ${new Date(testimonial.created_at).toLocaleTimeString('en-GB')}</p>
                        </div>
                    </div>
                `;
                
                $('#viewTestimonialContent').html(content);
                $('#viewTestimonialModal').modal('show');
            }
        },
        error: function() {
            showAlert('Error loading testimonial details', 'danger');
        }
    });
}

// Edit Testimonial
function editTestimonial(id) {
    openTestimonialModal(id);
}

// Delete Testimonial
function deleteTestimonial(id) {
    currentTestimonialId = id;
    $('#deleteModal').modal('show');
}

// Confirm Delete
$('#confirmDelete').click(function() {
    const btn = $(this);
    const spinner = $('#deleteSpinner');
    
    btn.prop('disabled', true);
    spinner.show();
    
    $.ajax({
        url: `/api/trainer/testimonials/${currentTestimonialId}`,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                $(`#testimonial-${currentTestimonialId}`).fadeOut(300, function() {
                    $(this).remove();
                    if ($('#testimonialsTable tbody tr').length === 0) {
                        location.reload();
                    }
                });
                showAlert(response.message, 'success');
                $('#deleteModal').modal('hide');
            } else {
                showAlert(response.message || 'Error deleting testimonial', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response?.message || 'Error deleting testimonial', 'danger');
        },
        complete: function() {
            btn.prop('disabled', false);
            spinner.hide();
        }
    });
});

// Form Submit
$('#testimonialForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $('#submitBtn');
    const spinner = $('#submitSpinner');
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    submitBtn.prop('disabled', true);
    spinner.show();
    
    const url = isEditMode ? `/api/trainer/testimonials/${currentTestimonialId}` : '/api/trainer/testimonials';
    const method = isEditMode ? 'POST' : 'POST';
    
    if (isEditMode) {
        formData.append('_method', 'PUT');
    }
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#testimonialModal').modal('hide');
                
                if (isEditMode) {
                    updateTestimonialRow(response.data);
                } else {
                    if ($('#testimonialsTable tbody tr td[colspan]').length > 0) {
                        location.reload();
                    } else {
                        addTestimonialRow(response.data);
                    }
                }
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            
            if (response.errors) {
                Object.keys(response.errors).forEach(function(field) {
                    const input = $(`#${field}`);
                    const feedback = input.siblings('.invalid-feedback');
                    
                    input.addClass('is-invalid');
                    feedback.text(response.errors[field][0]);
                });
            } else {
                showAlert(response?.message || 'Error saving testimonial', 'danger');
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false);
            spinner.hide();
        }
    });
});

// Helper Functions
function generateStarsHtml(rating) {
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            starsHtml += '<i class="ri-star-fill text-warning fs-14"></i>';
        } else {
            starsHtml += '<i class="ri-star-line text-muted fs-14"></i>';
        }
    }
    return starsHtml;
}

function updateTestimonialRow(testimonial) {
    const row = $(`#testimonial-${testimonial.id}`);
    const starsHtml = generateStarsHtml(testimonial.rate);
    
    row.find('td:eq(0)').text(testimonial.name);
    row.find('td:eq(1)').html(`
        <div class="d-flex align-items-center gap-1">
            ${starsHtml}
            <span class="ms-1 fw-semibold fs-12">${testimonial.rate}/5</span>
        </div>
    `);
    row.find('td:eq(2)').html(`
        <div class="text-truncate" style="max-width: 200px;" title="${testimonial.comments}">
            ${testimonial.comments.substring(0, 50)}${testimonial.comments.length > 50 ? '...' : ''}
        </div>
    `);
    row.find('td:eq(3)').html(`
        <div class="d-flex gap-2">
            <span class="badge bg-success-transparent">
                <i class="ri-thumb-up-line me-1"></i>${testimonial.likes}
            </span>
            <span class="badge bg-danger-transparent">
                <i class="ri-thumb-down-line me-1"></i>${testimonial.dislikes}
            </span>
        </div>
    `);
    row.find('td:eq(4)').text(new Date(testimonial.created_at).toLocaleDateString('en-GB'));
}

function addTestimonialRow(testimonial) {
    const rowCount = $('#testimonialsTable tbody tr').length + 1;
    const starsHtml = generateStarsHtml(testimonial.rate);
    
    const newRow = `
        <tr id="testimonial-${testimonial.id}">
            <th scope="row">${rowCount}</th>
            <td>${testimonial.name}</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    ${starsHtml}
                    <span class="ms-1 fw-semibold fs-12">${testimonial.rate}/5</span>
                </div>
            </td>
            <td>
                <div class="text-truncate" style="max-width: 200px;" title="${testimonial.comments}">
                    ${testimonial.comments.substring(0, 50)}${testimonial.comments.length > 50 ? '...' : ''}
                </div>
            </td>
            <td>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-transparent">
                        <i class="ri-thumb-up-line me-1"></i>${testimonial.likes}
                    </span>
                    <span class="badge bg-danger-transparent">
                        <i class="ri-thumb-down-line me-1"></i>${testimonial.dislikes}
                    </span>
                </div>
            </td>
            <td>${new Date(testimonial.created_at).toLocaleDateString('en-GB')}</td>
            <td>
                <button class="btn btn-sm btn-info btn-wave waves-effect waves-light" onclick="viewTestimonial(${testimonial.id})">
                    <i class="ri-eye-line align-middle me-2 d-inline-block"></i>View
                </button>
                <button class="btn btn-sm btn-success btn-wave waves-effect waves-light" onclick="editTestimonial(${testimonial.id})">
                    <i class="ri-edit-2-line align-middle me-2 d-inline-block"></i>Edit
                </button>
                <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light" onclick="deleteTestimonial(${testimonial.id})">
                    <i class="ri-delete-bin-5-line align-middle me-2 d-inline-block"></i>Delete
                </button>
            </td>
        </tr>
    `;
    
    $('#testimonialsTable tbody').append(newRow);
}

// Show Alert
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="ri-${type === 'success' ? 'check-circle' : 'error-warning'}-line me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('#alert-container').html(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection