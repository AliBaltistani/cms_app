@extends('layouts.master')

@section('styles')
<!-- DataTables CSS from CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<style>
.meal-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.meal-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.meal-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.macro-info {
    font-size: 0.85rem;
    color: #6c757d;
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Manage Meals - {{ $plan->plan_name }}</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('trainer.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('trainer.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Meals</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('trainer.nutrition-plans.meals.create', $plan->id)}}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
            <i class="ri-add-line me-1"></i> Add New Meal
        </a>
        <a href="{{route('trainer.nutrition-plans.show', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light">
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

<!-- Meals Management -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">
                    Meals ({{ $plan->meals->count() }})
                </div>
                <div class="d-flex gap-2">
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-filter-3-line me-1"></i> Filter by Type
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="">All Types</a></li>
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="breakfast">Breakfast</a></li>
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="lunch">Lunch</a></li>
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="dinner">Dinner</a></li>
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="snack">Snack</a></li>
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="pre_workout">Pre-Workout</a></li>
                            <li><a class="dropdown-item filter-meal-type" href="#" data-type="post_workout">Post-Workout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($plan->meals->count() > 0)
                    <div class="row" id="mealsContainer">
                        @foreach($plan->meals as $meal)
                            <div class="col-lg-6 col-xl-4 meal-item" data-meal-type="{{ $meal->meal_type }}">
                                <div class="meal-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-semibold mb-0">{{ $meal->title }}</h6>
                                        <span class="badge meal-type-badge bg-{{ $meal->meal_type === 'breakfast' ? 'warning' : ($meal->meal_type === 'lunch' ? 'success' : ($meal->meal_type === 'dinner' ? 'primary' : 'info')) }}-transparent">
                                            {{ ucfirst(str_replace('_', ' ', $meal->meal_type)) }}
                                        </span>
                                    </div>
                                    
                                    @if($meal->description)
                                        <p class="text-muted small mb-2">{{ Str::limit($meal->description, 80) }}</p>
                                    @endif
                                    
                                    @if($meal->image_url)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $meal->image_url) }}" alt="{{ $meal->title }}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                        </div>
                                    @endif
                                    
                                    <div class="macro-info mb-2">
                                        <div class="row text-center">
                                            <div class="col-3">
                                                <small class="d-block fw-semibold">{{ $meal->calories_per_serving ?? 0 }}</small>
                                                <small class="text-muted">Cal</small>
                                            </div>
                                            <div class="col-3">
                                                <small class="d-block fw-semibold">{{ $meal->protein_per_serving ?? 0 }}oz</small>
                                                <small class="text-muted">Protein</small>
                                            </div>
                                            <div class="col-3">
                                                <small class="d-block fw-semibold">{{ $meal->carbs_per_serving ?? 0 }}oz</small>
                                                <small class="text-muted">Carbs</small>
                                            </div>
                                            <div class="col-3">
                                                <small class="d-block fw-semibold">{{ $meal->fats_per_serving ?? 0 }}oz</small>
                                                <small class="text-muted">Fats</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            <i class="ri-time-line me-1"></i> {{ ($meal->prep_time ?? 0) + ($meal->cook_time ?? 0) }} min
                                            <span class="ms-2"><i class="ri-restaurant-line me-1"></i> {{ $meal->servings }} serving{{ $meal->servings > 1 ? 's' : '' }}</span>
                                        </div>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('trainer.nutrition-plans.meals.show', [$plan->id, $meal->id]) }}" class="btn btn-sm btn-info btn-wave" title="View Details">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('trainer.nutrition-plans.meals.edit', [$plan->id, $meal->id]) }}" class="btn btn-sm btn-success btn-wave" title="Edit Meal">
                                                <i class="ri-edit-2-line"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-wave" onclick="deleteMeal('{{ $meal->id }}')" title="Delete Meal">
                                                <i class="ri-delete-bin-5-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="ri-restaurant-line fs-48 text-muted mb-3"></i>
                        <h5 class="text-muted">No meals added yet</h5>
                        <p class="text-muted">Start building this nutrition plan by adding meals.</p>
                        <a href="{{ route('trainer.nutrition-plans.meals.create', $plan->id) }}" class="btn btn-primary btn-wave">
                            <i class="ri-add-line me-1"></i> Add First Meal
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Plan Summary Card -->
@if($plan->meals->count() > 0)
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Plan Summary
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-primary">{{ $plan->meals->count() }}</h4>
                            <small class="text-muted">Total Meals</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-success">{{ number_format($plan->meals->sum('calories_per_serving')) }}</h4>
                            <small class="text-muted">Total Calories</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-warning">{{ number_format($plan->meals->sum('protein_per_serving')) }}oz</h4>
                            <small class="text-muted">Total Protein</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <h4 class="mb-1 text-info">{{ number_format(($plan->meals->avg('prep_time') ?? 0) + ($plan->meals->avg('cook_time') ?? 0)) }}</h4>
                            <small class="text-muted">Avg Prep Time (min)</small>
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
$(document).ready(function() {
    // Filter meals by type
    $('.filter-meal-type').on('click', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        
        if (type === '') {
            $('.meal-item').show();
        } else {
            $('.meal-item').hide();
            $('.meal-item[data-meal-type="' + type + '"]').show();
        }
        
        // Update active filter
        $('.filter-meal-type').removeClass('active');
        $(this).addClass('active');
    });
});

// Delete meal function
function deleteMeal(mealId) {
    Swal.fire({
        title: 'Delete Meal',
        text: 'Are you sure you want to delete this meal? This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/trainer/nutrition-plans/{{ $plan->id }}/meals/' + mealId,
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
                    Swal.fire('Error!', 'Failed to delete meal', 'error');
                }
            });
        }
    });
}
</script>
@endsection