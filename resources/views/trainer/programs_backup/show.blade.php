@extends('layouts.master')

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>{{ $program->name }}</h1>
            <p class="text-muted">
                <i class="fas fa-calendar-alt"></i> {{ $program->duration }} weeks
                @if($program->client)
                    | <i class="fas fa-user"></i> {{ $program->client->name }}
                @else
                    | <span class="badge bg-secondary">Template</span>
                @endif
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('trainer.programs.edit', $program) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Program
            </a>
            <a href="{{ route('trainer.programs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if($program->description)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Description</h5>
                <p class="card-text">{{ $program->description }}</p>
            </div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Weeks</h6>
                    <h3 class="mb-0">{{ $program->duration }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Days Scheduled</h6>
                    <h3 class="mb-0">
                        @php
                            $totalDays = $program->weeks->sum(function ($week) {
                                return $week->days->count();
                            });
                        @endphp
                        {{ $totalDays }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Circuits</h6>
                    <h3 class="mb-0">
                        @php
                            $totalCircuits = 0;
                            foreach ($program->weeks as $week) {
                                foreach ($week->days as $day) {
                                    $totalCircuits += $day->circuits->count();
                                }
                            }
                        @endphp
                        {{ $totalCircuits }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Exercises</h6>
                    <h3 class="mb-0">
                        @php
                            $totalExercises = 0;
                            foreach ($program->weeks as $week) {
                                foreach ($week->days as $day) {
                                    foreach ($day->circuits as $circuit) {
                                        $totalExercises += $circuit->programExercises->count();
                                    }
                                }
                            }
                        @endphp
                        {{ $totalExercises }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Program Structure</h5>
        </div>
        <div class="card-body">
            @forelse($program->weeks as $week)
                <div class="mb-4">
                    <h6 class="fw-bold text-primary">
                        <i class="fas fa-calendar-check"></i> Week {{ $week->week_number }}
                        @if($week->name)
                            - {{ $week->name }}
                        @endif
                    </h6>
                    
                    <div class="ms-3">
                        @forelse($week->days as $day)
                            <div class="mb-3 p-3 border rounded bg-light">
                                <h6 class="mb-2">
                                    <i class="fas fa-dumbbell"></i> {{ $day->name }}
                                </h6>
                                
                                @if($day->description)
                                    <p class="text-muted small mb-2">{{ $day->description }}</p>
                                @endif
                                
                                @if($day->cool_down)
                                    <p class="small mb-2">
                                        <span class="badge bg-info">Cool Down: {{ $day->cool_down }} min</span>
                                    </p>
                                @endif
                                
                                <div class="ms-3">
                                    @forelse($day->circuits as $circuit)
                                        <div class="mb-2 p-2 bg-white border rounded">
                                            <h6 class="mb-1">
                                                <i class="fas fa-ring"></i> Circuit {{ $circuit->circuit_number }}
                                                @if($circuit->title)
                                                    - {{ $circuit->title }}
                                                @endif
                                            </h6>
                                            
                                            <div class="ms-3 small">
                                                @forelse($circuit->programExercises as $programExercise)
                                                    <div class="py-1">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                        <strong>{{ $programExercise->name }}</strong>
                                                        @if($programExercise->workout)
                                                            <span class="text-muted">({{ $programExercise->workout->name }})</span>
                                                        @endif
                                                        @if($programExercise->tempo)
                                                            <span class="text-muted">- Tempo: {{ $programExercise->tempo }}</span>
                                                        @endif
                                                        @if($programExercise->rest_interval)
                                                            <span class="text-muted">- Rest: {{ $programExercise->rest_interval }}s</span>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <p class="text-muted">No exercises</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted small">No circuits scheduled</p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No days scheduled in this week</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="text-center text-muted py-4">No weeks in this program yet</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4 mb-4">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted d-block">
                    <i class="fas fa-calendar"></i> Created: {{ $program->created_at->format('M d, Y') }}
                </small>
                <small class="text-muted d-block">
                    <i class="fas fa-sync"></i> Updated: {{ $program->updated_at->format('M d, Y H:i') }}
                </small>
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('trainer.programs.edit', $program) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i> Edit in Builder
                </a>
                <a href="{{ route('trainer.programs.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Programs
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
