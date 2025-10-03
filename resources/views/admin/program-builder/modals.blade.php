{{-- Week Modal --}}
<div class="modal fade" id="weekModal" tabindex="-1" role="dialog" aria-labelledby="weekModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="weekModalLabel">Add Week</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="weekForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="week_number" class="form-label">Week Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="week_number" name="week_number" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="week_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="week_title" name="title" maxlength="255">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="week_description" class="form-label">Description</label>
                        <textarea class="form-control" id="week_description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Week
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Day Modal --}}
<div class="modal fade" id="dayModal" tabindex="-1" role="dialog" aria-labelledby="dayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dayModalLabel">Add Day</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="dayForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="day_number" class="form-label">Day Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="day_number" name="day_number" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="day_title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="day_title" name="title" maxlength="255" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="day_description" class="form-label">Description</label>
                        <textarea class="form-control" id="day_description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="cool_down" class="form-label">Cool Down Instructions</label>
                        <textarea class="form-control" id="cool_down" name="cool_down" rows="2"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Day
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Circuit Modal --}}
<div class="modal fade" id="circuitModal" tabindex="-1" role="dialog" aria-labelledby="circuitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="circuitModalLabel">Add Circuit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="circuitForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="circuit_number" class="form-label">Circuit Number <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="circuit_number" name="circuit_number" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="circuit_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="circuit_title" name="title" maxlength="255">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="circuit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="circuit_description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Circuit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Exercise Modal --}}
<div class="modal fade" id="exerciseModal" tabindex="-1" role="dialog" aria-labelledby="exerciseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exerciseModalLabel">Add Exercise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exerciseForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 d-none">
                            <div class="form-group">
                                <label for="workout_id" class="form-label">Select Workout <span class="text-danger">*</span></label>
                                <select class="form-control" id="workout_id" name="workout_id" required>
                                    <option value="" disabled>Choose a workout...</option>
                                    @foreach($workouts as $index => $workout)
                                        <option value="{{ $workout->id }}" 
                                                data-duration="{{ $workout->duration }}" 
                                                data-difficulty="{{ $workout->difficulty_level }}"
                                                {{ $index === 0 ? 'selected' : '' }}>
                                            {{ $workout->name }} ({{ $workout->duration }}min - {{ ucfirst($workout->difficulty_level) }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6 d-none">
                            <div class="form-group">
                                <label for="exercise_order" class="form-label">Order <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="exercise_order" name="order" min="0" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tempo" class="form-label">Tempo</label>
                                <input type="text" class="form-control" id="tempo" name="tempo" maxlength="50" placeholder="e.g., 3-1-2-1">
                                <small class="form-text text-muted">Format: eccentric-pause-concentric-pause</small>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rest_interval" class="form-label">Rest Interval (seconds)</label>
                                <input type="text" class="form-control" id="rest_interval" name="rest_interval" maxlength="50" placeholder="e.g., 60">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="exercise_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="exercise_notes" name="notes" rows="3" placeholder="Special instructions or modifications..."></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    {{-- Sets Section --}}
                    <div class="form-group">
                        <label class="form-label">Exercise Sets <span class="text-danger">*</span></label>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Sets Configuration</h6>
                                <button type="button" class="btn btn-success btn-sm" onclick="addSet()">
                                    <i class="ri-add-line"></i> Add Set
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="sets-container">
                                    {{-- Sets will be added dynamically --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Exercise
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Sets Management Modal --}}
<div class="modal fade" id="setsModal" tabindex="-1" role="dialog" aria-labelledby="setsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setsModalLabel">Manage Exercise Sets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="setsForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Exercise Sets</h6>
                            <button type="button" class="btn btn-success btn-sm" onclick="addSetToManage()">
                                <i class="ri-add-line"></i> Add Set
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="manage-sets-container">
                                {{-- Sets will be loaded dynamically --}}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line"></i> Update Sets
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Duplicate Week Modal --}}
<div class="modal fade" id="duplicateWeekModal" tabindex="-1" role="dialog" aria-labelledby="duplicateWeekModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateWeekModalLabel">Duplicate Week</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="duplicateWeekForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ri-information-line"></i> This will create a complete copy of the selected week including all days, circuits, exercises, and sets.
                    </div>
                    
                    <div class="form-group">
                        <label for="duplicate_week_number" class="form-label">New Week Number <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duplicate_week_number" name="week_number" min="1" required>
                        <div class="invalid-feedback"></div>
                        <small class="form-text text-muted">The week number for the duplicated week</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="duplicate_week_title" class="form-label">Title (Optional)</label>
                        <input type="text" class="form-control" id="duplicate_week_title" name="title" maxlength="255" placeholder="Leave empty to copy original title">
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="duplicate_week_description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="duplicate_week_description" name="description" rows="3" placeholder="Leave empty to copy original description"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-file-copy-line"></i> Duplicate Week
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Are you sure you want to delete this item?</p>
                <div class="alert alert-warning">
                    <i class="ri-alert-line"></i> This action cannot be undone and will also delete all related items.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="ri-delete-bin-line"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Success/Error Toast --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="ri-check-line"></i> <span id="successMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="ri-error-warning-line"></i> <span id="errorMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>