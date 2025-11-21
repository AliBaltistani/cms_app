@extends('layouts.master')

@section('styles')
<!-- DataTables CSS from CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
.recipe-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.recipe-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.recipe-image {
    height: 150px;
    width: 100%;
    object-fit: cover;
    border-radius: 6px;
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Manage Recipes - {{ $plan->plan_name }}</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Recipes</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.recipes.create', $plan->id)}}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
            <i class="ri-add-line me-1"></i> Add New Recipe
        </a>
        <a href="{{route('admin.nutrition-plans.show', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line me-1"></i> Back to Plan
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Plan Info Card -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">{{ $plan->plan_name }}</h5>
                        <p class="text-muted mb-0">{{ $plan->description ?: 'No description provided' }}</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <span class="badge bg-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'inactive' ? 'danger' : 'warning') }}-transparent">
                                {{ ucfirst($plan->status) }}
                            </span>
                            @if($plan->is_global)
                                <span class="badge bg-info-transparent">Global Plan</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recipes Management -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Recipes ({{ $plan->recipes->count() }})
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-light btn-sm" onclick="toggleView()">
                        <i class="ri-layout-grid-line me-1" id="viewToggleIcon"></i> <span id="viewToggleText">List View</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($plan->recipes->count() > 0)
                    <div class="row" id="recipesContainer">
                        @foreach($plan->recipes as $recipe)
                            <div class="col-lg-6 col-xl-4 recipe-item">
                                <div class="recipe-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-semibold mb-0">{{ $recipe->title }}</h6>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.nutrition-plans.recipes.show', [$plan->id, $recipe->id]) }}" class="btn btn-sm btn-info btn-wave" title="View Details">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('admin.nutrition-plans.recipes.edit', [$plan->id, $recipe->id]) }}" class="btn btn-sm btn-success btn-wave" title="Edit Recipe">
                                                <i class="ri-edit-2-line"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deleteRecipe('{{ $recipe->id }}')" title="Delete Recipe">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    @if($recipe->image_url)
                                        <div class="mb-2">
                                            <img src="{{ $recipe->image_url }}" alt="{{ $recipe->title }}" class="recipe-image">
                                        </div>
                                    @endif
                                    
                                    @if($recipe->description)
                                        <p class="text-muted small mb-2">{{ $recipe->short_description }}</p>
                                    @endif
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            <i class="ri-calendar-line me-1"></i> {{ $recipe->formatted_date }}
                                        </div>
                                        <div class="small text-muted">
                                            Order: {{ $recipe->sort_order }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="ri-book-open-line fs-48 text-muted mb-3"></i>
                        <h5 class="text-muted">No recipes added yet</h5>
                        <p class="text-muted">Start building this nutrition plan by adding recipes.</p>
                        <a href="{{ route('admin.nutrition-plans.recipes.create', $plan->id) }}" class="btn btn-primary btn-wave">
                            <i class="ri-add-line me-1"></i> Add First Recipe
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Plan Summary Card -->
@if($plan->recipes->count() > 0)
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recipe Summary
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-primary">{{ $plan->recipes->count() }}</h4>
                            <small class="text-muted">Total Recipes</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-success">{{ $plan->recipes->where('image_url', '!=', null)->count() }}</h4>
                            <small class="text-muted">With Images</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-info">{{ $plan->recipes->where('description', '!=', null)->count() }}</h4>
                            <small class="text-muted">With Descriptions</small>
                        </div>
                    </div>
                </div>
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
let isGridView = true;

$(document).ready(function() {
    // Initialize view
    updateViewDisplay();
});

// Toggle between grid and list view
function toggleView() {
    isGridView = !isGridView;
    updateViewDisplay();
}

function updateViewDisplay() {
    const container = $('#recipesContainer');
    const icon = $('#viewToggleIcon');
    const text = $('#viewToggleText');
    
    if (isGridView) {
        container.removeClass('list-view').addClass('row');
        $('.recipe-item').removeClass('col-12').addClass('col-lg-6 col-xl-4');
        icon.removeClass('ri-layout-grid-line').addClass('ri-list-check');
        text.text('List View');
    } else {
        container.removeClass('row').addClass('list-view');
        $('.recipe-item').removeClass('col-lg-6 col-xl-4').addClass('col-12');
        icon.removeClass('ri-list-check').addClass('ri-layout-grid-line');
        text.text('Grid View');
    }
}

// Delete recipe function
function deleteRecipe(recipeId) {
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
                url: '/admin/nutrition-plans/{{ $plan->id }}/recipes/' + recipeId,
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
                    Swal.fire('Error!', 'Failed to delete recipe', 'error');
                }
            });
        }
    });
}
</script>
@endsection