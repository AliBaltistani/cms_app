@extends('layouts.master')

@section('styles')
<style>
.meal-image {
    max-height: 300px;
    width: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.nutrition-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
}

.ingredient-item, .instruction-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.ingredient-item:last-child, .instruction-item:last-child {
    border-bottom: none;
}

.macro-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    margin: 0 auto;
    font-weight: bold;
}

.macro-protein { background: linear-gradient(135deg, #ff6b6b, #ee5a52); }
.macro-carbs { background: linear-gradient(135deg, #4ecdc4, #44a08d); }
.macro-fats { background: linear-gradient(135deg, #feca57, #ff9ff3); }
.macro-calories { background: linear-gradient(135deg, #48dbfb, #0abde3); }
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $meal->title }}</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.meals.index', $plan->id)}}">Meals</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $meal->title }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.meals.edit', [$plan->id, $meal->id])}}" class="btn btn-success btn-wave waves-effect waves-light me-2">
            <i class="ri-edit-2-line me-1"></i> Edit Meal
        </a>
        <a href="{{route('admin.nutrition-plans.meals.index', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line me-1"></i> Back to Meals
        </a>
    </div>
</div>
<!-- Page Header Close -->

<div class="row">
    <!-- Main Content -->
    <div class="col-xl-8">
        <!-- Meal Overview -->
        <div class="card custom-card">
            <div class="card-body">
                <div class="row">
                    @if($meal->image_url)
                    <div class="col-md-5">
                        <img src="{{ asset('storage/' . $meal->image_url) }}" alt="{{ $meal->title }}" class="meal-image">
                    </div>
                    <div class="col-md-7">
                    @else
                    <div class="col-md-12">
                    @endif
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="fw-bold mb-2">{{ $meal->title }}</h3>
                                <span class="badge bg-{{ $meal->meal_type === 'breakfast' ? 'warning' : ($meal->meal_type === 'lunch' ? 'success' : ($meal->meal_type === 'dinner' ? 'primary' : 'info')) }}-transparent fs-6">
                                    {{ $meal->meal_type_display }}
                                </span>
                            </div>
                        </div>
                        
                        @if($meal->description)
                            <p class="text-muted mb-3">{{ $meal->description }}</p>
                        @endif
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-time-line text-primary me-2"></i>
                                    <span><strong>Prep Time:</strong> {{ $meal->prep_time_formatted }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-fire-line text-danger me-2"></i>
                                    <span><strong>Cook Time:</strong> {{ $meal->cook_time_formatted }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-restaurant-line text-success me-2"></i>
                                    <span><strong>Servings:</strong> {{ $meal->servings }}</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ri-timer-line text-info me-2"></i>
                                    <span><strong>Total Time:</strong> {{ $meal->total_time }} min</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ingredients -->
        @if($meal->ingredients)
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-list-check me-2"></i> Ingredients
                </div>
            </div>
            <div class="card-body">
                @foreach($meal->ingredients_array as $index => $ingredient)
                    <div class="ingredient-item">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary-transparent me-3">{{ $index + 1 }}</span>
                            <span>{{ $ingredient }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Instructions -->
        @if($meal->instructions)
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-file-list-3-line me-2"></i> Instructions
                </div>
            </div>
            <div class="card-body">
                @foreach($meal->instructions_array as $index => $instruction)
                    <div class="instruction-item">
                        <div class="d-flex align-items-start">
                            <span class="badge bg-success-transparent me-3 mt-1">{{ $index + 1 }}</span>
                            <span>{{ $instruction }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    
    <!-- Sidebar -->
    <div class="col-xl-4">
        <!-- Nutritional Information -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-heart-pulse-line me-2"></i> Nutrition Facts
                    <small class="text-muted ms-2">(Per Serving)</small>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="macro-circle macro-calories text-white">
                            <span class="fs-5 fw-bold">{{ $meal->calories_per_serving ?? 0 }}</span>
                            <small>Calories</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="macro-circle macro-protein text-white">
                            <span class="fs-5 fw-bold">{{ $meal->protein_per_serving ?? 0 }}oz</span>
                            <small>Protein</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="macro-circle macro-carbs text-white">
                            <span class="fs-5 fw-bold">{{ $meal->carbs_per_serving ?? 0 }}oz</span>
                            <small>Carbs</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="macro-circle macro-fats text-white">
                            <span class="fs-5 fw-bold">{{ $meal->fats_per_serving ?? 0 }}oz</span>
                            <small>Fats</small>
                        </div>
                    </div>
                </div>
                
                @if($meal->servings > 1)
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">Total for {{ $meal->servings }} servings:</h6>
                    <div class="row text-center">
                        <div class="col-3">
                            <strong>{{ $meal->total_macros['calories'] }}</strong><br>
                            <small>Calories</small>
                        </div>
                        <div class="col-3">
                            <strong>{{ $meal->total_macros['protein'] }}oz</strong><br>
                            <small>Protein</small>
                        </div>
                        <div class="col-3">
                            <strong>{{ $meal->total_macros['carbs'] }}oz</strong><br>
                            <small>Carbs</small>
                        </div>
                        <div class="col-3">
                            <strong>{{ $meal->total_macros['fats'] }}oz</strong><br>
                            <small>Fats</small>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Plan Information -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-file-list-line me-2"></i> Plan Details
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="fw-semibold mb-1">Nutrition Plan</h6>
                    <a href="{{ route('admin.nutrition-plans.show', $plan->id) }}" class="text-decoration-none">
                        {{ $plan->plan_name }}
                    </a>
                </div>
                
                @if($plan->client)
                <div class="mb-3">
                    <h6 class="fw-semibold mb-1">Assigned Client</h6>
                    <span class="text-muted">{{ $plan->client->name }}</span>
                </div>
                @endif
                
                @if($plan->trainer)
                <div class="mb-3">
                    <h6 class="fw-semibold mb-1">Created by Trainer</h6>
                    <span class="text-muted">{{ $plan->trainer->name }}</span>
                </div>
                @endif
                
                <div class="mb-3">
                    <h6 class="fw-semibold mb-1">Plan Status</h6>
                    <span class="badge bg-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'inactive' ? 'danger' : 'warning') }}-transparent">
                        {{ ucfirst($plan->status) }}
                    </span>
                </div>
                
                <div class="mb-0">
                    <h6 class="fw-semibold mb-1">Sort Order</h6>
                    <span class="text-muted">{{ $meal->sort_order }}</span>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-settings-3-line me-2"></i> Actions
                </div>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.nutrition-plans.meals.edit', [$plan->id, $meal->id]) }}" class="btn btn-success btn-wave waves-effect waves-light">
                        <i class="ri-edit-2-line me-1"></i> Edit Meal
                    </a>
                    <button type="button" class="btn btn-warning btn-wave waves-effect waves-light" onclick="duplicateMeal()">
                        <i class="ri-file-copy-line me-1"></i> Duplicate Meal
                    </button>
                    <button type="button" class="btn btn-danger btn-wave waves-effect waves-light" onclick="deleteMeal()">
                        <i class="ri-delete-bin-5-line me-1"></i> Delete Meal
                    </button>
                    @if($meal->image_url)
                    <button type="button" class="btn btn-light btn-wave waves-effect waves-light" onclick="deleteImage()">
                        <i class="ri-image-line me-1"></i> Remove Image
                    </button>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Meal Statistics -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bar-chart-line me-2"></i> Statistics
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <div class="border rounded p-2">
                            <h6 class="mb-0">{{ $meal->created_at->format('M d, Y') }}</h6>
                            <small class="text-muted">Created</small>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="border rounded p-2">
                            <h6 class="mb-0">{{ $meal->updated_at->format('M d, Y') }}</h6>
                            <small class="text-muted">Updated</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
// Delete meal function
function deleteMeal() {
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
                url: '/admin/nutrition-plans/{{ $plan->id }}/meals/{{ $meal->id }}',
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        window.location.href = '/admin/nutrition-plans/{{ $plan->id }}/meals';
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

// Delete image function
function deleteImage() {
    Swal.fire({
        title: 'Remove Image',
        text: 'Are you sure you want to remove this meal image?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/nutrition-plans/{{ $plan->id }}/meals/{{ $meal->id }}/delete-image',
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Removed!', response.message, 'success');
                        location.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to remove image', 'error');
                }
            });
        }
    });
}

// Duplicate meal function
function duplicateMeal() {
    Swal.fire({
        title: 'Duplicate Meal',
        text: 'This will create a copy of this meal in the same nutrition plan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, duplicate it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // For now, redirect to create page with meal data
            // In a full implementation, you'd create a duplicate endpoint
            window.location.href = '/admin/nutrition-plans/{{ $plan->id }}/meals/create?duplicate={{ $meal->id }}';
        }
    });
}
</script>
@endsection