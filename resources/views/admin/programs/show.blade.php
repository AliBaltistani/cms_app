@extends('layouts.master')

@section('styles')
    <style>
        .program-structure {
            /* background: #f8f9fc; */
            border-radius: 0.35rem;
            padding: 1rem;
        }
        
        .week-card {
            border-left: 4px solid #4e73df;
            margin-bottom: 1rem;
        }
        
        .day-card {
            border-left: 3px solid #1cc88a;
            margin-left: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .circuit-card {
            border-left: 2px solid #f6c23e;
            margin-left: 2rem;
            margin-bottom: 0.25rem;
        }
        
        .exercise-item {
            margin-left: 3rem;
            padding: 0.5rem;
            /* background: white; */
            border-radius: 0.25rem;
            margin-bottom: 0.25rem;
            /* border: 1px solid #e3e6f0; */
        }
        
        .sets-display {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        
        .set-badge {
            /* background: #e3e6f0; */
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            color: var(--black-6);
        }
        
        .collapse-toggle {
            cursor: pointer;
            user-select: none;
        }
        
        .collapse-toggle:hover {
            background-color: rgba(0,0,0,0.05);
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">{{ $program->name }}</h1>
            <p class="mb-0 text-muted">Program Details & Structure</p>
        </div>
        <div>
            <a href="{{ route('program-builder.show', $program->id) }}" class="btn btn-success me-2">
                <i class="fas fa-hammer me-2"></i>Program Builder
            </a>
            {{-- <a href="{{ route('programs.edit', $program->id) }}" class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i>Edit Program
            </a> --}}
            <a href="{{ route('programs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Programs
            </a>
        </div>
    </div>

    <!-- Program Overview -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-gray-800">Program Name</h6>
                            <p class="mb-3">{{ $program->name }}</p>
                            
                            <h6 class="text-gray-800">Trainer</h6>
                            <p class="mb-3">{{ $program->trainer->name ?? 'N/A' }}</p>
                            
                            <h6 class="text-gray-800">Duration</h6>
                            <p class="mb-3">{{ $program->duration }} weeks</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-gray-800">Client</h6>
                            <p class="mb-3">
                                @if($program->client)
                                    {{ $program->client->name }}
                                    <span class="badge bg-success ms-2">Assigned</span>
                                @else
                                    <span class="text-muted">Template (Not assigned)</span>
                                    <span class="badge bg-secondary ms-2">Template</span>
                                @endif
                            </p>
                            
                            <h6 class="text-gray-800">Created</h6>
                            <p class="mb-3">{{ $program->created_at->format('M d, Y \a\t g:i A') }}</p>
                            
                            <h6 class="text-gray-800">Last Updated</h6>
                            <p class="mb-3">{{ $program->updated_at->format('M d, Y \a\t g:i A') }}</p>
                        </div>
                    </div>
                    
                    @if($program->description)
                        <h6 class="text-gray-800">Description</h6>
                        <p class="mb-0">{{ $program->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Program Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-0">{{ $program->weeks->count() }}</h4>
                                <small class="text-muted">Weeks</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success mb-0">{{ $totalDays }}</h4>
                            <small class="text-muted">Days</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-info mb-0">{{ $totalCircuits }}</h4>
                                <small class="text-muted">Circuits</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning mb-0">{{ $totalExercises }}</h4>
                            <small class="text-muted">Exercises</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('program-builder.show', $program->id) }}" class="btn btn-success">
                            <i class="fas fa-hammer me-2"></i>Open Program Builder
                        </a>
                        <a href="{{ route('program-videos.index', $program->id) }}" class="btn btn-info">
                            <i class="fas fa-video me-2"></i>Manage Videos
                        </a>
                        <!-- @if(!$program->client_id)
                            <button class="btn btn-info" onclick="assignToClient()">
                                <i class="fas fa-user-plus me-2"></i>Assign to Client
                            </button>
                        @endif -->
                        <button class="btn btn-warning" onclick="duplicateProgram()">
                            <i class="fas fa-copy me-2"></i>Duplicate Program
                        </button>
                        <button class="btn btn-danger" onclick="deleteProgram()">
                            <i class="fas fa-trash me-2"></i>Delete Program
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Program Structure -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Program Structure</h6>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="expandAll()">
                    <i class="fas fa-expand-alt me-1"></i>Expand All
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">
                    <i class="fas fa-compress-alt me-1"></i>Collapse All
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($program->weeks->count() > 0)
                <div class="program-structure">
                    @foreach($program->weeks->sortBy('week_number') as $week)
                        <div class="card week-card">
                            <div class="card-header collapse-toggle" data-bs-toggle="collapse" data-bs-target="#week-{{ $week->id }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calendar-week text-primary me-2"></i>
                                        Week {{ $week->week_number }}
                                        @if($week->title)
                                            - {{ $week->title }}
                                        @endif
                                    </h6>
                                    <div>
                                        <span class="badge bg-success me-2">{{ $week->days->count() }} Days</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="collapse show" id="week-{{ $week->id }}">
                                <div class="card-body">
                                    @if($week->description)
                                        <p class="text-muted mb-3">{{ $week->description }}</p>
                                    @endif
                                    
                                    @foreach($week->days->sortBy('day_number') as $day)
                                        <div class="card day-card">
                                            <div class="card-header collapse-toggle" data-bs-toggle="collapse" data-bs-target="#day-{{ $day->id }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-calendar-day text-success me-2"></i>
                                                        Day {{ $day->day_number }}
                                                        @if($day->title)
                                                            - {{ $day->title }}
                                                        @endif
                                                    </h6>
                                                    <div>
                                                        <span class="badge bg-info me-2">{{ $day->circuits->count() }} Circuits</span>
                                                        <i class="fas fa-chevron-down"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="collapse show" id="day-{{ $day->id }}">
                                                <div class="card-body">
                                                    @if($day->description)
                                                        <p class="text-muted mb-3">{{ $day->description }}</p>
                                                    @endif
                                                    
                                                    @foreach($day->circuits->sortBy('circuit_number') as $circuit)
                                                        <div class="card circuit-card">
                                                            <div class="card-header collapse-toggle" data-bs-toggle="collapse" data-bs-target="#circuit-{{ $circuit->id }}">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0">
                                                                        <i class="fas fa-circle text-warning me-2"></i>
                                                                        Circuit {{ $circuit->circuit_number }}
                                                                        @if($circuit->title)
                                                                            - {{ $circuit->title }}
                                                                        @endif
                                                                    </h6>
                                                                    <div>
                                                                        <span class="badge bg-warning me-2">{{ $circuit->programExercises->count() }} Exercises</span>
                                                                        <i class="fas fa-chevron-down"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="collapse show" id="circuit-{{ $circuit->id }}">
                                                                <div class="card-body">
                                                                    @if($circuit->description)
                                                                        <p class="text-muted mb-3">{{ $circuit->description }}</p>
                                                                    @endif
                                                                    
                                                                    @foreach($circuit->programExercises->sortBy('order') as $programExercise)
                                                                        <div class="exercise-item">
                                                                            <div class="d-flex justify-content-between align-items-start">
                                                                                <div class="flex-grow-1">
                                                                                    <h6 class="mb-1">
                                                                                        <i class="fas fa-dumbbell text-danger me-2"></i>
                                                                                        {{ $programExercise->workout->name ?? 'Exercise Not Found' }}
                                                                                    </h6>
                                                                                    @if($programExercise->workout)
                                                                                        <small class="text-muted">{{ $programExercise->workout->category }}</small>
                                                                                    @endif
                                                                                    
                                                                                    @if($programExercise->tempo || $programExercise->rest_interval || $programExercise->notes)
                                                                                        <div class="mt-2">
                                                                                            @if($programExercise->tempo)
                                                                                                <span class="badge bg-light text-dark me-2">Tempo: {{ $programExercise->tempo }}</span>
                                                                                            @endif
                                                                                            @if($programExercise->rest_interval)
                                                                                                <span class="badge bg-light text-dark me-2">Rest: {{ $programExercise->rest_interval }}</span>
                                                                                            @endif
                                                                                        </div>
                                                                                        @if($programExercise->notes)
                                                                                            <div class="mt-2">
                                                                                                <small class="text-muted"><strong>Notes:</strong> {{ $programExercise->notes }}</small>
                                                                                            </div>
                                                                                        @endif
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                            
                                                                            @if($programExercise->exerciseSets->count() > 0)
                                                                                <div class="sets-display">
                                                                                    @foreach($programExercise->exerciseSets->sortBy('set_number') as $set)
                                                                                        <div class="set-badge">
                                                                                            Set {{ $set->set_number }}: {{ $set->reps }} reps
                                                                                            @if($set->weight)
                                                                                                @ {{ $set->formatted_weight }}
                                                                                            @endif
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    
                                                    @if($day->cool_down)
                                                        <div class="mt-3 p-3 bg-light rounded">
                                                            <h6 class="text-info mb-2">
                                                                <i class="fas fa-snowflake me-2"></i>Cool Down
                                                            </h6>
                                                            <p class="mb-0">{{ $day->cool_down }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No Program Structure Yet</h5>
                    <p class="text-muted mb-4">This program doesn't have any weeks, days, or exercises configured yet.</p>
                    <a href="{{ route('program-builder.show', $program->id) }}" class="btn btn-primary">
                        <i class="fas fa-hammer me-2"></i>Start Building Program
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function expandAll() {
            $('.collapse').collapse('show');
        }

        function collapseAll() {
            $('.collapse').collapse('hide');
        }

        function assignToClient() {
            // This would open a modal to select a client
            Swal.fire({
                title: 'Assign to Client',
                text: 'This feature will be implemented to assign this program template to a specific client.',
                icon: 'info'
            });
        }

        function duplicateProgram() {
            Swal.fire({
                title: 'Duplicate Program?',
                text: "This will create a copy of this program with all its structure.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, duplicate it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/programs/{{ $program->id }}/duplicate`,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'View New Program'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = `/admin/programs/${response.program_id}`;
                                    }
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'An error occurred while duplicating the program.', 'error');
                        }
                    });
                }
            });
        }

        function deleteProgram() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! This will delete the entire program structure.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/programs/{{ $program->id }}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success').then(() => {
                                    window.location.href = '{{ route("programs.index") }}';
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'An error occurred while deleting the program.', 'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection