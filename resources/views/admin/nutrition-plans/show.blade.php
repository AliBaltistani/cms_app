@extends('layouts.master')

@section('styles')
<!-- Custom styles for nutrition plan details -->
<style>
.nutrition-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: #fff;
}

.stat-card {
    text-align: center;
    padding: 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    margin-bottom: 1rem;
}

.meal-card {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.meal-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">{{ $plan->plan_name }}</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $plan->plan_name }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.edit', $plan->id)}}" class="btn btn-success btn-wave waves-effect waves-light me-2">
            <i class="ri-edit-2-line me-1"></i> Edit Plan
        </a>
        <a href="{{route('admin.nutrition-plans.index')}}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line me-1"></i> Back to Plans
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Plan Overview -->
<div class="row">
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Plan Details
                </div>
                <div class="ms-auto">
                    <span class="badge bg-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'inactive' ? 'danger' : 'warning') }}-transparent">
                        {{ ucfirst($plan->status) }}
                    </span>
                    @if($plan->is_global)
                        <span class="badge bg-info-transparent ms-2">Global Plan</span>
                    @endif
                    @if($plan->is_featured)
                        <span class="badge bg-warning-transparent ms-2"><i class="ri-star-fill me-1"></i>Featured</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-semibold mb-2">Description</h6>
                        <p class="text-muted mb-3">{{ $plan->description ?: 'No description provided' }}</p>
                        
                        <h6 class="fw-semibold mb-2">Goal Type</h6>
                        <p class="mb-3">
                            @if($plan->goal_type)
                                <span class="badge bg-primary-transparent">{{ ucfirst(str_replace('_', ' ', $plan->goal_type)) }}</span>
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </p>
                        
                        <h6 class="fw-semibold mb-2">Duration</h6>
                        <p class="mb-3">{{ $plan->duration_text }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold mb-2">Target Weight</h6>
                        <p class="mb-3">{{ $plan->target_weight ? $plan->target_weight . ' kg' : 'Not specified' }}</p>
                        
                        <h6 class="fw-semibold mb-2">Trainer</h6>
                        <p class="mb-3">{{ $plan->trainer ? $plan->trainer->name : 'Admin Created' }}</p>
                        
                        <h6 class="fw-semibold mb-2">Assigned Client</h6>
                        <p class="mb-3">{{ $plan->client ? $plan->client->name : 'Unassigned' }}</p>
                    </div>
                </div>
                
                @if($plan->tags && is_array($plan->tags) && count($plan->tags) > 0)
                    <div class="mt-3">
                        <h6 class="fw-semibold mb-2">Tags</h6>
                        @foreach($plan->tags as $tag)
                            <span class="badge bg-light text-dark me-1">{{ $tag }}</span>
                        @endforeach
                    </div>
                @elseif($plan->tags && is_string($plan->tags))
                    <div class="mt-3">
                        <h6 class="fw-semibold mb-2">Tags</h6>
                        @php
                            $tagsArray = json_decode($plan->tags, true) ?: [];
                        @endphp
                        @if(count($tagsArray) > 0)
                            @foreach($tagsArray as $tag)
                                <span class="badge bg-light text-dark me-1">{{ $tag }}</span>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Meals Section -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Meals ({{ $plan->meals->count() }})
                </div>
                <div class="ms-auto">
                    <a href="{{ route('admin.nutrition-plans.meals.create', $plan->id) }}" class="btn btn-sm btn-primary btn-wave">
                        <i class="ri-add-line me-1"></i> Add Meal
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($plan->meals->count() > 0)
                    <div class="row">
                        @foreach($plan->meals as $meal)
                            <div class="col-md-6 mb-3">
                                <div class="meal-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-semibold mb-0">{{ $meal->title }}</h6>
                                        <span class="badge bg-secondary-transparent">{{ ucfirst(str_replace('_', ' ', $meal->meal_type)) }}</span>
                                    </div>
                                    <p class="text-muted small mb-2">{{ Str::limit($meal->description, 80) }}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            <i class="ri-time-line me-1"></i> {{ $meal->prep_time + $meal->cook_time }} min
                                            <span class="ms-2"><i class="ri-fire-line me-1"></i> {{ $meal->calories_per_serving }} cal</span>
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.nutrition-plans.meals.show', [$plan->id, $meal->id]) }}" class="btn btn-sm btn-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ri-restaurant-line fs-48 text-muted mb-3"></i>
                        <h5 class="text-muted">No meals added yet</h5>
                        <p class="text-muted">Start building this nutrition plan by adding meals.</p>
                        <a href="{{ route('admin.nutrition-plans.meals.create', $plan->id) }}" class="btn btn-primary btn-wave">
                            <i class="ri-add-line me-1"></i> Add First Meal
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Statistics Sidebar -->
    <div class="col-xl-4">
        <!-- Quick Stats -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Plan Statistics
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="stat-card">
                            <h4 class="mb-1">{{ $stats['total_meals'] }}</h4>
                            <small>Total Meals</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <h4 class="mb-1">{{ number_format($stats['total_calories']) }}</h4>
                            <small>Total Calories</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <h4 class="mb-1">{{ number_format($stats['avg_prep_time']) }}</h4>
                            <small>Avg Prep Time</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <h4 class="mb-1">{{ count($stats['meal_types']) }}</h4>
                            <small>Meal Types</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dietary Restrictions -->
        @if($plan->restrictions)
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Dietary Restrictions
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $plan->restrictions->restrictions_summary }}</p>
                    @if($plan->restrictions->notes)
                        <hr>
                        <small class="text-muted">{{ $plan->restrictions->notes }}</small>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- Daily Macros -->
        @if($plan->dailyMacros)
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Daily Macro Targets
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Protein</span>
                            <span class="small fw-semibold">{{ $plan->dailyMacros->protein }}g</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 33%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Carbs</span>
                            <span class="small fw-semibold">{{ $plan->dailyMacros->carbs }}g</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 45%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">Fats</span>
                            <span class="small fw-semibold">{{ $plan->dailyMacros->fats }}g</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 22%"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Total Calories</span>
                        <span class="fw-semibold">{{ number_format($plan->dailyMacros->total_calories) }}</span>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Plan Media -->
        @if($plan->media_url)
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Plan Image
                    </div>
                </div>
                <div class="card-body p-0">
                    <img src="{{ Storage::url($plan->media_url) }}" class="img-fluid rounded" alt="{{ $plan->plan_name }}">
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Any additional JavaScript for the show page
    console.log('Nutrition plan details loaded');
});
</script>
@endsection