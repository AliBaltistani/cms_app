@extends('layouts.master')

@section('styles')
<style>
.calculator-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: #fff;
}

.result-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.macro-card {
    text-align: center;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

.macro-card.protein {
    border-left: 4px solid #28a745;
}

.macro-card.carbs {
    border-left: 4px solid #ffc107;
}

.macro-card.fats {
    border-left: 4px solid #dc3545;
}

.calculation-step {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-left: 4px solid #007bff;
}

.loading-overlay {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 1000;
    border-radius: 8px;
}

.loading-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Nutrition Calculator - {{ $plan->plan_name }}</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.index')}}">Nutrition Plans</a></li>
                    <li class="breadcrumb-item"><a href="{{route('admin.nutrition-plans.show', $plan->id)}}">{{ $plan->plan_name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Calculator</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{route('admin.nutrition-plans.show', $plan->id)}}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line me-1"></i> Back to Plan
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Plan Info -->
<div class="row mb-4">
    <div class="col-xl-12">
        <div class="alert alert-info" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri-calculator-fill me-2 fs-16"></i>
                <div>
                    <strong>Plan:</strong> {{ $plan->plan_name }}
                    @if($plan->client)
                        <span class="ms-2">• <strong>Client:</strong> {{ $plan->client->name }}</span>
                    @endif
                    @if($plan->goal_type)
                        <span class="ms-2">• <strong>Goal:</strong> {{ ucfirst(str_replace('_', ' ', $plan->goal_type)) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Calculator Form -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-calculator-fill me-2"></i> Nutrition Calculator
                </div>
            </div>
            <div class="card-body position-relative">
                <div class="loading-overlay" id="calculatorLoading">
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Calculating...</span>
                        </div>
                    </div>
                </div>

                <form id="calculatorForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="weight" class="form-label">Weight (lbs) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="weight" name="weight" step="0.1" min="1" max="1100" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="height" class="form-label">Height (cm) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="height" name="height" step="0.1" min="1" max="300" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age (years) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="age" name="age" min="1" max="120" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="activity_level" class="form-label">Activity Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="activity_level" name="activity_level" required>
                                <option value="">Select Activity Level</option>
                                @foreach($activityLevels as $level)
                                    <option value="{{ $level['value'] }}">{{ $level['label'] }} - {{ $level['description'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="goal_type" class="form-label">Goal Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="goal_type" name="goal_type" required>
                                <option value="">Select Goal Type</option>
                                @foreach($goalTypes as $goal)
                                    <option value="{{ $goal['value'] }}" {{ $plan->goal_type === $goal['value'] ? 'selected' : '' }}>
                                        {{ $goal['label'] }} - {{ $goal['description'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-wave">
                            <i class="ri-calculator-fill me-1"></i> Calculate Nutrition
                        </button>
                        <button type="button" class="btn btn-success btn-wave" id="saveRecommendations" style="display: none;">
                            <i class="ri-save-line me-1"></i> Save to Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Recommendations -->
        @if($plan->recommendations)
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-bookmark-line me-2"></i> Current Recommendations
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <h6 class="mb-1 text-primary">{{ number_format($plan->recommendations->target_calories) }}</h6>
                            <small class="text-muted">Calories</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <h6 class="mb-1 text-success">{{ $plan->recommendations->protein }}g</h6>
                            <small class="text-muted">Protein</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <h6 class="mb-1 text-warning">{{ $plan->recommendations->carbs }}g</h6>
                            <small class="text-muted">Carbs</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 mb-2">
                            <h6 class="mb-1 text-danger">{{ $plan->recommendations->fats }}g</h6>
                            <small class="text-muted">Fats</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="small text-muted">
                    <strong>Activity Level:</strong> {{ ucfirst(str_replace('_', ' ', $plan->recommendations->activity_level)) }}<br>
                    <strong>BMR:</strong> {{ number_format($plan->recommendations->bmr) }} calories<br>
                    <strong>TDEE:</strong> {{ number_format($plan->recommendations->tdee) }} calories
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Results -->
    <div class="col-xl-6">
        <div id="calculationResults" style="display: none;">
            <!-- Results will be populated here -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Sweet Alert -->
<script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

<script>
let currentCalculation = null;

$(document).ready(function() {
    // Handle form submission
    $('#calculatorForm').on('submit', function(e) {
        e.preventDefault();
        calculateNutrition();
    });

    // Handle save recommendations
    $('#saveRecommendations').on('click', function() {
        saveRecommendations();
    });
});

function calculateNutrition() {
    const formData = {
        weight: parseFloat($('#weight').val()),
        height: parseFloat($('#height').val()),
        age: parseInt($('#age').val()),
        gender: $('#gender').val(),
        activity_level: $('#activity_level').val(),
        goal_type: $('#goal_type').val()
    };

    // Validate form
    if (!formData.weight || !formData.height || !formData.age || !formData.gender || !formData.activity_level || !formData.goal_type) {
        Swal.fire('Error', 'Please fill in all required fields', 'error');
        return;
    }

    // Show loading
    $('#calculatorLoading').show();

    $.ajax({
        url: '{{ route("admin.nutrition-plans.calculate-nutrition") }}',
        type: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                currentCalculation = response.data;
                displayResults(response.data);
                $('#saveRecommendations').show();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            if (response && response.errors) {
                let errorMessage = 'Validation errors:\n';
                Object.keys(response.errors).forEach(key => {
                    errorMessage += `${key}: ${response.errors[key].join(', ')}\n`;
                });
                Swal.fire('Validation Error', errorMessage, 'error');
            } else {
                Swal.fire('Error', 'Failed to calculate nutrition', 'error');
            }
        },
        complete: function() {
            $('#calculatorLoading').hide();
        }
    });
}

function displayResults(data) {
    const calculations = data.calculations;
    const recommendations = data.recommendations;
    const meta = data.meta;

    const resultsHtml = `
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-pie-chart-line me-2"></i> Calculation Results
                </div>
            </div>
            <div class="card-body">
                <!-- Target Calories -->
                <div class="result-card text-center">
                    <h3 class="mb-1">${Math.round(recommendations.target_calories)}</h3>
                    <p class="mb-0">Daily Calories</p>
                    <small>Based on ${meta.goal_type_display} goal</small>
                </div>

                <!-- Macros -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="macro-card protein">
                            <h5 class="text-success mb-1">${recommendations.protein}g</h5>
                            <small class="text-muted">Protein</small>
                            <div class="small text-muted mt-1">${recommendations.macro_distribution.protein_percentage}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="macro-card carbs">
                            <h5 class="text-warning mb-1">${recommendations.carbs}g</h5>
                            <small class="text-muted">Carbs</small>
                            <div class="small text-muted mt-1">${recommendations.macro_distribution.carbs_percentage}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="macro-card fats">
                            <h5 class="text-danger mb-1">${recommendations.fats}g</h5>
                            <small class="text-muted">Fats</small>
                            <div class="small text-muted mt-1">${recommendations.macro_distribution.fats_percentage}%</div>
                        </div>
                    </div>
                </div>

                <!-- Calculation Details -->
                <div class="mt-3">
                    <h6 class="fw-semibold mb-2">Calculation Details</h6>
                    <div class="calculation-step">
                        <strong>BMR (Basal Metabolic Rate):</strong> ${Math.round(calculations.bmr)} calories
                        <small class="d-block text-muted">Energy needed at rest</small>
                    </div>
                    <div class="calculation-step">
                        <strong>TDEE (Total Daily Energy Expenditure):</strong> ${Math.round(calculations.tdee)} calories
                        <small class="d-block text-muted">BMR × Activity Level (${meta.activity_level_display})</small>
                    </div>
                    <div class="calculation-step">
                        <strong>Calorie Adjustment:</strong> ${calculations.calorie_deficit_surplus > 0 ? '+' : ''}${Math.round(calculations.calorie_deficit_surplus)} calories
                        <small class="d-block text-muted">Based on ${meta.goal_type_display} goal</small>
                    </div>
                    ${calculations.weekly_weight_change_lbs !== 0 ? `
                    <div class="calculation-step">
                        <strong>Estimated Weekly Weight Change:</strong> ${calculations.weekly_weight_change_lbs > 0 ? '+' : ''}${calculations.weekly_weight_change_lbs} lbs
                        <small class="d-block text-muted">Based on calorie deficit/surplus</small>
                    </div>
                    ` : ''}
                </div>

                <!-- Formula Used -->
                <div class="mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <strong>Formula:</strong> ${meta.formula_used}<br>
                        <strong>Calculated:</strong> ${new Date(meta.calculation_date).toLocaleString()}
                    </small>
                </div>
            </div>
        </div>
    `;

    $('#calculationResults').html(resultsHtml).show();
}

function saveRecommendations() {
    if (!currentCalculation) {
        Swal.fire('Error', 'No calculation data to save', 'error');
        return;
    }

    const saveData = {
        target_calories: currentCalculation.recommendations.target_calories,
        protein: currentCalculation.recommendations.protein,
        carbs: currentCalculation.recommendations.carbs,
        fats: currentCalculation.recommendations.fats,
        bmr: currentCalculation.calculations.bmr,
        tdee: currentCalculation.calculations.tdee,
        activity_level: currentCalculation.user_data.activity_level,
        macro_distribution: currentCalculation.recommendations.macro_distribution
    };

    $.ajax({
        url: '{{ route("admin.nutrition-plans.save-calculated-nutrition", $plan->id) }}',
        type: 'POST',
        data: saveData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Failed to save recommendations', 'error');
        }
    });
}
</script>
@endsection