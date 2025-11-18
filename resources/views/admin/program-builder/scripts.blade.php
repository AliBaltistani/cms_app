{{-- Program Builder JavaScript Functions --}}
<script>
// Global variables
let currentProgramId = '{{ $program->id }}';
let currentWeekId = null;
let currentDayId = null;
let currentCircuitId = null;
let currentExerciseId = null;
let setCounter = 0;

// Initialize on document ready
$(document).ready(function() {
    initializeBuilder();
});

function initializeBuilder() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Set initial set counter
    setCounter = 0;
}

// Toast notification functions
function showSuccess(message) {
    $('#successMessage').text(message);
    $('#successToast').toast('show');
}

function showError(message) {
    $('#errorMessage').text(message);
    $('#errorToast').toast('show');
}

// Clear form validation errors
function clearFormErrors(formId) {
    $(`#${formId} .form-control`).removeClass('is-invalid');
    $(`#${formId} .invalid-feedback`).text('');
}

// Display form validation errors
function displayFormErrors(formId, errors) {
    clearFormErrors(formId);
    
    $.each(errors, function(field, messages) {
        const input = $(`#${formId} [name="${field}"]`);
        input.addClass('is-invalid');
        input.siblings('.invalid-feedback').text(messages[0]);
    });
}

// Week Management Functions
function addWeek() {
    currentWeekId = null;
    $('#weekModalLabel').text('Add Week');
    $('#weekForm')[0].reset();
    clearFormErrors('weekForm');
    
    // Set next week number
    const weekNumbers = $('.week-section').map(function() {
        return parseInt($(this).find('.week-header h5').text().match(/\d+/)[0]);
    }).get();
    const nextWeekNumber = weekNumbers.length > 0 ? Math.max(...weekNumbers) + 1 : 1;
    $('#week_number').val(nextWeekNumber);
    
    $('#weekModal').modal('show');
}

function editWeek(weekId) {
    currentWeekId = weekId;
    $('#weekModalLabel').text('Edit Week');
    clearFormErrors('weekForm');
    
    // Load week data via AJAX
    $.get(`/admin/program-builder/weeks/${weekId}/edit`)
        .done(function(response) {
            $('#week_number').val(response.week.week_number);
            $('#week_title').val(response.week.title);
            $('#week_description').val(response.week.description);
            $('#weekModal').modal('show');
        })
        .fail(function() {
            showError('Failed to load week data');
        });
}

function deleteWeek(weekId) {
    $('#deleteModalLabel').text('Delete Week');
    $('#deleteMessage').text('Are you sure you want to delete this week? This will also delete all days, circuits, and exercises within it.');
    
    $('#confirmDelete').off('click').on('click', function() {
        $.ajax({
            url: `/admin/program-builder/weeks/${weekId}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`.week-section[data-week-id="${weekId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    showSuccess(response.message);
                } else {
                    showError(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                showError('Failed to delete week');
                $('#deleteModal').modal('hide');
            }
        });
    });
    
    $('#deleteModal').modal('show');
}

function duplicateWeek(weekId) {
    currentWeekId = weekId;
    $('#duplicateWeekModalLabel').text('Duplicate Week');
    $('#duplicateWeekForm')[0].reset();
    clearFormErrors('duplicateWeekForm');
    
    // Set next week number
    const weekNumbers = $('.week-section').map(function() {
        return parseInt($(this).find('.week-header h5').text().match(/\d+/)[0]);
    }).get();
    const nextWeekNumber = weekNumbers.length > 0 ? Math.max(...weekNumbers) + 1 : 1;
    $('#duplicate_week_number').val(nextWeekNumber);
    
    // Load original week data to show in placeholders
    $.get(`/admin/program-builder/weeks/${weekId}/edit`)
        .done(function(response) {
            $('#duplicate_week_title').attr('placeholder', `Copy of: ${response.week.title || 'Week ' + response.week.week_number}`);
            $('#duplicate_week_description').attr('placeholder', response.week.description || 'No description');
            $('#duplicateWeekModal').modal('show');
        })
        .fail(function() {
            showError('Failed to load week data');
        });
}

// Day Management Functions
function addDay(weekId) {
    currentWeekId = weekId;
    currentDayId = null;
    $('#dayModalLabel').text('Add Day');
    $('#dayForm')[0].reset();
    clearFormErrors('dayForm');
    
    // Set next day number for this week
    const weekSection = $(`.week-section[data-week-id="${weekId}"]`);
    const dayNumbers = weekSection.find('.day-section').map(function() {
        return parseInt($(this).find('.day-header h6').text().match(/\d+/)[0]);
    }).get();
    const nextDayNumber = dayNumbers.length > 0 ? Math.max(...dayNumbers) + 1 : 1;
    $('#day_number').val(nextDayNumber);
    
    $('#dayModal').modal('show');
}

function editDay(dayId) {
    currentDayId = dayId;
    $('#dayModalLabel').text('Edit Day');
    clearFormErrors('dayForm');
    
    // Load day data via AJAX
    $.get(`/admin/program-builder/days/${dayId}/edit`)
        .done(function(response) {
            $('#day_number').val(response.day.day_number);
            $('#day_title').val(response.day.title);
            $('#day_description').val(response.day.description);
            $('#cool_down').val(response.day.cool_down);
            $('#dayModal').modal('show');
        })
        .fail(function() {
            showError('Failed to load day data');
        });
}

function deleteDay(dayId) {
    $('#deleteModalLabel').text('Delete Day');
    $('#deleteMessage').text('Are you sure you want to delete this day? This will also delete all circuits and exercises within it.');
    
    $('#confirmDelete').off('click').on('click', function() {
        $.ajax({
            url: `/admin/program-builder/days/${dayId}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`.day-section[data-day-id="${dayId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    showSuccess(response.message);
                } else {
                    showError(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                showError('Failed to delete day');
                $('#deleteModal').modal('hide');
            }
        });
    });
    
    $('#deleteModal').modal('show');
}

// Circuit Management Functions
function addCircuit(dayId) {
    currentDayId = dayId;
    currentCircuitId = null;
    $('#circuitModalLabel').text('Add Circuit');
    $('#circuitForm')[0].reset();
    clearFormErrors('circuitForm');
    
    // Set next circuit number for this day
    const daySection = $(`.day-section[data-day-id="${dayId}"]`);
    const circuitNumbers = daySection.find('.circuit-section').map(function() {
        return parseInt($(this).find('.circuit-header strong').text().match(/\d+/)[0]);
    }).get();
    const nextCircuitNumber = circuitNumbers.length > 0 ? Math.max(...circuitNumbers) + 1 : 1;
    $('#circuit_number').val(nextCircuitNumber);
    
    $('#circuitModal').modal('show');
}

function editCircuit(circuitId) {
    currentCircuitId = circuitId;
    $('#circuitModalLabel').text('Edit Circuit');
    clearFormErrors('circuitForm');
    
    // Load circuit data via AJAX
    $.get(`/admin/program-builder/circuits/${circuitId}/edit`)
        .done(function(response) {
            $('#circuit_number').val(response.circuit.circuit_number);
            $('#circuit_title').val(response.circuit.title);
            $('#circuit_description').val(response.circuit.description);
            $('#circuitModal').modal('show');
        })
        .fail(function() {
            showError('Failed to load circuit data');
        });
}

function deleteCircuit(circuitId) {
    $('#deleteModalLabel').text('Delete Circuit');
    $('#deleteMessage').text('Are you sure you want to delete this circuit? This will also delete all exercises within it.');
    
    $('#confirmDelete').off('click').on('click', function() {
        $.ajax({
            url: `/admin/program-builder/circuits/${circuitId}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`.circuit-section[data-circuit-id="${circuitId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    showSuccess(response.message);
                } else {
                    showError(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                showError('Failed to delete circuit');
                $('#deleteModal').modal('hide');
            }
        });
    });
    
    $('#deleteModal').modal('show');
}

// Exercise Management Functions
function addExercise(circuitId) {
    currentCircuitId = circuitId;
    currentExerciseId = null;
    $('#exerciseModalLabel').text('Add Exercise');
    $('#exerciseForm')[0].reset();
    clearFormErrors('exerciseForm');
    
    // Set next exercise order for this circuit
    const circuitSection = $(`.circuit-section[data-circuit-id="${circuitId}"]`);
    const exerciseCount = circuitSection.find('.exercise-item').length;
    $('#exercise_order').val(exerciseCount);
    
    // Clear and add initial set
    $('#sets-container').empty();
    setCounter = 0;
    addSet();
    
    $('#exerciseModal').modal('show');
}

function editExercise(exerciseId) {
    currentExerciseId = exerciseId;
    $('#exerciseModalLabel').text('Edit Exercise');
    clearFormErrors('exerciseForm');
    
    // Load exercise data via AJAX
    $.get(`/admin/program-builder/exercises/${exerciseId}/edit`)
        .done(function(response) {
            const exercise = response.exercise;
            $('#workout_id').val(exercise.workout_id);
            $('#exercise_order').val(exercise.order);
            $('#tempo').val(exercise.tempo);
            $('#rest_interval').val(exercise.rest_interval);
            $('#exercise_notes').val(exercise.notes);
            
            // Load sets
            $('#sets-container').empty();
            setCounter = 0;
            
            if (exercise.exercise_sets && exercise.exercise_sets.length > 0) {
                exercise.exercise_sets.forEach(function(set) {
                    addSet(set.set_number, set.reps, set.weight);
                });
            } else {
                addSet();
            }
            
            $('#exerciseModal').modal('show');
        })
        .fail(function() {
            showError('Failed to load exercise data');
        });
}

function deleteExercise(exerciseId) {
    $('#deleteModalLabel').text('Delete Exercise');
    $('#deleteMessage').text('Are you sure you want to delete this exercise?');
    
    $('#confirmDelete').off('click').on('click', function() {
        $.ajax({
            url: `/admin/program-builder/exercises/${exerciseId}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`.exercise-item[data-exercise-id="${exerciseId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    showSuccess(response.message);
                } else {
                    showError(response.message);
                }
                $('#deleteModal').modal('hide');
            },
            error: function() {
                showError('Failed to delete exercise');
                $('#deleteModal').modal('hide');
            }
        });
    });
    
    $('#deleteModal').modal('show');
}

// Sets Management Functions
function addSet(setNumber = null, reps = null, weight = null) {
    setCounter++;
    const setNum = setNumber || setCounter;
    
    const setHtml = `
        <div class="row mb-3 set-row" data-set-counter="${setCounter}">
            <div class="col-md-3">
                <label class="form-label">Set ${setNum}</label>
                <input type="hidden" name="sets[${setCounter}][set_number]" value="${setNum}">
                <input type="number" class="form-control" name="sets[${setCounter}][reps]" 
                       placeholder="Reps" min="0" value="${reps || ''}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Weight (lbs)</label>
                <input type="number" class="form-control" name="sets[${setCounter}][weight]" 
                       placeholder="Weight" min="0" step="0.5" value="${weight || ''}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeSet(${setCounter})">
                    <i class="ri-delete-bin-line"></i> Remove
                </button>
            </div>
        </div>
    `;
    
    $('#sets-container').append(setHtml);
}

function removeSet(setCounter) {
    $(`.set-row[data-set-counter="${setCounter}"]`).fadeOut(300, function() {
        $(this).remove();
        updateSetNumbers();
    });
}

function updateSetNumbers() {
    $('#sets-container .set-row').each(function(index) {
        const setNumber = index + 1;
        $(this).find('label').first().text(`Set ${setNumber}`);
        $(this).find('input[name*="[set_number]"]').val(setNumber);
    });
}

// Sets Management Modal Functions
function manageSets(exerciseId) {
    currentExerciseId = exerciseId;
    
    // Load exercise sets data
    $.get(`/admin/program-builder/exercises/${exerciseId}/sets`)
        .done(function(response) {
            const exercise = response.exercise;
            $('#setsModalLabel').text(`Manage Sets - ${exercise.workout.name}`);
            
            // Clear and populate sets
            $('#manage-sets-container').empty();
            setCounter = 0;
            
            if (exercise.exercise_sets && exercise.exercise_sets.length > 0) {
                exercise.exercise_sets.forEach(function(set) {
                    addSetToManage(set.set_number, set.reps, set.weight);
                });
            } else {
                addSetToManage();
            }
            
            $('#setsModal').modal('show');
        })
        .fail(function() {
            showError('Failed to load exercise sets');
        });
}

function addSetToManage(setNumber = null, reps = null, weight = null) {
    setCounter++;
    const setNum = setNumber || setCounter;
    
    const setHtml = `
        <div class="row mb-3 set-row" data-set-counter="${setCounter}">
            <div class="col-md-3">
                <label class="form-label">Set ${setNum}</label>
                <input type="hidden" name="sets[${setCounter}][set_number]" value="${setNum}">
                <input type="number" class="form-control" name="sets[${setCounter}][reps]" 
                       placeholder="Reps" min="0" value="${reps || ''}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Weight (lbs)</label>
                <input type="number" class="form-control" name="sets[${setCounter}][weight]" 
                       placeholder="Weight" min="0" step="0.5" value="${weight || ''}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeSetFromManage(${setCounter})">
                    <i class="ri-delete-bin-line"></i> Remove
                </button>
            </div>
        </div>
    `;
    
    $('#manage-sets-container').append(setHtml);
}

function removeSetFromManage(setCounter) {
    $(`.set-row[data-set-counter="${setCounter}"]`).fadeOut(300, function() {
        $(this).remove();
        updateManageSetNumbers();
    });
}

function updateManageSetNumbers() {
    $('#manage-sets-container .set-row').each(function(index) {
        const setNumber = index + 1;
        $(this).find('label').first().text(`Set ${setNumber}`);
        $(this).find('input[name*="[set_number]"]').val(setNumber);
    });
}

// Form Submission Handlers
$('#weekForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = currentWeekId ? 
        `/admin/program-builder/weeks/${currentWeekId}` : 
        `/admin/program-builder/${currentProgramId}/weeks`;
    const method = currentWeekId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#weekModal').modal('hide');
                showSuccess(response.message);
                location.reload(); // Reload to show updated structure
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                displayFormErrors('weekForm', xhr.responseJSON.errors);
            } else {
                showError('An error occurred while saving the week');
            }
        }
    });
});

$('#duplicateWeekForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = `/admin/program-builder/weeks/${currentWeekId}/duplicate`;
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#duplicateWeekModal').modal('hide');
                showSuccess(response.message);
                location.reload(); // Reload to show duplicated week
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                displayFormErrors('duplicateWeekForm', xhr.responseJSON.errors);
            } else {
                showError('An error occurred while duplicating the week');
            }
        }
    });
});

$('#dayForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = currentDayId ? 
        `/admin/program-builder/days/${currentDayId}` : 
        `/admin/program-builder/weeks/${currentWeekId}/days`;
    const method = currentDayId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#dayModal').modal('hide');
                showSuccess(response.message);
                location.reload();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                displayFormErrors('dayForm', xhr.responseJSON.errors);
            } else {
                showError('An error occurred while saving the day');
            }
        }
    });
});

$('#circuitForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = currentCircuitId ? 
        `/admin/program-builder/circuits/${currentCircuitId}` : 
        `/admin/program-builder/days/${currentDayId}/circuits`;
    const method = currentCircuitId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#circuitModal').modal('hide');
                showSuccess(response.message);
                location.reload();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                displayFormErrors('circuitForm', xhr.responseJSON.errors);
            } else {
                showError('An error occurred while saving the circuit');
            }
        }
    });
});

$('#exerciseForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = currentExerciseId ? 
        `/admin/program-builder/exercises/${currentExerciseId}` : 
        `/admin/program-builder/circuits/${currentCircuitId}/exercises`;
    const method = currentExerciseId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#exerciseModal').modal('hide');
                showSuccess(response.message);
                location.reload();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                displayFormErrors('exerciseForm', xhr.responseJSON.errors);
            } else {
                showError('An error occurred while saving the exercise');
            }
        }
    });
});

$('#setsForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: `/admin/program-builder/exercises/${currentExerciseId}/sets`,
        type: 'PUT',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#setsModal').modal('hide');
                showSuccess(response.message);
                location.reload();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                displayFormErrors('setsForm', xhr.responseJSON.errors);
            } else {
                showError('An error occurred while updating the sets');
            }
        }
    });
});
</script>