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

.recipe-card {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    border-left: 4px solid #0d6efd;
}

.recipe-card:hover {
    box-shadow: 0 2px 8px rgba(13,110,253,0.15);
    transform: translateY(-2px);
}

.calculator-card {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    border-left: 4px solid #17a2b8;
}

.calculator-card:hover {
    box-shadow: 0 2px 8px rgba(23,162,184,0.15);
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
        <a href="{{route('admin.nutrition-plans.calculator', $plan->id)}}" class="btn btn-info btn-wave waves-effect waves-light me-2">
            <i class="ri-calculator-line me-1"></i> Calculator
        </a>
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
        
        <!-- Quick Navigation -->
        <div class="card custom-card">
            <div class="card-body py-2">
                <div class="d-flex justify-content-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="scrollToSection('meals')">
                            <i class="ri-restaurant-line me-1"></i> Meals ({{ $plan->meals->count() }})
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="scrollToSection('recipes')">
                            <i class="ri-book-open-line me-1"></i> Recipes ({{ $plan->recipes->count() }})
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="scrollToSection('calculator')">
                            <i class="ri-calculator-line me-1"></i> Calculator
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Meals Section -->
        <div class="card custom-card" id="meals-section">
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
        
        <!-- Recipes Section -->
        <div class="card custom-card" id="recipes-section">
            <div class="card-header">
                <div class="card-title">
                    Recipes ({{ $plan->recipes->count() }})
                </div>
                <div class="ms-auto">
                    <a href="{{ route('admin.nutrition-plans.recipes.index', $plan->id) }}" class="btn btn-sm btn-outline-primary btn-wave me-2">
                        <i class="ri-list-check me-1"></i> Manage Recipes
                    </a>
                    <a href="{{ route('admin.nutrition-plans.recipes.create', $plan->id) }}" class="btn btn-sm btn-primary btn-wave">
                        <i class="ri-add-line me-1"></i> Add Recipe
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($plan->recipes->count() > 0)
                    <div class="row">
                        @foreach($plan->recipes as $recipe)
                             <div class="col-md-6 mb-3">
                                 <div class="recipe-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-semibold mb-0">{{ $recipe->title }}</h6>
                                        <span class="badge bg-info-transparent">Recipe</span>
                                    </div>
                                    @if($recipe->description)
                                        <p class="text-muted small mb-2">{{ Str::limit($recipe->description, 80) }}</p>
                                    @endif
                                    @if($recipe->image_url)
                                        <div class="mb-2">
                                            <img src="{{ $recipe->image_url }}" alt="{{ $recipe->title }}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="small text-muted">
                                            <i class="ri-calendar-line me-1"></i> {{ $recipe->formatted_date }}
                                            <span class="ms-2"><i class="ri-sort-asc me-1"></i> Order: {{ $recipe->sort_order }}</span>
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.nutrition-plans.recipes.show', [$plan->id, $recipe->id]) }}" class="btn btn-sm btn-light">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('admin.nutrition-plans.recipes.edit', [$plan->id, $recipe->id]) }}" class="btn btn-sm btn-success">
                                                <i class="ri-edit-2-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($plan->recipes->count() > 4)
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.nutrition-plans.recipes.index', $plan->id) }}" class="btn btn-outline-primary btn-wave">
                                <i class="ri-arrow-right-line me-1"></i> View All Recipes ({{ $plan->recipes->count() }})
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4">
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
        
        <!-- Calculator Section -->
        <div class="card custom-card" id="calculator-section">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-calculator-line me-2"></i> Nutrition Calculator
                </div>
                <div class="ms-auto">
                    <a href="{{ route('admin.nutrition-plans.calculator', $plan->id) }}" class="btn btn-sm btn-primary btn-wave">
                        <i class="ri-calculator-line me-1"></i> Open Calculator
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($plan->recommendations)
                    <!-- Current Calculations Display -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="ri-information-line me-2 fs-16"></i>
                                    <div>
                                        <strong>Current Nutrition Recommendations</strong>
                                        <small class="d-block text-muted">Based on calculated BMR and TDEE values</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Target Calories Card -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h3 class="text-primary mb-1">{{ number_format($plan->recommendations->target_calories) }}</h3>
                                    <p class="mb-0 text-muted">Daily Target Calories</p>
                                    <small class="text-muted">Based on {{ ucfirst(str_replace('_', ' ', $plan->goal_type ?? 'general')) }} goal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Macros Breakdown -->
                    @php
                        $macroData = [];
                        if($plan->recommendations->macro_distribution) {
                            $macroData = is_string($plan->recommendations->macro_distribution) 
                                ? json_decode($plan->recommendations->macro_distribution, true) 
                                : $plan->recommendations->macro_distribution;
                        } elseif(method_exists($plan->recommendations, 'getCalculatedMacroDistributionAttribute')) {
                            // Fallback to calculated distribution if stored distribution is not available
                            $macroData = $plan->recommendations->calculated_macro_distribution;
                        }
                    @endphp
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h5 class="text-success mb-1">{{ $plan->recommendations->protein }}g</h5>
                                    <small class="text-muted">Protein</small>
                                    @if(!empty($macroData))
                                        <div class="small text-muted mt-1">{{ $macroData['protein_percentage'] ?? 25 }}%</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h5 class="text-warning mb-1">{{ $plan->recommendations->carbs }}g</h5>
                                    <small class="text-muted">Carbs</small>
                                    @if(!empty($macroData))
                                        <div class="small text-muted mt-1">{{ $macroData['carbs_percentage'] ?? 45 }}%</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h5 class="text-danger mb-1">{{ $plan->recommendations->fats }}g</h5>
                                    <small class="text-muted">Fats</small>
                                    @if(!empty($macroData))
                                        <div class="small text-muted mt-1">{{ $macroData['fats_percentage'] ?? 30 }}%</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calculation Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-2">Metabolic Calculations</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small">BMR (Basal Metabolic Rate):</span>
                                        <span class="small fw-semibold">{{ number_format($plan->recommendations->bmr ?? 0) }} cal</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small">TDEE (Total Daily Energy):</span>
                                        <span class="small fw-semibold">{{ number_format($plan->recommendations->tdee ?? 0) }} cal</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small">Activity Level:</span>
                                        <span class="small fw-semibold">{{ ucfirst(str_replace('_', ' ', $plan->recommendations->activity_level ?? 'moderate')) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-2">Goal Adjustment</h6>
                                    @php
                                        $calorieAdjustment = ($plan->recommendations->target_calories ?? 0) - ($plan->recommendations->tdee ?? 0);
                                    @endphp
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small">Calorie Adjustment:</span>
                                        <span class="small fw-semibold {{ $calorieAdjustment > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $calorieAdjustment > 0 ? '+' : '' }}{{ number_format($calorieAdjustment) }} cal
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="small">Goal Type:</span>
                                        <span class="small fw-semibold">{{ ucfirst(str_replace('_', ' ', $plan->goal_type ?? 'general')) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small">Formula Used:</span>
                                        <span class="small fw-semibold">{{ $plan->recommendations->calculation_method ?? 'Mifflin-St Jeor' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('admin.nutrition-plans.calculator', $plan->id) }}" class="btn btn-primary btn-wave me-2">
                                <i class="ri-calculator-line me-1"></i> Recalculate
                            </a>
                            <button type="button" class="btn btn-outline-info btn-wave" onclick="showCalculationDetails()">
                                <i class="ri-information-line me-1"></i> View Details
                            </button>
                        </div>
                    </div>
                @else
                    <!-- No Calculations Available -->
                    <div class="text-center py-4">
                        <i class="ri-calculator-line fs-48 text-muted mb-3"></i>
                        <h5 class="text-muted">No calculations available</h5>
                        <p class="text-muted">Use the nutrition calculator to generate personalized recommendations based on client data.</p>
                        <a href="{{ route('admin.nutrition-plans.calculator', $plan->id) }}" class="btn btn-primary btn-wave">
                            <i class="ri-calculator-line me-1"></i> Calculate Nutrition
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
                            <h4 class="mb-1">{{ $stats['total_recipes'] }}</h4>
                            <small>Total Recipes</small>
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

// Smooth scroll to section function
function scrollToSection(section) {
    const targetId = section + '-section';
    const targetElement = document.getElementById(targetId);
    
    if (targetElement) {
        // Update active button
        $('.btn-group .btn').removeClass('active');
        $(`button[onclick="scrollToSection('${section}')"]`).addClass('active');
        
        // Smooth scroll to target
        targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        // Add a subtle highlight effect
        $(targetElement).addClass('border-primary');
        setTimeout(() => {
            $(targetElement).removeClass('border-primary');
        }, 2000);
    }
}

// Update active navigation based on scroll position
$(window).scroll(function() {
    const scrollPos = $(window).scrollTop() + 100;
    const mealsSection = $('#meals-section').offset();
    const recipesSection = $('#recipes-section').offset();
    const calculatorSection = $('#calculator-section').offset();
    
    if (mealsSection && recipesSection && calculatorSection) {
        if (scrollPos >= calculatorSection.top) {
            $('.btn-group .btn').removeClass('active');
            $(`button[onclick="scrollToSection('calculator')"]`).addClass('active');
        } else if (scrollPos >= recipesSection.top) {
            $('.btn-group .btn').removeClass('active');
            $(`button[onclick="scrollToSection('recipes')"]`).addClass('active');
        } else if (scrollPos >= mealsSection.top) {
            $('.btn-group .btn').removeClass('active');
            $(`button[onclick="scrollToSection('meals')"]`).addClass('active');
        }
    }
});

// Show calculation details function
function showCalculationDetails() {
    @if($plan->recommendations)
        const details = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Metabolic Calculations:</h6>
                    <ul class="list-unstyled">
                        <li><strong>BMR:</strong> {{ number_format($plan->recommendations->bmr ?? 0) }} calories</li>
                        <li><strong>TDEE:</strong> {{ number_format($plan->recommendations->tdee ?? 0) }} calories</li>
                        <li><strong>Activity Level:</strong> {{ ucfirst(str_replace('_', ' ', $plan->recommendations->activity_level ?? 'moderate')) }}</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>Macro Distribution:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Protein:</strong> {{ $plan->recommendations->protein }}g</li>
                        <li><strong>Carbs:</strong> {{ $plan->recommendations->carbs }}g</li>
                        <li><strong>Fats:</strong> {{ $plan->recommendations->fats }}g</li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="text-muted small">
                <strong>Formula:</strong> {{ $plan->recommendations->calculation_method ?? 'Mifflin-St Jeor' }}<br>
                <strong>Goal Type:</strong> {{ ucfirst(str_replace('_', ' ', $plan->goal_type ?? 'general')) }}
            </p>
        `;
        
        // Using SweetAlert if available, otherwise alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Calculation Details',
                html: details,
                width: 600,
                showCloseButton: true,
                showConfirmButton: false
            });
        } else {
            alert('Calculation details are displayed in the section above.');
        }
    @else
        alert('No calculation data available. Please use the calculator to generate recommendations.');
    @endif
}
</script>
@endsection