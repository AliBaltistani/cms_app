@extends('layouts.master')

@section('styles')
<!-- Filepond CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond/filepond.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css')}}">
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Add New Recipe</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.recipes.index', $plan->id)}}">Recipes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Recipe</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.recipes.index', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light me-2">
            <i class="ri-arrow-left-line me-1"></i> Back to Recipes
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
                    <strong>Adding recipe to:</strong> {{ $plan->plan_name }}
                    @if($plan->client)
                        <span class="ms-2">• <strong>Client:</strong> {{ $plan->client->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<form id="recipeForm" action="{{ route('admin.nutrition-plans.recipes.store', $plan->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <!-- Main Recipe Information -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Recipe Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12 mb-3">
                            <label for="title" class="form-label">Recipe Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter recipe title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Enter recipe description">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-12 mb-3">
                            <label for="sort_order" class="form-label">Sort Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" placeholder="Display order" min="0" value="{{ old('sort_order', $nextSortOrder) }}" required>
                            <small class="form-text text-muted">Lower numbers appear first</small>
                            @error('sort_order')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recipe Image -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Recipe Image
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="image_file" class="form-label">Upload Image</label>
                        <input type="file" class="filepond" name="image_file" accept="image/*" data-max-file-size="2MB">
                        <small class="form-text text-muted">Supported formats: JPG, PNG, GIF. Max size: 2MB</small>
                        @error('image_file')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Actions
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="ri-save-line me-1"></i> Save Recipe
                        </button>
                        <button type="submit" name="add_another" value="1" class="btn btn-success btn-wave">
                            <i class="ri-add-line me-1"></i> Save & Add Another
                        </button>
                        <a href="{{ route('admin.nutrition-plans.recipes.index', $plan->id) }}" class="btn btn-secondary btn-wave">
                            <i class="ri-close-line me-1"></i> Cancel
                        </a>
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
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js')}}"></script>

<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
$(document).ready(function() {
    // Register FilePond plugins
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType
    );

    // Initialize FilePond
    const inputElement = document.querySelector('.filepond');
    const pond = FilePond.create(inputElement, {
        labelIdle: 'Drag & Drop your image or <span class="filepond--label-action">Browse</span>',
        imagePreviewHeight: 170,
        imageCropAspectRatio: '1:1',
        imageResizeTargetWidth: 200,
        imageResizeTargetHeight: 200,
        stylePanelLayout: 'compact circle',
        styleLoadIndicatorPosition: 'center bottom',
        styleProgressIndicatorPosition: 'right bottom',
        styleButtonRemoveItemPosition: 'left bottom',
        styleButtonProcessItemPosition: 'right bottom',
    });

    // Form validation
    $('#recipeForm').on('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.text-danger').hide();
        
        // Validate title
        if (!$('#title').val().trim()) {
            $('#title').addClass('is-invalid');
            isValid = false;
        }
        
        // Validate sort order
        if (!$('#sort_order').val() || $('#sort_order').val() < 0) {
            $('#sort_order').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields correctly.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>
@endsection