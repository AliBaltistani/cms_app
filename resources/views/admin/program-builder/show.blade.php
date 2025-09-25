@extends('layouts.master')

@section('styles')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        .program-builder {
            background: #f8f9fc;
            min-height: 600px;
        }
        
        .week-section {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            margin-bottom: 1rem;
        }
        
        .week-header {
            background: #4e73df;
            color: white;
            padding: 1rem;
            border-radius: 0.35rem 0.35rem 0 0;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .day-section {
            border: 1px solid #1cc88a;
            border-radius: 0.25rem;
            margin: 1rem;
            background: white;
        }
        
        .day-header {
            background: #1cc88a;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.25rem 0.25rem 0 0;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .circuit-section {
            border: 1px solid #f6c23e;
            border-radius: 0.25rem;
            margin: 0.5rem;
            background: #fffbf0;
        }
        
        .circuit-header {
            background: #f6c23e;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem 0.25rem 0 0;
            display: flex;
            justify-content: between;
            align-items: center;
            font-weight: 600;
        }
        
        .exercise-row {
            background: white;
            border: 1px solid #e3e6f0;
            border-radius: 0.25rem;
            margin: 0.5rem;
            padding: 1rem;
        }
        
        .exercise-table {
            width: 100%;
            margin-top: 1rem;
        }
        
        .exercise-table th {
            background: #f8f9fc;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid #e3e6f0;
        }
        
        .exercise-table td {
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #e3e6f0;
        }
        
        .exercise-table input {
            width: 60px;
            text-align: center;
            border: none;
            background: transparent;
        }
        
        .exercise-table input:focus {
            background: #fff;
            border: 1px solid #4e73df;
            border-radius: 0.25rem;
        }
        
        .exercise-name {
            font-weight: 600;
            color: #5a5c69;
        }
        
        .tempo-rest-notes {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
            align-items: center;
        }
        
        .tempo-rest-notes input, .tempo-rest-notes textarea {
            border: 1px solid #d1d3e2;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .add-btn {
            background: #1cc88a;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
        }
        
        .add-btn:hover {
            background: #17a673;
        }
        
        .remove-btn {
            background: #e74a3b;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
        }
        
        .remove-btn:hover {
            background: #c0392b;
        }
        
        .cool-down-section {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 0.25rem;
            margin: 0.5rem;
            padding: 1rem;
        }
        
        .cool-down-header {
            color: #0c5460;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .inline-edit {
            background: transparent;
            border: none;
            font-weight: inherit;
            color: inherit;
            width: auto;
            min-width: 100px;
        }
        
        .inline-edit:focus {
            background: rgba(255,255,255,0.9);
            border: 1px solid #4e73df;
            border-radius: 0.25rem;
            padding: 0.25rem;
        }
        
        .section-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .drag-handle {
            cursor: move;
            color: #adb5bd;
            margin-right: 0.5rem;
        }
        
        .drag-handle:hover {
            color: #6c757d;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Program Builder</h1>
            <p class="mb-0 text-muted">{{ $program->name }} - Build your workout program structure</p>
        </div>
        <div>
            <button class="btn btn-success me-2" onclick="saveProgram()">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
            <a href="{{ route('programs.show', $program->id) }}" class="btn btn-info me-2">
                <i class="fas fa-eye me-2"></i>Preview Program
            </a>
            <a href="{{ route('programs.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Programs
            </a>
        </div>
    </div>

    <!-- Program Builder Interface -->
    <div class="program-builder">
        <div id="weeks-container">
            @foreach($program->weeks->sortBy('week_number') as $week)
                <div class="week-section" data-week-id="{{ $week->id }}">
                    <div class="week-header">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="fas fa-grip-vertical drag-handle"></i>
                            <span class="me-3">Week {{ $week->week_number }}</span>
                            <input type="text" class="inline-edit" value="{{ $week->title }}" 
                                   placeholder="Week title..." onchange="updateWeek('{{ $week->id }}', 'title', this.value)">
                        </div>
                        <div class="section-actions">
                            <button class="add-btn" onclick="addDay('{{ $week->id }}')">
                                <i class="fas fa-plus me-1"></i>Add Day
                            </button>
                            <button class="remove-btn" onclick="removeWeek('{{ $week->id }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="days-container" data-week-id="{{ $week->id }}">
                        @foreach($week->days->sortBy('day_number') as $day)
                            <div class="day-section" data-day-id="{{ $day->id }}">
                                <div class="day-header">
                                    <div class="d-flex align-items-center flex-grow-1">
                                        <i class="fas fa-grip-vertical drag-handle"></i>
                                        <span class="me-3">Day {{ $day->day_number }}</span>
                                        <input type="text" class="inline-edit" value="{{ $day->title }}" 
                                               placeholder="Day title (e.g., Full Body Push)..." 
                                               onchange="updateDay('{{ $day->id }}', 'title', this.value)">
                                    </div>
                                    <div class="section-actions">
                                        <button class="add-btn" onclick="addCircuit('{{ $day->id }}')">
                                            <i class="fas fa-plus me-1"></i>Add Circuit
                                        </button>
                                        <button class="remove-btn" onclick="removeDay('{{ $day->id }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="circuits-container" data-day-id="{{ $day->id }}">
                                    @foreach($day->circuits->sortBy('circuit_number') as $circuit)
                                        <div class="circuit-section" data-circuit-id="{{ $circuit->id }}">
                                            <div class="circuit-header">
                                                <div class="d-flex align-items-center flex-grow-1">
                                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                                    <span class="me-3">Circuit {{ $circuit->circuit_number }}</span>
                                                    <input type="text" class="inline-edit" value="{{ $circuit->title }}" 
                                                           placeholder="Circuit title..." 
                                                           onchange="updateCircuit('{{ $circuit->id }}', 'title', this.value)">
                                                </div>
                                                <div class="section-actions">
                                                    <button class="add-btn" onclick="addExercise('{{ $circuit->id }}')">
                                                        <i class="fas fa-plus me-1"></i>Add Exercise
                                                    </button>
                                                    <button class="remove-btn" onclick="removeCircuit('{{ $circuit->id }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="exercises-container" data-circuit-id="{{ $circuit->id }}">
                                                @foreach($circuit->programExercises->sortBy('order') as $programExercise)
                                                    <div class="exercise-row" data-exercise-id="{{ $programExercise->id }}">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                                                    <select class="form-select exercise-select me-3" style="width: 300px;" 
                                                                            onchange="updateExercise('{{ $programExercise->id }}', 'workout_id', this.value)">
                                                                        <option value="">Select Exercise</option>
                                                                        @foreach($workouts as $workout)
                                                                            <option value="{{ $workout->id }}" 
                                                                                    {{ $programExercise->workout_id == $workout->id ? 'selected' : '' }}>
                                                                                {{ $workout->name }} ({{ $workout->category }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <button class="remove-btn" onclick="removeExercise('{{ $programExercise->id }}')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                                
                                                                <!-- Sets Table -->
                                                                <table class="exercise-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Exercise</th>
                                                                            <th>Set 1 - rep / w</th>
                                                                            <th>Set 2 - rep / w</th>
                                                                            <th>Set 3 - rep / w</th>
                                                                            <th>Set 4 - reps / w</th>
                                                                            <th>Set 5 - reps / w</th>
                                                                            <th>Notes</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="exercise-name">
                                                                                {{ $programExercise->workout->name ?? 'Select Exercise' }}
                                                                            </td>
                                                                            @for($i = 1; $i <= 5; $i++)
                                                                                @php
                                                                                    $set = $programExercise->exerciseSets->where('set_number', $i)->first();
                                                                                @endphp
                                                                                <td>
                                                                                    <div class="d-flex align-items-center justify-content-center">
                                                                                        <input type="number" 
                                                                                               value="{{ $set->reps ?? '' }}" 
                                                                                               placeholder="reps"
                                                                                               onchange="updateSet('{{ $programExercise->id }}', '{{ $i }}', 'reps', this.value)">
                                                                                        <span class="mx-1">/</span>
                                                                                        <input type="number" 
                                                                                               value="{{ $set->weight ?? '' }}" 
                                                                                               placeholder="weight"
                                                                                               step="0.5"
                                                                                               onchange="updateSet('{{ $programExercise->id }}', '{{ $i }}', 'weight', this.value)">
                                                                                    </div>
                                                                                </td>
                                                                            @endfor
                                                                            <td rowspan="2" style="vertical-align: top; width: 150px;">
                                                                                <textarea rows="3" 
                                                                                          placeholder="Notes..."
                                                                                          style="width: 100%; resize: none;"
                                                                                          onchange="updateExercise('{{ $programExercise->id }}', 'notes', this.value)">{{ $programExercise->notes }}</textarea>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="background: #f8f9fc; font-weight: 600;">
                                                                                Tempo / Rest
                                                                            </td>
                                                                            <td colspan="5">
                                                                                <div class="tempo-rest-notes">
                                                                                    <div>
                                                                                        <label class="form-label mb-1" style="font-size: 0.75rem;">Tempo:</label>
                                                                                        <input type="text" 
                                                                                               value="{{ $programExercise->tempo }}" 
                                                                                               placeholder="e.g., 3-1-2-1"
                                                                                               style="width: 100px;"
                                                                                               onchange="updateExercise('{{ $programExercise->id }}', 'tempo', this.value)">
                                                                                    </div>
                                                                                    <div>
                                                                                        <label class="form-label mb-1" style="font-size: 0.75rem;">Rest Interval:</label>
                                                                                        <input type="text" 
                                                                                               value="{{ $programExercise->rest_interval }}" 
                                                                                               placeholder="e.g., 60-90s"
                                                                                               style="width: 100px;"
                                                                                               onchange="updateExercise('{{ $programExercise->id }}', 'rest_interval', this.value)">
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <!-- Cool Down Section -->
                                <div class="cool-down-section">
                                    <div class="cool-down-header">
                                        <i class="fas fa-snowflake me-2"></i>Cool Down
                                    </div>
                                    <textarea class="form-control" 
                                              rows="2" 
                                              placeholder="Optional cool down instructions..."
                                              onchange="updateDay('{{ $day->id }}', 'cool_down', this.value)">{{ $day->cool_down }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Add Week Button -->
        <div class="text-center mt-4">
            <button class="btn btn-primary btn-lg" onclick="addWeek()">
                <i class="fas fa-plus me-2"></i>Add Week
            </button>
        </div>
    </div>
</div>

<!-- Exercise Selection Modal -->
<div class="modal fade" id="exerciseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Exercise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="exerciseSearch" placeholder="Search exercises...">
                </div>
                <div id="exerciseList" style="max-height: 400px; overflow-y: auto;">
                    @foreach($workouts as $workout)
                        <div class="exercise-item p-3 border-bottom" onclick="selectExercise('{{ $workout->id }}', '{{ $workout->name }}')">
                            <h6 class="mb-1">{{ $workout->name }}</h6>
                            <small class="text-muted">{{ $workout->category }} - {{ $workout->equipment }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Sortable JS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
        let currentExerciseId = null;
        
        $(document).ready(function() {
            // Initialize Select2 for exercise selects
            $('.exercise-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select Exercise',
                allowClear: true
            });
            
            // Initialize sortable for drag and drop
            initializeSortable();
        });

        function initializeSortable() {
            // Make weeks sortable
            new Sortable(document.getElementById('weeks-container'), {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    updateWeekOrder();
                }
            });
            
            // Make days sortable within each week
            document.querySelectorAll('.days-container').forEach(container => {
                new Sortable(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        updateDayOrder(container.dataset.weekId);
                    }
                });
            });
            
            // Make circuits sortable within each day
            document.querySelectorAll('.circuits-container').forEach(container => {
                new Sortable(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        updateCircuitOrder(container.dataset.dayId);
                    }
                });
            });
            
            // Make exercises sortable within each circuit
            document.querySelectorAll('.exercises-container').forEach(container => {
                new Sortable(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        updateExerciseOrder(container.dataset.circuitId);
                    }
                });
            });
        }

        // Week functions
        function addWeek() {
            $.ajax({
                url: `/admin/program-builder/{{ $program->id }}/weeks`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to add week.', 'error');
                }
            });
        }

        function removeWeek(weekId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the week and all its days, circuits, and exercises.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/program-builder/weeks/${weekId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                $(`[data-week-id="${weekId}"]`).remove();
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete week.', 'error');
                        }
                    });
                }
            });
        }

        function updateWeek(weekId, field, value) {
            $.ajax({
                url: `/admin/program-builder/weeks/${weekId}`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    [field]: value
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update week.', 'error');
                }
            });
        }

        // Day functions
        function addDay(weekId) {
            $.ajax({
                url: `/admin/program-builder/weeks/${weekId}/days`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to add day.', 'error');
                }
            });
        }

        function removeDay(dayId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the day and all its circuits and exercises.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/program-builder/days/${dayId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                $(`[data-day-id="${dayId}"]`).remove();
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete day.', 'error');
                        }
                    });
                }
            });
        }

        function updateDay(dayId, field, value) {
            $.ajax({
                url: `/admin/program-builder/days/${dayId}`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    [field]: value
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update day.', 'error');
                }
            });
        }

        // Circuit functions
        function addCircuit(dayId) {
            $.ajax({
                url: `/admin/program-builder/days/${dayId}/circuits`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to add circuit.', 'error');
                }
            });
        }

        function removeCircuit(circuitId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the circuit and all its exercises.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/program-builder/circuits/${circuitId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                $(`[data-circuit-id="${circuitId}"]`).remove();
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete circuit.', 'error');
                        }
                    });
                }
            });
        }

        function updateCircuit(circuitId, field, value) {
            $.ajax({
                url: `/admin/program-builder/circuits/${circuitId}`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    [field]: value
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update circuit.', 'error');
                }
            });
        }

        // Exercise functions
        function addExercise(circuitId) {
            $.ajax({
                url: `/admin/program-builder/circuits/${circuitId}/exercises`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to add exercise.', 'error');
                }
            });
        }

        function removeExercise(exerciseId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will delete the exercise and all its sets.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/program-builder/exercises/${exerciseId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                $(`[data-exercise-id="${exerciseId}"]`).remove();
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete exercise.', 'error');
                        }
                    });
                }
            });
        }

        function updateExercise(exerciseId, field, value) {
            // For workout_id updates, we need to handle this differently
            // since the main updateExercise endpoint requires sets data
            if (field === 'workout_id') {
                $.ajax({
                    url: `/admin/program-builder/exercises/${exerciseId}/workout`,
                    type: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        workout_id: value
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the exercise name in the table
                            const exerciseName = $(`[data-exercise-id="${exerciseId}"] .exercise-select option:selected`).text().split(' (')[0];
                            $(`[data-exercise-id="${exerciseId}"] .exercise-name`).text(exerciseName);
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to update exercise.', 'error');
                    }
                });
            } else {
                // For other fields like tempo, rest_interval, notes
                // Get current sets data first
                const exerciseRow = $(`[data-exercise-id="${exerciseId}"]`);
                const sets = [];
                
                exerciseRow.find('.sets-container .set-row').each(function(index) {
                    const setRow = $(this);
                    sets.push({
                        set_number: index + 1,
                        reps: setRow.find('.reps-input').val() || null,
                        weight: setRow.find('.weight-input').val() || null
                    });
                });
                
                // If no sets found, create a default set
                if (sets.length === 0) {
                    sets.push({
                        set_number: 1,
                        reps: null,
                        weight: null
                    });
                }
                
                const updateData = {
                    sets: sets,
                    [field]: value
                };
                
                $.ajax({
                    url: `/admin/program-builder/exercises/${exerciseId}`,
                    type: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: updateData,
                    success: function(response) {
                        // Handle success if needed
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to update exercise.', 'error');
                    }
                });
            }
        }

        function updateSet(exerciseId, setNumber, field, value) {
            $.ajax({
                url: `/admin/program-builder/exercises/${exerciseId}/sets`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    set_number: setNumber,
                    [field]: value
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to update set.', 'error');
                }
            });
        }

        function saveProgram() {
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we save your changes.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // All changes are saved automatically, so just show success
            setTimeout(() => {
                Swal.fire({
                    title: 'Success!',
                    text: 'All changes have been saved.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 1000);
        }

        // Order update functions
        function updateWeekOrder() {
            const weekIds = Array.from(document.querySelectorAll('[data-week-id]')).map(el => el.dataset.weekId);
            $.ajax({
                url: `/admin/program-builder/{{ $program->id }}/weeks/reorder`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { week_ids: weekIds }
            });
        }

        function updateDayOrder(weekId) {
            const dayIds = Array.from(document.querySelectorAll(`[data-week-id="${weekId}"] [data-day-id]`)).map(el => el.dataset.dayId);
            $.ajax({
                url: `/admin/program-builder/weeks/${weekId}/days/reorder`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { day_ids: dayIds }
            });
        }

        function updateCircuitOrder(dayId) {
            const circuitIds = Array.from(document.querySelectorAll(`[data-day-id="${dayId}"] [data-circuit-id]`)).map(el => el.dataset.circuitId);
            $.ajax({
                url: `/admin/program-builder/days/${dayId}/circuits/reorder`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { circuit_ids: circuitIds }
            });
        }

        function updateExerciseOrder(circuitId) {
            const exerciseIds = Array.from(document.querySelectorAll(`[data-circuit-id="${circuitId}"] [data-exercise-id]`)).map(el => el.dataset.exerciseId);
            $.ajax({
                url: `/admin/program-builder/circuits/${circuitId}/exercises/reorder`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { exercise_ids: exerciseIds }
            });
        }
    </script>
@endsection