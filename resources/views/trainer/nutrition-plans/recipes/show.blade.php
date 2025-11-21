@extends('layouts.master')

@section('styles')
<style>
.recipe-image {
    max-height: 400px;
    width: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.recipe-info-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.recipe-meta {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $recipe->title }}</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('trainer.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.recipes.index', $plan->id)}}">Recipes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $recipe->title }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('trainer.nutrition-plans.recipes.edit', [$plan->id, $recipe->id])}}" class="btn btn-success btn-wave waves-effect waves-light me-2">
            <i class="ri-edit-2-line me-1"></i> Edit Recipe
        </a>
        <a href="{{route('trainer.nutrition-plans.recipes.index', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light">
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
                    <strong>Recipe from:</strong> {{ $plan->plan_name }}
                    @if($plan->client)
                        <span class="ms-2">• <strong>Client:</strong> {{ $plan->client->name }}</span>
                    @endif
                    @if($plan->trainer)
                        <span class="ms-2">• <strong>Trainer:</strong> {{ $plan->trainer->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recipe Image -->
    @if($recipe->image_url)
    <div class="col-xl-5">
        <div class="card custom-card">
            <div class="card-body p-0">
                <img src="{{ $recipe->image_url }}" alt="{{ $recipe->title }}" class="recipe-image">
            </div>
        </div>
    </div>
    @endif

    <!-- Recipe Details -->
    <div class="col-xl-{{ $recipe->image_url ? '7' : '12' }}">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recipe Details
                </div>
                <div class="ms-auto">
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-more-2-line"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('trainer.nutrition-plans.recipes.edit', [$plan->id, $recipe->id]) }}">
                                <i class="ri-edit-2-line me-2"></i> Edit Recipe
                            </a></li>
                            <li><button class="dropdown-item" onclick="duplicateRecipe()">
                                <i class="ri-file-copy-line me-2"></i> Duplicate Recipe
                            </button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger" onclick="deleteRecipe()">
                                <i class="ri-delete-bin-line me-2"></i> Delete Recipe
                            </button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="recipe-info-card">
                    <h4 class="mb-3">{{ $recipe->title }}</h4>
                    
                    @if($recipe->description)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p class="mb-0">{{ $recipe->description }}</p>
                        </div>
                    @endif
                    
                    <div class="recipe-meta">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-calendar-line me-2 text-primary"></i>
                                    <span class="fw-semibold me-2">Created:</span>
                                    <span>{{ $recipe->formatted_date }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-time-line me-2 text-success"></i>
                                    <span class="fw-semibold me-2">Time:</span>
                                    <span>{{ $recipe->formatted_time }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-sort-asc me-2 text-warning"></i>
                                    <span class="fw-semibold me-2">Sort Order:</span>
                                    <span>{{ $recipe->sort_order }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-image-line me-2 text-info"></i>
                                    <span class="fw-semibold me-2">Has Image:</span>
                                    <span class="badge bg-{{ $recipe->has_image ? 'success' : 'secondary' }}-transparent">
                                        {{ $recipe->has_image ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Plan Context -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Plan Context
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Nutrition Plan</h6>
                        <p class="mb-3">
                            <a href="{{ route('trainer.nutrition-plans.show', $plan->id) }}" class="text-decoration-none">
                                {{ $plan->plan_name }}
                            </a>
                        </p>
                        
                        @if($plan->description)
                            <h6 class="text-muted mb-2">Plan Description</h6>
                            <p class="mb-3">{{ $plan->description }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Plan Statistics</h6>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h6 class="mb-1 text-primary">{{ $plan->recipes->count() }}</h6>
                                    <small class="text-muted">Recipes</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h6 class="mb-1 text-success">{{ $plan->meals->count() }}</h6>
                                    <small class="text-muted">Meals</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2">
                                    <h6 class="mb-1 text-info">{{ ucfirst($plan->status) }}</h6>
                                    <small class="text-muted">Status</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Related Recipes -->
@if($plan->recipes->where('id', '!=', $recipe->id)->count() > 0)
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Other Recipes in this Plan
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($plan->recipes->where('id', '!=', $recipe->id)->take(6) as $relatedRecipe)
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card border">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        @if($relatedRecipe->image_url)
                                            <img src="{{ $relatedRecipe->image_url }}" alt="{{ $relatedRecipe->title }}" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="ri-book-open-line text-muted"></i>
                                            </div>
                                        @endif
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="{{ route('trainer.nutrition-plans.recipes.show', [$plan->id, $relatedRecipe->id]) }}" class="text-decoration-none">
                                                    {{ $relatedRecipe->title }}
                                                </a>
                                            </h6>
                                            <small class="text-muted">{{ $relatedRecipe->short_description }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($plan->recipes->where('id', '!=', $recipe->id)->count() > 6)
                    <div class="text-center">
                        <a href="{{ route('trainer.nutrition-plans.recipes.index', $plan->id) }}" class="btn btn-light btn-sm">
                            View All Recipes ({{ $plan->recipes->count() - 1 }} more)
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
// Duplicate recipe function
function duplicateRecipe() {
    Swal.fire({
        title: 'Duplicate Recipe',
        text: 'This will create a copy of this recipe. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, duplicate it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/trainer/nutrition-plans/{{ $plan->id }}/recipes/{{ $recipe->id }}/duplicate',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Duplicated!', response.message, 'success');
                        window.location.href = '/trainer/nutrition-plans/{{ $plan->id }}/recipes/' + response.recipe.id + '/edit';
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to duplicate recipe', 'error');
                }
            });
        }
    });
}

// Delete recipe function
function deleteRecipe() {
    Swal.fire({
        title: 'Delete Recipe',
        text: 'Are you sure you want to delete this recipe? This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/trainer/nutrition-plans/{{ $plan->id }}/recipes/{{ $recipe->id }}',
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        window.location.href = '/trainer/nutrition-plans/{{ $plan->id }}/recipes';
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to delete recipe', 'error');
                }
            });
        }
    });
}
</script>
@endsection