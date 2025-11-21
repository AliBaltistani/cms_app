@extends('layouts.master')

@section('styles')
<!-- Filepond CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond/filepond.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css')}}">
<!-- Select2 CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/select2/css/select2.min.css')}}">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Add New Meal</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('trainer.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.meals.index', $plan->id)}}">Meals</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Meal</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('trainer.nutrition-plans.meals.index', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light me-2">
            <i class="ri-arrow-left-line me-1"></i> Back to Meals
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Plan Info -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="alert alert-info" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri-information-line me-2 fs-16"></i>
                <div>
                    <strong>Adding meal to:</strong> {{ $plan->plan_name }}
                    @if($plan->client)
                        <span class="ms-2">• <strong>Client:</strong> {{ $plan->client->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<form id="mealForm" action="{{ route('trainer.nutrition-plans.meals.store', $plan->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <!-- Main Meal Information -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Meal Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 mb-3">
                            <label for="title" class="form-label">Meal Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter meal title" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter meal description"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="meal_type" class="form-label">Meal Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="meal_type" name="meal_type" required>
                                <option value="">Select Meal Type</option>
                                <option value="breakfast">Breakfast</option>
                                <option value="lunch">Lunch</option>
                                <option value="dinner">Dinner</option>
                                <option value="snack">Snack</option>
                                <option value="pre_workout">Pre-Workout</option>
                                <option value="post_workout">Post-Workout</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="servings" class="form-label">Servings <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="servings" name="servings" placeholder="Number of servings" min="1" max="20" value="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="prep_time" class="form-label">Prep Time (minutes)</label>
                            <input type="number" class="form-control" id="prep_time" name="prep_time" placeholder="Preparation time" min="0" max="480">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="cook_time" class="form-label">Cook Time (minutes)</label>
                            <input type="number" class="form-control" id="cook_time" name="cook_time" placeholder="Cooking time" min="0" max="480">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ingredients & Instructions -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Recipe Details
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 mb-3">
                            <label for="ingredients" class="form-label">Ingredients</label>
                            <textarea class="form-control" id="ingredients" name="ingredients" rows="6" placeholder="Enter ingredients (one per line)&#10;Example:&#10;2 cups oats&#10;1 cup milk&#10;1 banana&#10;1 tbsp honey"></textarea>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Enter each ingredient on a new line</small>
                        </div>
                        <div class="col-xl-12 mb-3">
                            <label for="instructions" class="form-label">Instructions</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="6" placeholder="Enter cooking instructions (one step per line)&#10;Example:&#10;1. Heat milk in a saucepan&#10;2. Add oats and cook for 5 minutes&#10;3. Slice banana and add to bowl&#10;4. Drizzle with honey"></textarea>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Enter each instruction step on a new line</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nutritional Information -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Nutritional Information (Per Serving)
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-6 mb-3">
                            <label for="calories_per_serving" class="form-label">Calories</label>
                            <input type="number" class="form-control" id="calories_per_serving" name="calories_per_serving" placeholder="Calories per serving" min="0" max="2000" step="1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="protein_per_serving" class="form-label">Protein (oz)</label>
                            <input type="number" class="form-control" id="protein_per_serving" name="protein_per_serving" placeholder="Protein in oz" min="0" max="200" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="carbs_per_serving" class="form-label">Carbohydrates (oz)</label>
                            <input type="number" class="form-control" id="carbs_per_serving" name="carbs_per_serving" placeholder="Carbs in oz" min="0" max="300" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-xl-6 mb-3">
                            <label for="fats_per_serving" class="form-label">Fats (oz)</label>
                            <input type="number" class="form-control" id="fats_per_serving" name="fats_per_serving" placeholder="Fats in oz" min="0" max="100" step="0.1">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="alert alert-info" role="alert">
                        <i class="ri-information-line me-2"></i>
                        <strong>Tip:</strong> You can use nutrition databases or apps to get accurate nutritional information for your ingredients.
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-xl-4">
            <!-- Meal Image -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Meal Image
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="image_file" class="form-label">Upload Image</label>
                        <input type="file" class="filepond" name="image_file" id="image_file" accept="image/*">
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Upload an appetizing image of this meal</small>
                    </div>
                </div>
            </div>

            <!-- Sort Order -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Display Order
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ $nextSortOrder }}" min="0" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Lower numbers appear first in the meal list</small>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Actions
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-wave waves-effect waves-light">
                            <i class="ri-save-line me-1"></i> Add Meal
                        </button>
                        <button type="button" class="btn btn-success btn-wave waves-effect waves-light" id="saveAndAddAnother">
                            <i class="ri-add-line me-1"></i> Save & Add Another
                        </button>
                        <a href="{{ route('trainer.nutrition-plans.meals.index', $plan->id) }}" class="btn btn-light btn-wave waves-effect waves-light">
                            <i class="ri-close-line me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="ri-lightbulb-line me-1"></i> Quick Tips
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Use clear, descriptive meal titles</li>
                        <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Include accurate portion sizes</li>
                        <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Add preparation and cooking times</li>
                        <li class="mb-2"><i class="ri-check-line text-success me-1"></i> Upload high-quality food images</li>
                        <li class="mb-0"><i class="ri-check-line text-success me-1"></i> Double-check nutritional values</li>
                    </ul>
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
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js')}}"></script>

<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
$(document).ready(function() {
    // Initialize Filepond
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType
    );
    
    const pond = FilePond.create(document.querySelector('#image_file'), {
        acceptedFileTypes: ['image/*'],
        maxFileSize: '2MB',
        labelIdle: 'Drag & Drop your meal image or <span class="filepond--label-action">Browse</span>',
    });
    
    // Auto-calculate total time
    $('#prep_time, #cook_time').on('input', function() {
        var prepTime = parseInt($('#prep_time').val()) || 0;
        var cookTime = parseInt($('#cook_time').val()) || 0;
        var totalTime = prepTime + cookTime;
        
        if (totalTime > 0) {
            $('#totalTimeDisplay').remove();
            $('#cook_time').parent().append('<small id="totalTimeDisplay" class="text-info">Total time: ' + totalTime + ' minutes</small>');
        }
    });
    
    // Form validation
    $('#mealForm').on('submit', function(e) {
        let isValid = true;
        
        // Reset previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Validate required fields
        if (!$('#title').val().trim()) {
            $('#title').addClass('is-invalid');
            $('#title').siblings('.invalid-feedback').text('Meal title is required.');
            isValid = false;
        }
        
        if (!$('#meal_type').val()) {
            $('#meal_type').addClass('is-invalid');
            $('#meal_type').siblings('.invalid-feedback').text('Meal type is required.');
            isValid = false;
        }
        
        if (!$('#servings').val() || $('#servings').val() < 1) {
            $('#servings').addClass('is-invalid');
            $('#servings').siblings('.invalid-feedback').text('Number of servings is required and must be at least 1.');
            isValid = false;
        }
        
        if (!$('#sort_order').val() && $('#sort_order').val() !== '0') {
            $('#sort_order').addClass('is-invalid');
            $('#sort_order').siblings('.invalid-feedback').text('Sort order is required.');
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
    
    // Save and add another meal
    $('#saveAndAddAnother').on('click', function() {
        // Add a hidden input to indicate we want to add another meal
        $('<input>').attr({
            type: 'hidden',
            name: 'add_another',
            value: '1'
        }).appendTo('#mealForm');
        
        $('#mealForm').submit();
    });
});
</script>
@endsection