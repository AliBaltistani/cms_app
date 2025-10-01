@extends('layouts.master')

@section('styles')
<!-- Filepond CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond/filepond.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css')}}">
<!-- Select2 CSS -->
{{-- <link rel="stylesheet" href="{{asset('build/assets/libs/select2/css/select2.min.css')}}"> --}}
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/flatpickr/flatpickr.min.css')}}">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Create Nutrition Plan</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create Plan</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.index')}}" class="btn btn-secondary btn-wave waves-effect waves-light me-2">
            <i class="ri-arrow-left-line me-1"></i> Back to Plans
        </a>
    </div>
</div>
<!-- Page Header Close -->

<form id="nutritionPlanForm" action="{{ route('admin.nutrition-plans.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <!-- Main Plan Information -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Plan Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 mb-3">
                            <label for="plan_name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="plan_name" name="plan_name" placeholder="Enter plan name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter plan description"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="goal_type" class="form-label">Goal Type</label>
                            <select class="form-select" id="goal_type" name="goal_type">
                                <option value="">Select Goal Type</option>
                                <option value="weight_loss">Weight Loss</option>
                                <option value="weight_gain">Weight Gain</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="muscle_gain">Muscle Gain</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="target_weight" class="form-label">Target Weight (kg)</label>
                            <input type="number" class="form-control" id="target_weight" name="target_weight" placeholder="Enter target weight" min="1" max="300" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="duration_days" class="form-label">Duration (Days)</label>
                            <input type="number" class="form-control" id="duration_days" name="duration_days" placeholder="Enter duration in days" min="1" max="365">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="draft">Draft</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignment Section -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Plan Assignment
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-6 mb-3">
                            <label for="trainer_id" class="form-label">Trainer</label>
                            <select class="form-select select2" id="trainer_id" name="trainer_id">
                                <option value="">Select Trainer (Optional)</option>
                                @foreach($trainers as $trainer)
                                    <option value="{{$trainer->id}}">{{$trainer->name}} ({{$trainer->email}})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Leave empty for admin-created global plans</small>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select select2" id="client_id" name="client_id">
                                <option value="">Select Client (Optional)</option>
                                @foreach($clients as $client)
                                    <option value="{{$client->id}}">{{$client->name}} ({{$client->email}})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Leave empty for unassigned plans</small>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1">
                                <label class="form-check-label" for="is_global">
                                    Global Plan
                                </label>
                            </div>
                            <small class="text-muted">Global plans can be viewed by all trainers and reused for multiple clients</small>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                <label class="form-check-label" for="is_featured">
                                    <i class="ri-star-line me-1"></i>Featured Plan
                                </label>
                            </div>
                            <small class="text-muted">Featured plans will be highlighted and promoted in the system</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tags Section -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Tags & Categories
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <select class="form-select select2-tags" id="tags" name="tags[]" multiple>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                                <option value="vegetarian">Vegetarian</option>
                                <option value="vegan">Vegan</option>
                                <option value="keto">Keto</option>
                                <option value="low-carb">Low Carb</option>
                                <option value="high-protein">High Protein</option>
                                <option value="gluten-free">Gluten Free</option>
                                <option value="dairy-free">Dairy Free</option>
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Add tags to categorize and filter plans easily</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Macronutrient Targets Section -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Macronutrient Targets
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-6 mb-3">
                            <label for="protein" class="form-label">Protein (g)</label>
                            <input type="number" class="form-control" id="protein" name="protein" placeholder="Enter protein target" min="0" max="500" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="carbs" class="form-label">Carbs (g)</label>
                            <input type="number" class="form-control" id="carbs" name="carbs" placeholder="Enter carbs target" min="0" max="800" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="fats" class="form-label">Fats (g)</label>
                            <input type="number" class="form-control" id="fats" name="fats" placeholder="Enter fats target" min="0" max="200" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="total_calories" class="form-label">Total Calories</label>
                            <input type="number" class="form-control" id="total_calories" name="total_calories" placeholder="Enter total calories" min="0" max="5000" step="1">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dietary Restrictions Section -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Dietary Restrictions
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 mb-3">
                            <h6 class="fw-semibold mb-2">Dietary Preferences</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="vegetarian" name="vegetarian" value="1">
                                        <label class="form-check-label" for="vegetarian">Vegetarian</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="vegan" name="vegan" value="1">
                                        <label class="form-check-label" for="vegan">Vegan</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="keto" name="keto" value="1">
                                        <label class="form-check-label" for="keto">Ketogenic</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <h6 class="fw-semibold mb-2">Allergens & Intolerances</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="gluten_free" name="gluten_free" value="1">
                                        <label class="form-check-label" for="gluten_free">Gluten-Free</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="dairy_free" name="dairy_free" value="1">
                                        <label class="form-check-label" for="dairy_free">Dairy-Free</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="nut_free" name="nut_free" value="1">
                                        <label class="form-check-label" for="nut_free">Nut-Free</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Upload & Actions -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Plan Media
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="media_file" class="form-label">Plan Image</label>
                        <input type="file" class="filepond" name="media_file" id="media_file" accept="image/*">
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Upload an image to represent this nutrition plan</small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Actions
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-wave waves-effect waves-light">
                            <i class="ri-save-line me-1"></i> Create Plan
                        </button>
                        <button type="button" class="btn btn-success btn-wave waves-effect waves-light" id="saveAndAddMeals">
                            <i class="ri-restaurant-line me-1"></i> Create & Add Meals
                        </button>
                        <a href="{{route('admin.nutrition-plans.index')}}" class="btn btn-light btn-wave waves-effect waves-light">
                            <i class="ri-close-line me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-information-line me-1"></i> Help
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading">Creating Nutrition Plans</h6>
                        <ul class="mb-0 ps-3">
                            <li>Fill in the basic plan information</li>
                            <li>Assign to a trainer and/or client</li>
                            <li>Add relevant tags for easy filtering</li>
                            <li>Upload a representative image</li>
                            <li>After creation, you can add meals and set macros</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<!-- Filepond JS -->
<script src="{{asset('build/assets/libs/filepond/filepond.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-crop/filepond-plugin-image-crop.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-resize/filepond-plugin-image-resize.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-transform/filepond-plugin-image-transform.min.js')}}"></script>

<!-- Select2 JS -->
{{-- <script src="{{asset('build/assets/libs/select2/js/select2.min.js')}}"></script> --}}

<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
$(document).ready(function() {
    // Initialize Filepond
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateSize,
        FilePondPluginImageEdit,
        FilePondPluginFileValidateType,
        FilePondPluginImageCrop,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );

    const pond = FilePond.create(document.querySelector('#media_file'), {
        labelIdle: 'Drag & Drop your image or <span class="filepond--label-action">Browse</span>',
        imagePreviewHeight: 120,
        imageCropAspectRatio: '16:9',
        imageResizeTargetWidth: 800,
        imageResizeTargetHeight: 450,
        stylePanelLayout: 'compact',
        styleLoadIndicatorPosition: 'center bottom',
        styleProgressIndicatorPosition: 'right bottom',
        styleButtonRemoveItemPosition: 'left bottom',
        styleButtonProcessItemPosition: 'right bottom',
        acceptedFileTypes: ['image/*'],
        maxFileSize: '2MB',
        server: {
            process: null,
            revert: null,
            restore: null,
            load: null,
            fetch: null
        }
    });

    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Select an option',
        allowClear: true,
        width: '100%'
    });

    $('.select2-tags').select2({
        placeholder: 'Add tags',
        allowClear: true,
        tags: true,
        width: '100%'
    });

    // Form submission
    $('#nutritionPlanForm').on('submit', function(e) {
        e.preventDefault();
        submitForm(false);
    });

    $('#saveAndAddMeals').on('click', function() {
        submitForm(true);
    });

    function submitForm(redirectToMeals = false) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Show loading
        const submitBtn = redirectToMeals ? $('#saveAndAddMeals') : $('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="ri-loader-2-line spin me-1"></i> Creating...').prop('disabled', true);

        // Prepare form data
        const formData = new FormData();
        
        // Add form fields
        formData.append('_token', $('input[name="_token"]').val());
        formData.append('plan_name', $('#plan_name').val());
        formData.append('description', $('#description').val());
        formData.append('goal_type', $('#goal_type').val());
        formData.append('target_weight', $('#target_weight').val());
        formData.append('duration_days', $('#duration_days').val());
        formData.append('status', $('#status').val());
        formData.append('trainer_id', $('#trainer_id').val());
        formData.append('client_id', $('#client_id').val());
        formData.append('is_global', $('#is_global').is(':checked') ? 1 : 0);
        formData.append('is_featured', $('#is_featured').is(':checked') ? 1 : 0);
        
        // Add tags
        const tags = $('#tags').val();
        if (tags && tags.length > 0) {
            tags.forEach(function(tag, index) {
                formData.append('tags[' + index + ']', tag);
            });
        }

        // Add file if selected
        const pondFiles = pond.getFiles();
        if (pondFiles.length > 0) {
            formData.append('media_file', pondFiles[0].file);
        }

        // Submit form
        $.ajax({
            url: '{{ route("admin.nutrition-plans.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (redirectToMeals) {
                            window.location.href = '/admin/nutrition-plans/' + response.plan.id + '/meals';
                        } else {
                            window.location.href = '/admin/nutrition-plans/' + response.plan.id;
                        }
                    });
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        const field = $('#' + key);
                        field.addClass('is-invalid');
                        field.siblings('.invalid-feedback').text(errors[key][0]);
                    });
                    
                    Swal.fire('Validation Error', 'Please check the form for errors', 'error');
                } else {
                    Swal.fire('Error!', 'Failed to create nutrition plan', 'error');
                }
            },
            complete: function() {
                // Reset button
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

    // Auto-calculate duration based on goal type
    $('#goal_type').on('change', function() {
        const goalType = $(this).val();
        const durationField = $('#duration_days');
        
        if (!durationField.val()) {
            const suggestedDurations = {
                'weight_loss': 90,
                'weight_gain': 120,
                'maintenance': 30,
                'muscle_gain': 180
            };
            
            if (suggestedDurations[goalType]) {
                durationField.val(suggestedDurations[goalType]);
            }
        }
    });

    // Global plan checkbox logic
    $('#is_global').on('change', function() {
        if ($(this).is(':checked')) {
            $('#client_id').val('').trigger('change').prop('disabled', true);
            $('#trainer_id').val('').trigger('change');
        } else {
            $('#client_id').prop('disabled', false);
        }
    });
});
</script>

<style>
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.filepond--root {
    margin-bottom: 0;
}

.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #dee2e6;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 10px;
}

.select2-container--default .select2-selection--multiple {
    min-height: 38px;
    border: 1px solid #dee2e6;
}
</style>
@endsection