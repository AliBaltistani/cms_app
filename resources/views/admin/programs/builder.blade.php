@extends('layouts.master')

@section('title', 'Program Builder - ' . $program->name)

@section('styles')
    <style>
        .program-builder {
            background: #f8f9fc;
            border-radius: 0.35rem;
            padding: 1rem;
        }
        
        .week-section {
            border: 2px solid #4e73df;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            background: white;
        }
        
        .week-header {
            background: #4e73df;
            color: white;
            padding: 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .day-section {
            border: 1px solid #1cc88a;
            border-radius: 0.35rem;
            margin: 1rem;
            background: #f8fff8;
        }
        
        .day-header {
            background: #1cc88a;
            color: white;
            padding: 0.75rem;
            border-radius: 0.35rem 0.35rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .circuit-section {
            border: 1px solid #f6c23e;
            border-radius: 0.25rem;
            margin: 0.75rem;
            background: #fffef8;
        }
        
        .circuit-header {
            background: #f6c23e;
            color: #333;
            padding: 0.5rem;
            border-radius: 0.25rem 0.25rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exercise-item {
            padding: 0.75rem;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .exercise-item:last-child {
            border-bottom: none;
        }
        
        .exercise-details {
            flex: 1;
        }
        
        .exercise-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .sets-display {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .set-badge {
            background: #e3e6f0;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        
        .add-button {
            border: 2px dashed #ccc;
            background: transparent;
            padding: 0.75rem;
            border-radius: 0.25rem;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin: 0.5rem 0;
        }
        
        .add-button:hover {
            border-color: #4e73df;
            color: #4e73df;
            background: #f8f9fc;
        }
        

    </style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Program Builder</h1>
        <div>
            <a href="{{ route('programs.show', $program->id) }}" class="btn btn-secondary btn-sm">
                <i class="ri-eye-line"></i> View Program
            </a>
            <a href="{{ route('programs.index') }}" class="btn btn-primary btn-sm">
                <i class="ri-arrow-left-line"></i> Back to Programs
            </a>
        </div>
    </div>

    <!-- Program Info -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ $program->name }}</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Trainer:</strong> {{ $program->trainer->name ?? 'N/A' }}</p>
                    <p><strong>Client:</strong> {{ $program->client->name ?? 'Unassigned' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Duration:</strong> {{ $program->duration }} weeks</p>
                    <p><strong>Status:</strong> 
                        <span class="badge badge-{{ $program->is_active ? 'success' : 'secondary' }}">
                            {{ $program->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </p>
                </div>
            </div>
            @if($program->description)
                <p><strong>Description:</strong> {{ $program->description }}</p>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Program Structure -->
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Program Structure</h6>
                    <button class="btn btn-success btn-sm" onclick="addWeek()">
                        <i class="ri-add-line"></i> Add Week
                    </button>
                </div>
                <div class="card-body">
                    <div class="program-builder" id="program-structure">
                        @forelse($program->weeks as $week)
                            <div class="week-section" data-week-id="{{ $week->id }}">
                                <div class="week-header">
                                    <div>
                                        <h5 class="mb-0">Week {{ $week->week_number }}</h5>
                                        @if($week->title)
                                            <small>{{ $week->title }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        <button class="btn btn-light btn-sm" onclick="addDay('{{ $week->id }}')">
                                            <i class="ri-add-line"></i> Add Day
                                        </button>
                                        <button class="btn btn-outline-light btn-sm" onclick="editWeek('{{ $week->id }}')">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-outline-light btn-sm" onclick="deleteWeek('{{ $week->id }}')">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                @forelse($week->days as $day)
                                    <div class="day-section" data-day-id="{{ $day->id }}">
                                        <div class="day-header">
                                            <div>
                                                <h6 class="mb-0">Day {{ $day->day_number }}</h6>
                                                @if($day->title)
                                                    <small>{{ $day->title }}</small>
                                                @endif
                                            </div>
                                            <div>
                                                <button class="btn btn-light btn-sm" onclick="addCircuit('{{ $day->id }}')">
                                                    <i class="ri-add-line"></i> Add Circuit
                                                </button>
                                                <button class="btn btn-outline-light btn-sm" onclick="editDay('{{ $day->id }}')">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <button class="btn btn-outline-light btn-sm" onclick="deleteDay('{{ $day->id }}')">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        @forelse($day->circuits as $circuit)
                                            <div class="circuit-section" data-circuit-id="{{ $circuit->id }}">
                                                <div class="circuit-header">
                                                    <div>
                                                        <strong>Circuit {{ $circuit->circuit_number }}</strong>
                                                        @if($circuit->title)
                                                            - {{ $circuit->title }}
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-warning btn-sm" onclick="addExercise('{{ $circuit->id }}')">
                                                            <i class="ri-add-line"></i> Add Exercise
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm" onclick="editCircuit('{{ $circuit->id }}')">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm" onclick="deleteCircuit('{{ $circuit->id }}')">
                                                            <i class="ri-delete-bin-line"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                @forelse($circuit->programExercises as $exercise)
                                                    <div class="exercise-item" data-exercise-id="{{ $exercise->id }}">
                                                        <div class="exercise-details">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <strong>{{ $exercise->workout->name ?? 'Unknown Exercise' }}</strong>
                                                                    @if($exercise->tempo)
                                                                        <span class="badge badge-info ml-2">Tempo: {{ $exercise->tempo }}</span>
                                                                    @endif
                                                                    @if($exercise->rest_interval)
                                                                        <span class="badge badge-warning ml-2">Rest: {{ $exercise->rest_interval }}s</span>
                                                                    @endif
                                                                </div>
                                                                <div class="exercise-actions">
                                                                    <button class="btn btn-info btn-sm" onclick="manageSets('{{ $exercise->id }}')">
                                                                        <i class="ri-barbell-line"></i> Sets
                                                                    </button>
                                                                    <button class="btn btn-secondary btn-sm" onclick="editExercise('{{ $exercise->id }}')">
                                                                        <i class="ri-edit-line"></i>
                                                                    </button>
                                                                    <button class="btn btn-danger btn-sm" onclick="deleteExercise('{{ $exercise->id }}')">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            
                                                            @if($exercise->notes)
                                                                <p class="text-muted mb-1 mt-2"><small>{{ $exercise->notes }}</small></p>
                                                            @endif
                                                            
                                                            @if($exercise->exerciseSets->count() > 0)
                                                                <div class="sets-display">
                                                                    @foreach($exercise->exerciseSets as $set)
                                                                        <span class="set-badge">
                                                                            Set {{ $set->set_number }}: {{ $set->reps ?? '?' }} reps
                                                                            @if($set->weight) @ {{ $set->weight }}kg @endif
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-center py-3">
                                                        <button class="add-button" onclick="addExercise('{{ $circuit->id }}')">
                                                            <i class="ri-add-line"></i> Add First Exercise
                                                        </button>
                                                    </div>
                                                @endforelse
                                            </div>
                                        @empty
                                            <div class="text-center py-3">
                                                <button class="add-button" onclick="addCircuit('{{ $day->id }}')">
                                                    <i class="ri-add-line"></i> Add First Circuit
                                                </button>
                                            </div>
                                        @endforelse
                                    </div>
                                @empty
                                    <div class="text-center py-3">
                                        <button class="add-button" onclick="addDay('{{ $week->id }}')">
                                            <i class="ri-add-line"></i> Add First Day
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <h5 class="text-muted">No weeks added yet</h5>
                                <button class="btn btn-primary" onclick="addWeek()">
                                    <i class="ri-add-line"></i> Add First Week
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
@include('admin.program-builder.modals')

@endsection

@section('scripts')
<!-- Include Scripts -->
@include('admin.program-builder.scripts')
@endsection