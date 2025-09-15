@extends('layouts.master')

@section('styles')
<style>
.modal-backdrop {
    z-index: 1040;
}
.modal {
    z-index: 1050;
}
</style>
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">My Certifications</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainer.dashboard') }}">Trainer</a></li>
            <li class="breadcrumb-item active" aria-current="page">Certifications</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Alert Messages -->
<div id="alert-container"></div>

<!-- Certifications List -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    My Certifications
                </div>
                <div class="prism-toggle">
                    <button class="btn btn-sm btn-primary-light" onclick="openCertificationModal()">
                        <i class="ri-add-line me-1"></i>Add New
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table text-nowrap table-striped" id="certificationsTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Certificate Name</th>
                                <th scope="col">Document</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($certifications as $certification)
                            <tr id="certification-{{ $certification->id }}">
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td>{{ $certification->certificate_name }}</td>
                                <td>
                                    @if($certification->doc)
                                        <a href="{{ asset('storage/' . $certification->doc) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-download-line me-1"></i>View
                                        </a>
                                    @else
                                        <span class="badge bg-secondary-transparent">No Document</span>
                                    @endif
                                </td>
                                <td>{{ $certification->created_at->format('d-m-Y') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-success btn-wave waves-effect waves-light" onclick="editCertification({{ $certification->id }})">
                                        <i class="ri-edit-2-line align-middle me-2 d-inline-block"></i>Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light" onclick="deleteCertification({{ $certification->id }})">
                                        <i class="ri-delete-bin-5-line align-middle me-2 d-inline-block"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="ri-award-line fs-48 text-muted mb-3"></i>
                                    <h5 class="fw-semibold mb-2">No Certifications Yet</h5>
                                    <p class="text-muted mb-3">Add your professional certifications to build credibility.</p>
                                    <button class="btn btn-primary" onclick="openCertificationModal()">
                                        <i class="ri-add-line me-1"></i>Add Your First Certification
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if($certifications->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    {{ $certifications->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Certification Modal -->
<div class="modal fade" id="certificationModal" tabindex="-1" aria-labelledby="certificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="certificationModalLabel">Add New Certification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="certificationForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Certificate Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="certificate_name" name="certificate_name" placeholder="Enter certificate name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Certificate Document</label>
                            <input type="file" class="form-control" id="doc" name="doc" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Accepted formats: PDF, JPG, JPEG, PNG (Max: 2MB)</small>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div id="current-document" class="col-md-12 mb-3" style="display: none;">
                            <label class="form-label">Current Document</label>
                            <div id="document-preview"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>
                        Save Certification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Certification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this certification? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <span class="spinner-border spinner-border-sm me-2" id="deleteSpinner" style="display: none;"></span>
                    Delete Certification
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentCertificationId = null;
let isEditMode = false;

// CSRF Token Setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Open Modal for Add/Edit
function openCertificationModal(id = null) {
    isEditMode = id !== null;
    currentCertificationId = id;
    
    // Reset form
    $('#certificationForm')[0].reset();
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    $('#current-document').hide();
    
    if (isEditMode) {
        $('#certificationModalLabel').text('Edit Certification');
        $('#submitBtn').html('<span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>Update Certification');
        loadCertificationData(id);
    } else {
        $('#certificationModalLabel').text('Add New Certification');
        $('#submitBtn').html('<span class="spinner-border spinner-border-sm me-2" id="submitSpinner" style="display: none;"></span>Save Certification');
    }
    
    $('#certificationModal').modal('show');
}

// Load Certification Data for Edit
function loadCertificationData(id) {
    $.ajax({
        url: `/api/trainer/certifications/${id}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const cert = response.data;
                $('#certificate_name').val(cert.certificate_name);
                
                if (cert.doc) {
                    $('#current-document').show();
                    $('#document-preview').html(`
                        <a href="/storage/${cert.doc}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="ri-download-line me-1"></i>View Current Document
                        </a>
                    `);
                }
            }
        },
        error: function() {
            showAlert('Error loading certification data', 'danger');
        }
    });
}

// Edit Certification
function editCertification(id) {
    openCertificationModal(id);
}

// Delete Certification
function deleteCertification(id) {
    currentCertificationId = id;
    $('#deleteModal').modal('show');
}

// Confirm Delete
$('#confirmDelete').click(function() {
    const btn = $(this);
    const spinner = $('#deleteSpinner');
    
    btn.prop('disabled', true);
    spinner.show();
    
    $.ajax({
        url: `/api/trainer/certifications/${currentCertificationId}`,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                $(`#certification-${currentCertificationId}`).fadeOut(300, function() {
                    $(this).remove();
                    // Check if table is empty
                    if ($('#certificationsTable tbody tr').length === 0) {
                        location.reload();
                    }
                });
                showAlert(response.message, 'success');
                $('#deleteModal').modal('hide');
            } else {
                showAlert(response.message || 'Error deleting certification', 'danger');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showAlert(response?.message || 'Error deleting certification', 'danger');
        },
        complete: function() {
            btn.prop('disabled', false);
            spinner.hide();
        }
    });
});

// Form Submit
$('#certificationForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $('#submitBtn');
    const spinner = $('#submitSpinner');
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    submitBtn.prop('disabled', true);
    spinner.show();
    
    const url = isEditMode ? `/api/trainer/certifications/${currentCertificationId}` : '/api/trainer/certifications';
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
                $('#certificationModal').modal('hide');
                
                if (isEditMode) {
                    // Update existing row
                    updateCertificationRow(response.data);
                } else {
                    // Add new row or reload if empty
                    if ($('#certificationsTable tbody tr td[colspan]').length > 0) {
                        location.reload();
                    } else {
                        addCertificationRow(response.data);
                    }
                }
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            
            if (response.errors) {
                // Display validation errors
                Object.keys(response.errors).forEach(function(field) {
                    const input = $(`#${field}`);
                    const feedback = input.siblings('.invalid-feedback');
                    
                    input.addClass('is-invalid');
                    feedback.text(response.errors[field][0]);
                });
            } else {
                showAlert(response?.message || 'Error saving certification', 'danger');
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false);
            spinner.hide();
        }
    });
});

// Update Certification Row
function updateCertificationRow(certification) {
    const row = $(`#certification-${certification.id}`);
    const docCell = certification.doc ? 
        `<a href="/storage/${certification.doc}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="ri-download-line me-1"></i>View
        </a>` : 
        '<span class="badge bg-secondary-transparent">No Document</span>';
    
    row.find('td:eq(0)').text(certification.certificate_name);
    row.find('td:eq(1)').html(docCell);
    row.find('td:eq(2)').text(new Date(certification.created_at).toLocaleDateString('en-GB'));
}

// Add New Certification Row
function addCertificationRow(certification) {
    const rowCount = $('#certificationsTable tbody tr').length + 1;
    const docCell = certification.doc ? 
        `<a href="/storage/${certification.doc}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="ri-download-line me-1"></i>View
        </a>` : 
        '<span class="badge bg-secondary-transparent">No Document</span>';
    
    const newRow = `
        <tr id="certification-${certification.id}">
            <th scope="row">${rowCount}</th>
            <td>${certification.certificate_name}</td>
            <td>${docCell}</td>
            <td>${new Date(certification.created_at).toLocaleDateString('en-GB')}</td>
            <td>
                <button class="btn btn-sm btn-success btn-wave waves-effect waves-light" onclick="editCertification(${certification.id})">
                    <i class="ri-edit-2-line align-middle me-2 d-inline-block"></i>Edit
                </button>
                <button class="btn btn-sm btn-danger btn-wave waves-effect waves-light" onclick="deleteCertification(${certification.id})">
                    <i class="ri-delete-bin-5-line align-middle me-2 d-inline-block"></i>Delete
                </button>
            </td>
        </tr>
    `;
    
    $('#certificationsTable tbody').append(newRow);
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
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection