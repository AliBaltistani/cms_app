@extends('layouts.master')

@section('styles')
<!-- Filepond CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond/filepond.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css')}}">
<!-- Select2 CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/select2/css/select2.min.css')}}">
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/flatpickr/flatpickr.min.css')}}">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Edit Nutrition Plan</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.show', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light me-2">
            <i class="ri-arrow-left-line me-1"></i> Back to Plan
        </a>
    </div>
</div>
<!-- Page Header Close -->

<form id="nutritionPlanForm" action="{{ route('admin.nutrition-plans.update', $plan->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
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
                            <input type="text" class="form-control" id="plan_name" name="plan_name" placeholder="Enter plan name" value="{{ old('plan_name', $plan->plan_name) }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter plan description">{{ old('description', $plan->description) }}</textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="goal_type" class="form-label">Goal Type</label>
                            <select class="form-select" id="goal_type" name="goal_type">
                                <option value="">Select Goal Type</option>
                                <option value="weight_loss" {{ old('goal_type', $plan->goal_type) == 'weight_loss' ? 'selected' : '' }}>Weight Loss</option>
                                <option value="weight_gain" {{ old('goal_type', $plan->goal_type) == 'weight_gain' ? 'selected' : '' }}>Weight Gain</option>
                                <option value="maintenance" {{ old('goal_type', $plan->goal_type) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                <option value="muscle_gain" {{ old('goal_type', $plan->goal_type) == 'muscle_gain' ? 'selected' : '' }}>Muscle Gain</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="target_weight" class="form-label">Target Weight (kg)</label>
                            <input type="number" class="form-control" id="target_weight" name="target_weight" placeholder="Enter target weight" min="30" max="300" step="0.1" value="{{ old('target_weight', $plan->target_weight) }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="duration_days" class="form-label">Duration (Days)</label>
                            <input type="number" class="form-control" id="duration_days" name="duration_days" placeholder="Enter duration in days" min="1" max="365" value="{{ old('duration_days', $plan->duration_days) }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" {{ old('status', $plan->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $plan->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="draft" {{ old('status', $plan->status) == 'draft' ? 'selected' : '' }}>Draft</option>
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
                                    <option value="{{$trainer->id}}" {{ old('trainer_id', $plan->trainer_id) == $trainer->id ? 'selected' : '' }}>{{$trainer->name}} ({{$trainer->email}})</option>
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
                                    <option value="{{$client->id}}" {{ old('client_id', $plan->client_id) == $client->id ? 'selected' : '' }}>{{$client->name}} ({{$client->email}})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Leave empty for unassigned plans</small>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1" {{ old('is_global', $plan->is_global) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_global">
                                    Global Plan
                                </label>
                            </div>
                            <small class="text-muted">Global plans can be viewed by all trainers and reused for multiple clients</small>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $plan->is_featured) ? 'checked' : '' }}>
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
                            @php
                                // Ensure tags is always an array for in_array() function
                                $planTags = $plan->tags ?? [];
                                if (is_string($planTags)) {
                                    $planTags = json_decode($planTags, true) ?? [];
                                }
                            @endphp
                            <select class="form-select select2-tags" id="tags" name="tags[]" multiple>
                                <option value="beginner" {{ in_array('beginner', old('tags', $planTags)) ? 'selected' : '' }}>Beginner</option>
                                <option value="intermediate" {{ in_array('intermediate', old('tags', $planTags)) ? 'selected' : '' }}>Intermediate</option>
                                <option value="advanced" {{ in_array('advanced', old('tags', $planTags)) ? 'selected' : '' }}>Advanced</option>
                                <option value="vegetarian" {{ in_array('vegetarian', old('tags', $planTags)) ? 'selected' : '' }}>Vegetarian</option>
                                <option value="vegan" {{ in_array('vegan', old('tags', $planTags)) ? 'selected' : '' }}>Vegan</option>
                                <option value="keto" {{ in_array('keto', old('tags', $planTags)) ? 'selected' : '' }}>Keto</option>
                                <option value="low-carb" {{ in_array('low-carb', old('tags', $planTags)) ? 'selected' : '' }}>Low Carb</option>
                                <option value="high-protein" {{ in_array('high-protein', old('tags', $planTags)) ? 'selected' : '' }}>High Protein</option>
                                <option value="gluten-free" {{ in_array('gluten-free', old('tags', $planTags)) ? 'selected' : '' }}>Gluten Free</option>
                                <option value="dairy-free" {{ in_array('dairy-free', old('tags', $planTags)) ? 'selected' : '' }}>Dairy Free</option>
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
                            <input type="number" class="form-control" id="protein" name="protein" placeholder="Enter protein target" min="0" max="500" step="0.1" value="{{ old('protein', $plan->dailyMacros->protein ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="carbs" class="form-label">Carbs (g)</label>
                            <input type="number" class="form-control" id="carbs" name="carbs" placeholder="Enter carbs target" min="0" max="800" step="0.1" value="{{ old('carbs', $plan->dailyMacros->carbs ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="fats" class="form-label">Fats (g)</label>
                            <input type="number" class="form-control" id="fats" name="fats" placeholder="Enter fats target" min="0" max="200" step="0.1" value="{{ old('fats', $plan->dailyMacros->fats ?? '') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="total_calories" class="form-label">Total Calories</label>
                            <input type="number" class="form-control" id="total_calories" name="total_calories" placeholder="Enter total calories" min="0" max="5000" step="1" value="{{ old('total_calories', $plan->dailyMacros->total_calories ?? '') }}">
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
                                        <input class="form-check-input" type="checkbox" id="vegetarian" name="vegetarian" value="1" {{ old('vegetarian', $plan->restrictions->vegetarian ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vegetarian">Vegetarian</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="vegan" name="vegan" value="1" {{ old('vegan', $plan->restrictions->vegan ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vegan">Vegan</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="keto" name="keto" value="1" {{ old('keto', $plan->restrictions->keto ?? false) ? 'checked' : '' }}>
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
                                        <input class="form-check-input" type="checkbox" id="gluten_free" name="gluten_free" value="1" {{ old('gluten_free', $plan->restrictions->gluten_free ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="gluten_free">Gluten-Free</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="dairy_free" name="dairy_free" value="1" {{ old('dairy_free', $plan->restrictions->dairy_free ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="dairy_free">Dairy-Free</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="nut_free" name="nut_free" value="1" {{ old('nut_free', $plan->restrictions->nut_free ?? false) ? 'checked' : '' }}>
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
                    @if($plan->media_url)
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $plan->media_url) }}" alt="Plan Image" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2">
<button type="button" class="btn btn-sm btn-danger" onclick="deleteMedia('{{ $plan->id }}')">
                                    <i class="ri-delete-bin-line me-1"></i> Remove Image
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="media_file" class="form-label">{{ $plan->media_url ? 'Replace Image' : 'Plan Image' }}</label>
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
                            <i class="ri-save-line me-1"></i> Update Plan
                        </button>
                        <a href="{{ route('admin.nutrition-plans.meals.index', $plan->id) }}" class="btn btn-success btn-wave waves-effect waves-light">
                            <i class="ri-restaurant-line me-1"></i> Manage Meals
                        </a>
                        <button type="button" class="btn btn-warning btn-wave waves-effect waves-light" onclick="duplicatePlan('{{ $plan->id }}')">
                            <i class="ri-file-copy-line me-1"></i> Duplicate Plan
                        </button>
                        <a href="{{ route('admin.nutrition-plans.show', $plan->id) }}" class="btn btn-light btn-wave waves-effect waves-light">
                            <i class="ri-eye-line me-1"></i> View Plan
                        </a>
                    </div>
                </div>
            </div>

            <!-- Plan Statistics -->
            @if($plan->meals->count() > 0)
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Current Statistics
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <div class="border rounded p-2">
                                <h5 class="mb-0">{{ $plan->meals->count() }}</h5>
                                <small class="text-muted">Meals</small>
                            </div>
                        </div>
                        <div class="col-6 mb-2">
                            <div class="border rounded p-2">
                                <h5 class="mb-0">{{ number_format($plan->meals->sum('calories_per_serving')) }}</h5>
                                <small class="text-muted">Calories</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</form>
@endsection

@section('scripts')
<!-- Filepond JS -->
<script src="{{asset('build/assets/libs/filepond/filepond.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js')}}"></script>

<!-- Select2 JS -->
<script src="{{asset('build/assets/libs/select2/js/select2.min.js')}}"></script>

<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Select an option',
        allowClear: true
    });
    
    $('.select2-tags').select2({
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'Add tags'
    });
    
    // Initialize Filepond
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType
    );
    
    const pond = FilePond.create(document.querySelector('#media_file'), {
        acceptedFileTypes: ['image/*'],
        maxFileSize: '2MB',
        labelIdle: 'Drag & Drop your image or <span class="filepond--label-action">Browse</span>',
    });
    
    // Form validation
    $('#nutritionPlanForm').on('submit', function(e) {
        let isValid = true;
        
        // Reset previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Validate required fields
        if (!$('#plan_name').val().trim()) {
            $('#plan_name').addClass('is-invalid');
            $('#plan_name').siblings('.invalid-feedback').text('Plan name is required.');
            isValid = false;
        }
        
        if (!$('#status').val()) {
            $('#status').addClass('is-invalid');
            $('#status').siblings('.invalid-feedback').text('Status is required.');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields.',
                icon: 'error'
            });
        }
    });
});

// Delete media function
function deleteMedia(planId) {
    Swal.fire({
        title: 'Delete Media',
        text: 'Are you sure you want to delete this image?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/nutrition-plans/' + planId + '/delete-media',
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        location.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to delete media', 'error');
                }
            });
        }
    });
}

// Duplicate plan function
function duplicatePlan(planId) {
    Swal.fire({
        title: 'Duplicate Plan',
        text: 'This will create a copy of the nutrition plan. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, duplicate it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/nutrition-plans/' + planId + '/duplicate',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        window.location.href = '/admin/nutrition-plans/' + response.duplicate_plan.id;
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to duplicate plan', 'error');
                }
            });
        }
    });
}
</script>
@endsection