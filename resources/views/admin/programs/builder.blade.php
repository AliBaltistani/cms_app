@extends('layouts.master')

@section('content')
<div class="container-fluid" >
    <div class="card shadow-lg border-0 mb-4" style="background: #ffffff; border: 2px solid rgba(255, 106, 0, 0.2) !important;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold" style="color: #000000;">
                        <i class="bi bi-calendar-week me-2" style="color: rgb(255, 106, 0);"></i>Workout Program Builder
                    </h2>
                    <p class="text-muted mb-0">Design your perfect training program</p>
                </div>
                <button class="btn btn-lg shadow" style="background-color: rgb(255, 106, 0) !important; border-color: rgba(255, 106, 0, 0.894) !important; color: white;" onclick="addWeek()">
                    <i class="bi bi-plus-circle me-2"></i>Add Week
                </button>
            </div>
        </div>
    </div>

    <div id="weeksContainer">
        <!-- Weeks will be added here dynamically -->
    </div>
</div>

<!-- Column Settings Modal -->
<div class="modal fade" id="columnSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: rgb(255, 106, 0) !important;">
                <h5 class="modal-title">
                    <i class="bi bi-sliders me-2"></i>Configure Columns
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert" style="background-color: rgba(255, 106, 0, 0.1); border-color: rgba(255, 106, 0, 0.3); color: #000;">
                    <i class="bi bi-info-circle me-2" style="color: rgb(255, 106, 0);"></i>
                    Add, remove, or rename columns for your workout table
                </div>
                <div id="columnList"></div>
                <button class="btn btn-outline mt-3" style="border-color: rgb(255, 106, 0); color: rgb(255, 106, 0);" onclick="addColumn()">
                    <i class="bi bi-plus-circle me-2"></i>Add Column
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn" style="background-color: rgb(255, 106, 0) !important; border-color: rgba(255, 106, 0, 0.894) !important; color: white;" onclick="applyColumnSettings()">
                    <i class="bi bi-check-circle me-2"></i>Apply Changes
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .week-container {
        margin-bottom: 2rem;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        transition: all 0.3s ease;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .week-container:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }

    .week-header {
        color: white;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid rgb(255, 106, 0);
    }

    .week-header h4 {
        margin: 0;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .day-container {
        border-bottom: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .day-container:last-child {
        border-bottom: none;
    }

    .day-header {
        /* background: linear-gradient(135deg, rgb(255, 106, 0) 0%, rgba(255, 106, 0, 0.894) 100%); */
        /* color: white; */
        background: gainsboro;
        color: black;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        border-top: 1px solid #dee2e6;
    }

    .day-name-input {
        background: rgba(255, 255, 255, 0.2);
        /* border: 2px solid rgba(255, 255, 255, 0.3); */
         border: 1px solid rgba(255, 106, 0, 0.3);
        color: black;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .day-name-input:focus {
        background: rgba(255, 255, 255, 0.3);
        border-color: black;
        outline: none;
    }

    /* .day-name-input::placeholder {
        color: rgba(255, 255, 255, 0.7);
    } */

    /* tempo-info and tempo-input removed */

    .exercise-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .exercise-table thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 0.4rem;
        font-weight: 700;
        border: 1px solid #dee2e6;
        text-align: center;
        font-size: 0.9rem;
        color: #495057;
        position: relative;
    }

    .column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        min-width: 105px;
    }

    .column-remove-btn {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .exercise-table thead th:hover .column-remove-btn {
        opacity: 1;
    }

    .exercise-table tbody tr {
        transition: all 0.3s ease;
    }

    .exercise-table tbody tr:hover {
        background: rgba(255, 106, 0, 0.05);
    }

    .exercise-table td {
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .exercise-table input,
    .exercise-table select,
    .exercise-table textarea {
        border: 2px solid transparent;
        background: transparent;
        width: 100%;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .exercise-table input:focus,
    .exercise-table select:focus,
    .exercise-table textarea:focus {
        outline: none;
        border-color: rgb(255, 106, 0);
        background: rgba(255, 106, 0, 0.05);
    }

    .circuit-label {
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        font-weight: 700;
        color: #000000;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 0.85rem;
        border-left: 4px solid rgb(255, 106, 0);
    }

    .cool-down {
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        font-style: italic;
        text-align: center;
        padding: 1rem;
        font-weight: 500;
        color: #333;
        border-left: 4px solid rgba(255, 106, 0, 0.5);
    }

    .action-cell {
        background: #fafafa;
        text-align: center;
        width: 80px;
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, rgb(255, 106, 0) 0%, rgba(255, 106, 0, 0.894) 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 106, 0, 0.4);
        color: white;
        background: linear-gradient(135deg, rgba(255, 106, 0, 0.9) 0%, rgba(255, 106, 0, 0.8) 100%);
    }

    .btn-gradient-danger {
        background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
        border: 1px solid rgba(255, 106, 0, 0.3);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-gradient-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        color: white;
        border-color: rgb(255, 106, 0);
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }

    .action-buttons {
        padding: 1.5rem;
        background: #f8f9fa;
        border-top: 2px solid #e9ecef;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .card {
        border-radius: 16px;
    }

    .badge-custom {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        background-color: rgb(255, 106, 0);
        color: white;
    }

    .column-config-item {
        background: #ffffff;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
    }

    .column-config-item:hover {
        border-color: rgb(255, 106, 0);
        box-shadow: 0 4px 12px rgba(255, 106, 0, 0.15);
    }

    .drag-handle {
        cursor: move;
        color: #999;
    }

    .drag-handle:hover {
        color: rgb(255, 106, 0);
    }
</style>
@endsection

@section('scripts')
<script>
    // =====================================================================
    // Backend Integration Context
    // - Program ID and available workouts injected from server
    // - Base URL for Program Builder routes
    // - CSRF token helper for AJAX requests
    // =====================================================================
    const PROGRAM_ID = '{{ $program->id }}';
    const BASE_PROGRAM_BUILDER_URL = "{{ url('/admin/program-builder') }}";
    const WORKOUTS = @json($workouts);
    // Suppress backend persistence during initial render to avoid duplicates
    let SUPPRESS_PERSIST = false;

    function getCsrfToken() {
        const el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    async function ajax(url, options = {}) {
        const defaults = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            credentials: 'same-origin'
        };
        const opts = Object.assign({}, defaults, options);
        const res = await fetch(url, opts);
        const isJson = res.headers.get('content-type')?.includes('application/json');
        const body = isJson ? await res.json() : await res.text();
        if (!res.ok) {
            throw { status: res.status, body };
        }
        return body;
    }

    // =====================================================================
    // Notification Helpers (Bootstrap-friendly without altering markup)
    // =====================================================================
    function showNotification(type, message) {
        // type: 'success' | 'error' | 'info'
        const color = type === 'success' ? '#198754' : (type === 'error' ? '#dc3545' : '#0d6efd');
        const alert = document.createElement('div');
        alert.className = 'alert shadow-sm';
        alert.style.border = `1px solid ${color}`;
        alert.style.color = '#000';
        alert.style.backgroundColor = 'rgba(0,0,0,0.03)';
        alert.innerHTML = `<i class="bi ${type === 'success' ? 'bi-check-circle' : (type === 'error' ? 'bi-exclamation-triangle' : 'bi-info-circle')} me-2" style="color:${color}"></i>${message}`;
        const headerCard = document.querySelector('.card-body');
        if (headerCard) {
            headerCard.insertAdjacentElement('afterend', alert);
            setTimeout(() => alert.remove(), 4000);
        }
    }

    function showAjaxError(err, fallbackMsg = 'Operation failed') {
        try {
            if (err && typeof err.body === 'object') {
                const b = err.body;
                // Laravel validation error bag
                if (b && b.errors && typeof b.errors === 'object') {
                    const messages = Object.values(b.errors).flat().filter(Boolean);
                    if (messages.length) {
                        showNotification('error', messages.join(' | '));
                        return;
                    }
                }
                if (b.message) {
                    showNotification('error', String(b.message));
                    return;
                }
            } else if (typeof err.body === 'string') {
                showNotification('error', err.body);
                return;
            }
        } catch (_) {}
        showNotification('error', fallbackMsg);
    }

    // =====================================================================
    // Tooltip & Column Config Persistence Helpers
    // =====================================================================
    function initTooltips(context = document) {
        try {
            const tooltipEls = [].slice.call(context.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipEls.forEach(el => {
                // Reuse existing instance if present
                const existing = bootstrap.Tooltip.getInstance(el);
                if (existing) { existing.dispose(); }
                new bootstrap.Tooltip(el);
            });
        } catch (e) {
            // No-op: bootstrap may not be available in test contexts
        }
    }

    // ColumnConfig persistence moved to server (DB) per program (no localStorage fallback)

    let saveColumnTimer = null;
    function saveColumnConfigRemote() {
        if (saveColumnTimer) { clearTimeout(saveColumnTimer); }
        // debounce to reduce chatter when dragging/updating repeatedly
        saveColumnTimer = setTimeout(() => {
            ajax(`${BASE_PROGRAM_BUILDER_URL}/${PROGRAM_ID}/columns`, {
                method: 'PUT',
                body: JSON.stringify({ columns: columnConfig })
            }).then(() => {
                showNotification('success', 'Column settings saved');
            }).catch((err) => {
                showAjaxError(err, 'Failed to save column settings');
            });
        }, 400);
    }

    async function loadColumnConfigRemote() {
        try {
            const resp = await ajax(`${BASE_PROGRAM_BUILDER_URL}/${PROGRAM_ID}/columns`, {
                method: 'GET'
            });
            if (resp && Array.isArray(resp.columns)) {
                columnConfig = resp.columns;
            }
        } catch (e) {
            // If server load fails, fall back to the default in-memory columnConfig
        }
    }

    // =====================================================================
    // Workout Name → ID Resolver
    // =====================================================================
    function resolveWorkoutIdByName(name) {
        if (!name) { return null; }
        const n = String(name).trim().toLowerCase();
        const match = WORKOUTS.find(w => String(w.name || w.title || '').trim().toLowerCase() === n);
        return match ? match.id : null;
    }

    let weekCounter = 0;
    let dayCounter = {};
    let exerciseCounter = {};
    let currentDayId = null;

    // Default column configuration
    const DEFAULT_COLUMNS = [{
            id: 'exercise',
            name: 'Exercise',
            width: '25%',
            type: 'text',
            required: true
        },
        {
            id: 'set1',
            name: 'Set 1 - rep / w',
            width: '12%',
            type: 'text',
            required: false
        },
        {
            id: 'set2',
            name: 'Set 2 - rep / w',
            width: '12%',
            type: 'text',
            required: false
        },
        {
            id: 'set3',
            name: 'Set 3 - rep / w',
            width: '12%',
            type: 'text',
            required: false
        },
        {
            id: 'set4',
            name: 'Set 4 - reps / w',
            width: '12%',
            type: 'text',
            required: false
        },
        {
            id: 'set5',
            name: 'Set 5 - reps / w',
            width: '12%',
            type: 'text',
            required: false
        },
        {
            id: 'notes',
            name: 'Notes',
            width: '15%',
            type: 'text',
            required: false
        }
    ];
    let columnConfig = JSON.parse(JSON.stringify(DEFAULT_COLUMNS));

    // =====================================================================
    // Render & CRUD: Week
    // =====================================================================
    function addWeek() {
        weekCounter++;
        dayCounter[weekCounter] = 0;

        const weekHtml = `
        <div class="week-container" id="week-${weekCounter}">
            <div class="week-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="m-0 d-flex align-items-center">
                        <i class="bi bi-calendar3 me-2"></i>Week ${weekCounter}
                    </h4>
                    <input type="text" class="form-control week-title-input"
                           id="week-${weekCounter}-title"
                           placeholder="Week title (autosaves)"
                           style="width: 250px; border: 1.5px solid rgb(255, 106, 0); background: #fff;"
                           data-bs-toggle="tooltip" title="Week title autosaves to backend" />
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-light btn-sm shadow-sm" onclick="addDay(${weekCounter})" 
                            data-bs-toggle="tooltip" title="Add Day">
                        <i class="bi bi-plus-circle me-1"></i>Add Day
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="duplicateWeek(${weekCounter})"
                            data-bs-toggle="tooltip" title="Duplicate Week">
                        <i class="bi bi-files"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="removeWeek(${weekCounter})"
                            data-bs-toggle="tooltip" title="Remove Week">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#week-${weekCounter}-collapse" aria-expanded="true" aria-controls="week-${weekCounter}-collapse" title="Toggle Week">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div id="week-${weekCounter}-collapse" class="collapse show">
                <div class="mt-2" style="max-width: 500px; display:none;">
                    <textarea class="form-control form-control-sm mt-2 week-description-input" 
                              id="week-${weekCounter}-description" placeholder="Week description (autosaves)" style="display:none;"></textarea>
                </div>
                <div id="week-${weekCounter}-days"></div>
            </div>
        </div>
    `;

        document.getElementById('weeksContainer').insertAdjacentHTML('beforeend', weekHtml);

        // Persist the new week in backend
        const payload = {
            week_number: weekCounter,
            title: `Week ${weekCounter}`
        };
        if (SUPPRESS_PERSIST) {
            // Skip initial POST during renderProgram; ID will be linked later
            return;
        }
        ajax(`${BASE_PROGRAM_BUILDER_URL}/${PROGRAM_ID}/weeks`, {
            method: 'POST',
            body: JSON.stringify(payload)
        }).then(resp => {
            const container = document.getElementById(`week-${weekCounter}`);
            if (resp?.week?.id && container) {
                container.dataset.weekId = String(resp.week.id);
                // Bind autosave for week title/description now that backend ID exists
                bindWeekAutosave(weekCounter);
                showNotification('success', 'Week added');
            }
        }).catch(err => {
            showAjaxError(err, 'Failed to add week');
        });
    }

    function removeWeek(weekId) {
        if (confirm('Are you sure you want to remove this week?')) {
            const el = document.getElementById(`week-${weekId}`);
            const backendId = el?.dataset?.weekId;
            if (backendId) {
                ajax(`${BASE_PROGRAM_BUILDER_URL}/weeks/${backendId}`, { method: 'DELETE' })
                .then(() => {
                    el.remove();
                    showNotification('success', 'Week removed');
                    reindexWeeksAndDays();
                }).catch(err => {
                    showAjaxError(err, 'Failed to remove week');
                });
            } else {
                el.remove();
                reindexWeeksAndDays();
            }
        }
    }

    function duplicateWeek(weekId) {
        const weekElement = document.getElementById(`week-${weekId}`);
        const sourceBackendId = weekElement?.dataset?.weekId;
        const newNumber = weekCounter + 1;
        if (!sourceBackendId) {
            showNotification('error', 'Cannot duplicate: Week not persisted yet');
            return;
        }
        ajax(`${BASE_PROGRAM_BUILDER_URL}/weeks/${sourceBackendId}/duplicate`, {
            method: 'POST',
            body: JSON.stringify({ week_number: newNumber })
        }).then(resp => {
            // Render a fresh week container for duplicated week without triggering an extra POST
            SUPPRESS_PERSIST = true;
            addWeek();
            SUPPRESS_PERSIST = false;
            const newEl = document.getElementById(`week-${weekCounter}`);
            if (resp?.week && newEl) {
                // Populate week container and render nested days/circuits/exercises
                renderWeekIntoExisting(resp.week, weekCounter);
                // Accordion behavior: collapse others, expand this one
                collapseAllWeeksExcept(weekCounter);
                const lastDayId = `week-${weekCounter}-day-${dayCounter[weekCounter] || 1}`;
                collapseAllDaysExcept(weekCounter, lastDayId);
                collapseAllCircuitsExcept(lastDayId);
                showNotification('success', 'Week duplicated');
            }
        }).catch(err => {
            showAjaxError(err, 'Failed to duplicate week');
        });
    }

    // =====================================================================
    // Render & CRUD: Day
    // =====================================================================
    function addDay(weekId) {
        dayCounter[weekId]++;
        const dayId = `week-${weekId}-day-${dayCounter[weekId]}`;
        exerciseCounter[dayId] = 0;

        const dayHtml = `
        <div class="day-container" id="${dayId}">
            <div class="day-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-white fs-6 px-3 py-2" style="color: rgb(255, 106, 0);">Day ${dayCounter[weekId]}</span>
                    <input type="text" class="form-control day-name-input" 
                           placeholder="e.g., Full Body Pump" 
                           id="${dayId}-name"
                           style="width: 250px;"
                           data-bs-toggle="tooltip" title="Day title autosaves to backend">
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-light btn-sm" onclick="openColumnSettings('${dayId}')"
                            data-bs-toggle="tooltip" title="Configure Columns">
                        <i class="bi bi-sliders" style="color: rgb(255, 106, 0);"></i>
                    </button>
                    <button class="btn btn-light btn-sm" onclick="duplicateDay('${dayId}', ${weekId})"
                            data-bs-toggle="tooltip" title="Duplicate Day">
                        <i class="bi bi-files"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="removeDay('${dayId}')"
                            data-bs-toggle="tooltip" title="Remove Day">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#${dayId}-collapse" aria-expanded="true" aria-controls="${dayId}-collapse" title="Toggle Day">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div id="${dayId}-collapse" class="collapse show">
                <div class="table-responsive">
                    <table class="exercise-table" id="${dayId}-table">
                        <thead>
                            <tr>
                                <th style="width: 5%">
                                 <button class="btn btn-light btn-sm" onclick="openColumnSettings('${dayId}')"
                                data-bs-toggle="tooltip" title="Configure Columns">
                                    <i class="bi bi-gear-fill"></i>
                                </button>
                                </th>
                                ${generateColumnHeaders()}
                            </tr>
                        </thead>
                        <tbody id="${dayId}-exercises">
                        </tbody>
                    </table>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-gradient-primary btn-sm shadow-sm" onclick="addCircuit('${dayId}')">
                        <i class="bi bi-diagram-3 me-2"></i>Add Circuit
                    </button>
                    <button class="btn btn-sm" style="border: 2px solid rgb(255, 106, 0); color: rgb(255, 106, 0); background: white;" onclick="addExercise('${dayId}', false)">
                        <i class="bi bi-plus-circle me-2"></i>Add Exercise
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="addCoolDown('${dayId}')">
                        <i class="bi bi-wind me-2"></i>Add Cool Down
                    </button>
                    <button class="btn btn-outline-dark btn-sm" onclick="addCustomRow('${dayId}')">
                        <i class="bi bi-pencil-square me-2"></i>Add Custom Row
                    </button>
                </div>
            </div>
        </div>
    `;

        document.getElementById(`week-${weekId}-days`).insertAdjacentHTML('beforeend', dayHtml);

        // Persist the new day in backend
        const weekEl = document.getElementById(`week-${weekId}`);
        const backendWeekId = weekEl?.dataset?.weekId;
        if (backendWeekId) {
            const payload = {
                day_number: dayCounter[weekId],
                title: document.getElementById(`${dayId}-name`)?.value || `Day ${dayCounter[weekId]}`
            };
            if (SUPPRESS_PERSIST) {
                // Skip initial POST during renderProgram; ID will be linked later
                return;
            }
            ajax(`${BASE_PROGRAM_BUILDER_URL}/weeks/${backendWeekId}/days`, {
                method: 'POST',
                body: JSON.stringify(payload)
            }).then(resp => {
                const container = document.getElementById(dayId);
                if (resp?.day?.id && container) {
                    container.dataset.dayId = String(resp.day.id);
                    showNotification('success', 'Day added');
                    // Bind day title autosave after initial persistence
                    bindDayTitleAutosave(dayId);
                    // Accordion behavior: collapse other days in week; expand this one
                    collapseAllDaysExcept(weekId, dayId);
                }
            }).catch(err => {
                showAjaxError(err, 'Failed to add day');
            });
        }
    }

    function generateColumnHeaders() {
        return columnConfig.map(col =>
            `<th style="width: ${col.width}">
            <div class="column-header">
                <span>${col.name}</span>
                ${!col.required ? `<button class="btn btn-sm btn-icon column-remove-btn" onclick="removeColumnFromTable('${col.id}')" data-bs-toggle="tooltip" title="Remove column">
                    <i class="bi bi-x-circle text-danger"></i>
                </button>` : ''}
            </div>
        </th>`
        ).join('');
    }

    function removeDay(dayId) {
        if (confirm('Are you sure you want to remove this day?')) {
            const el = document.getElementById(dayId);
            const backendId = el?.dataset?.dayId;
            if (backendId) {
                ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendId}`, { method: 'DELETE' })
                .then(() => {
                    el.remove();
                    showNotification('success', 'Day removed');
                    const m = dayId.match(/^week-(\d+)-/);
                    const wIndex = m ? Number(m[1]) : null;
                    if (wIndex) { reindexDaysInWeek(document.getElementById(`week-${wIndex}`)); }
                }).catch(err => {
                    showAjaxError(err, 'Failed to remove day');
                });
            } else {
                el.remove();
                const m = dayId.match(/^week-(\d+)-/);
                const wIndex = m ? Number(m[1]) : null;
                if (wIndex) { reindexDaysInWeek(document.getElementById(`week-${wIndex}`)); }
            }
        }
    }

    function duplicateDay(dayId, weekId) {
        const dayElement = document.getElementById(dayId);
        const sourceBackendId = dayElement?.dataset?.dayId;
        const newNumber = (dayCounter[weekId] || 0) + 1;
        if (!sourceBackendId) {
            showNotification('error', 'Cannot duplicate: Day not persisted yet');
            return;
        }
        ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${sourceBackendId}/duplicate`, {
            method: 'POST',
            body: JSON.stringify({ day_number: newNumber })
        }).then(resp => {
            if (!resp?.day) { showNotification('error', 'Duplicate day failed'); return; }
            SUPPRESS_PERSIST = true;
            addDay(weekId);
            SUPPRESS_PERSIST = false;
            const newDayId = `week-${weekId}-day-${dayCounter[weekId]}`;
            // Populate new day container
            renderDayIntoExisting(weekId, resp.day, newDayId);
            // Accordion behavior: collapse others, expand this one
            collapseAllDaysExcept(weekId, newDayId);
            collapseAllCircuitsExcept(newDayId);
            showNotification('success', 'Day duplicated');
        }).catch(err => {
            showAjaxError(err, 'Failed to duplicate day');
        });
    }

    // ============================
    // Rendering helpers
    // ============================
    function renderWeekIntoExisting(w, weekIndex) {
        const weekEl = document.getElementById(`week-${weekIndex}`);
        if (!weekEl) { return; }
        weekEl.dataset.weekId = String(w.id);
        const header = weekEl.querySelector('.week-header h4');
        if (header && w.week_number) {
            header.innerHTML = `<i class="bi bi-calendar3 me-2"></i>Week ${w.week_number}`;
        }
        const wTitleEl = document.getElementById(`week-${weekIndex}-title`);
        const wDescEl = document.getElementById(`week-${weekIndex}-description`);
        if (wTitleEl) { wTitleEl.value = w.title || ''; }
        if (wDescEl) { wDescEl.value = w.description || ''; }
        bindWeekAutosave(weekIndex);
        const days = (w.days || []).sort((a,b) => (a.day_number||0) - (b.day_number||0));
        days.forEach(d => {
            SUPPRESS_PERSIST = true;
            addDay(weekIndex);
            SUPPRESS_PERSIST = false;
            const dayId = `week-${weekIndex}-day-${dayCounter[weekIndex]}`;
            renderDayIntoExisting(weekIndex, d, dayId);
        });
        initTooltips(weekEl);
    }

function renderDayIntoExisting(weekIndex, d, dayId) {
    const dayEl = document.getElementById(dayId);
    if (!dayEl) { return; }
    dayEl.dataset.dayId = String(d.id);
    const badge = dayEl.querySelector('.badge');
    if (badge) { badge.textContent = `Day ${d.day_number}`; }
    const nameInput = dayEl.querySelector(`#${dayId}-name`);
    if (nameInput) { nameInput.value = d.title || ''; }
    bindDayTitleAutosave(dayId);
    // Render cool down and custom rows (day-level specials) if present
    try {
        const cdVal = (d.cool_down || '').trim();
        if (cdVal) {
            // Add cool down row and set its value
            addCoolDown(dayId);
            const cdInput = dayEl.querySelector('td.cool-down input, td.cool-down textarea');
            if (cdInput) { cdInput.value = cdVal; }
        }
        const customRows = Array.isArray(d.custom_rows || d.customRows) ? (d.custom_rows || d.customRows) : [];
        if (customRows && customRows.length) {
            customRows.forEach(text => {
                addCustomRow(dayId);
                const lastRow = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
                const ta = lastRow ? lastRow.querySelector('textarea') : null;
                if (ta) { ta.value = (text || '').trim(); }
            });
        }
    } catch (e) {
        console.warn('Render day specials warn:', e);
    }
    const circuits = (d.circuits || []).sort((a,b) => (a.circuit_number||0) - (b.circuit_number||0));
    circuits.forEach(c => {
        SUPPRESS_PERSIST = true;
        addCircuit(dayId);
        SUPPRESS_PERSIST = false;
            const circuitRow = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
            if (circuitRow) { circuitRow.dataset.circuitId = String(c.id); }
            const exercises = (c.program_exercises || c.programExercises || []).sort((a,b) => (a.order||0) - (b.order||0));
            exercises.forEach(ex => {
                SUPPRESS_PERSIST = true;
                addExercise(dayId, true);
                SUPPRESS_PERSIST = false;
                const rowId = `${dayId}-exercise-${exerciseCounter[dayId]}`;
                const row = document.getElementById(rowId);
                row.dataset.exerciseId = String(ex.id);
                const cellsInputs = Array.from(row.querySelectorAll('td:not(.action-cell) input, td:not(.action-cell) textarea'));
                if (cellsInputs[0]) { cellsInputs[0].value = (ex.workout?.name || ex.workout?.title || ex.name || ''); }
                const sets = ex.exercise_sets || ex.exerciseSets || [];
                let si = 0;
                for (let i = 0; i < columnConfig.length; i++) {
                    if (columnConfig[i].id.startsWith('set')) {
                        const s = sets[si];
                        if (cellsInputs[i]) {
                            cellsInputs[i].value = s ? `${s.reps ?? ''} / ${s.weight ?? ''}` : '';
                        }
                        si++;
                    }
                }
                const notesIndex = columnConfig.findIndex(c => c.id === 'notes');
                if (notesIndex >= 0 && cellsInputs[notesIndex]) {
                    cellsInputs[notesIndex].value = ex.notes || '';
                }
                bindExerciseRowAutosave(row, dayId);
            });
        });
    }

    // ============================
    // Accordion helpers
    // ============================
    function collapseAllWeeksExcept(weekIndex) {
        const containers = Array.from(document.querySelectorAll('.week-container'));
        containers.forEach(cont => {
            const collapseEl = cont.querySelector(`#${cont.id}-collapse`);
            if (!collapseEl) { return; }
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            const idxMatch = cont.id.match(/^week-(\d+)$/);
            const idx = idxMatch ? Number(idxMatch[1]) : null;
            if (idx === weekIndex) { bsCollapse.show(); } else { bsCollapse.hide(); }
        });
    }

    function collapseAllDaysExcept(weekIndex, dayIdToShow) {
        const dayCollapses = Array.from(document.querySelectorAll(`#week-${weekIndex} [id^='week-${weekIndex}-day-'][id$='-collapse']`));
        dayCollapses.forEach(c => {
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
            if (`${c.id}` === `${dayIdToShow}-collapse`) { bsCollapse.show(); } else { bsCollapse.hide(); }
        });
    }

    function collapseAllCircuitsExcept(dayId, circuitRowToKeepId = null) {
        const tbody = document.getElementById(`${dayId}-exercises`);
        if (!tbody) { return; }
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let currentHeader = null;
        rows.forEach(r => {
            if (r.classList.contains('circuit-row')) {
                currentHeader = r;
                // Show or hide based on target
                r.classList.remove('d-none');
                return;
            }
            // Hide all non-header rows initially
            r.classList.add('d-none');
        });
        // If keepId provided, show rows under that circuit header
        if (circuitRowToKeepId) {
            const header = document.getElementById(circuitRowToKeepId);
            if (!header) { return; }
            let walker = header.nextElementSibling;
            while (walker && !walker.classList.contains('circuit-row')) {
                walker.classList.remove('d-none');
                walker = walker.nextElementSibling;
            }
        } else {
            // Default: show rows under the last circuit header
            const headers = Array.from(tbody.querySelectorAll('.circuit-row'));
            const lastHeader = headers[headers.length - 1];
            if (lastHeader) {
                let walker = lastHeader.nextElementSibling;
                while (walker && !walker.classList.contains('circuit-row')) {
                    walker.classList.remove('d-none');
                    walker = walker.nextElementSibling;
                }
            }
        }
    }

    function toggleCircuit(headerRowId) {
        const header = document.getElementById(headerRowId);
        if (!header) { return; }
        // Determine dayId from header id pattern: week-<w>-day-<d>-exercise-<n>
        const match = headerRowId.match(/^(week-\d+-day-\d+)-exercise-\d+$/);
        const dayId = match ? match[1] : null;
        if (!dayId) { return; }
        const tbody = document.getElementById(`${dayId}-exercises`);
        if (!tbody) { return; }
        // Toggle visibility of the group under this header; hide other groups
        const headers = Array.from(tbody.querySelectorAll('.circuit-row'));
        headers.forEach(h => {
            let walker = h.nextElementSibling;
            const shouldShow = h.id === headerRowId;
            while (walker && !walker.classList.contains('circuit-row')) {
                walker.classList.toggle('d-none', !shouldShow);
                walker = walker.nextElementSibling;
            }
        });
    }

    // =====================================================================
    // Render & CRUD: Circuit
    // =====================================================================
    function addCircuit(dayId) {
        exerciseCounter[dayId]++;
        const exercisesTbody = document.getElementById(`${dayId}-exercises`);
        const existingCircuits = exercisesTbody ? exercisesTbody.querySelectorAll('.circuit-row').length : 0;
        const circuitNum = existingCircuits + 1;

        const circuitHtml = `
        <tr class="circuit-row" id="${dayId}-exercise-${exerciseCounter[dayId]}">
            <td class="action-cell">
                <button class="btn btn-outline-danger btn-icon btn-sm" onclick="removeCircuit('${dayId}-exercise-${exerciseCounter[dayId]}')"
                        data-bs-toggle="tooltip" title="Remove Circuit">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <td class="circuit-label" colspan="${columnConfig.length}">
                <div class="d-flex justify-content-between align-items-center px-2">
                    <span><i class="bi bi-diagram-3 me-2" style="color: rgb(255, 106, 0);"></i>Circuit ${circuitNum}</span>
                    <button class="btn btn-sm" style="border: 1px solid rgb(255, 106, 0); color: rgb(255, 106, 0); background: white;" onclick="addExerciseToCircuit('${dayId}-exercise-${exerciseCounter[dayId]}')">
                        <i class="bi bi-plus me-1"></i>Add Exercise
                    </button>
                    <button class="btn btn-sm btn-link" onclick="toggleCircuit('${dayId}-exercise-${exerciseCounter[dayId]}')" title="Toggle Circuit">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;

        document.getElementById(`${dayId}-exercises`).insertAdjacentHTML('beforeend', circuitHtml);

        // Mark as pending circuit until backend returns an ID to prevent race conditions
        const pendingCircuitRow = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
        if (pendingCircuitRow) {
            pendingCircuitRow.dataset.circuitId = 'pending';
        }

        // Persist the new circuit in backend
        const dayEl = document.getElementById(dayId);
        const backendDayId = dayEl?.dataset?.dayId;
        if (backendDayId) {
            const payload = {
                circuit_number: circuitNum
            };
            if (SUPPRESS_PERSIST) {
                // Skip initial POST during renderProgram; ID will be linked later
                return;
            }
            ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}/circuits`, {
                method: 'POST',
                body: JSON.stringify(payload)
            }).then(resp => {
                const row = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
                if (resp?.circuit?.id && row) {
                    row.dataset.circuitId = String(resp.circuit.id);
                    showNotification('success', 'Circuit added');
                }
            }).catch(err => {
                showAjaxError(err, 'Failed to add circuit');
            });
        }
    }

    // Remove Circuit: deletes backend record if persisted, then removes row
    function removeCircuit(rowId) {
        if (!confirm('Are you sure you want to remove this circuit?')) { return; }
        const row = document.getElementById(rowId);
        const circuitBackendId = row?.dataset?.circuitId;
        const match = rowId.match(/^(week-\d+-day-\d+)-exercise-\d+$/);
        const dayId = match ? match[1] : null;
        if (circuitBackendId) {
            ajax(`${BASE_PROGRAM_BUILDER_URL}/circuits/${circuitBackendId}`, { method: 'DELETE' })
            .then(() => {
                // Remove header and all exercises until next circuit header
                let walker = row.nextElementSibling;
                row.remove();
                while (walker && !walker.classList.contains('circuit-row')) {
                    const next = walker.nextElementSibling;
                    walker.remove();
                    walker = next;
                }
                showNotification('success', 'Circuit removed');
                if (dayId) { reindexCircuits(dayId); }
            }).catch(err => {
                showAjaxError(err, 'Failed to remove circuit');
            });
        } else {
            let walker = row.nextElementSibling;
            row.remove();
            while (walker && !walker.classList.contains('circuit-row')) {
                const next = walker.nextElementSibling;
                walker.remove();
                walker = next;
            }
            if (dayId) { reindexCircuits(dayId); }
        }
    }

    function addExerciseToCircuit(headerRowId) {
        // Determine dayId from header id pattern: week-<w>-day-<d>-exercise-<n>
        const match = headerRowId.match(/^(week-\d+-day-\d+)-exercise-\d+$/);
        const dayId = match ? match[1] : null;
        if (!dayId) { return; }
        addExercise(dayId, true, headerRowId);
    }

    function reindexCircuits(dayId) {
        const tbody = document.getElementById(`${dayId}-exercises`);
        if (!tbody) { return; }
        const headers = Array.from(tbody.querySelectorAll('.circuit-row'));
        let cNum = 1;
        const promises = [];
        headers.forEach(h => {
            const label = h.querySelector('.circuit-label span');
            if (label) { label.innerHTML = `<i class="bi bi-diagram-3 me-2" style="color: rgb(255, 106, 0);"></i>Circuit ${cNum}`; }
            const cid = h?.dataset?.circuitId;
            if (cid) {
                promises.push(ajax(`${BASE_PROGRAM_BUILDER_URL}/circuits/${cid}`, {
                    method: 'PUT',
                    body: JSON.stringify({ circuit_number: cNum })
                }));
            }
            persistExercisesReorderInCircuit(h);
            cNum++;
        });
        Promise.allSettled(promises).then(() => {
            showNotification('success', 'Circuits renumbered');
        }).catch(() => {});
    }

    function persistExercisesReorderInCircuit(circuitRow) {
        const circuitId = circuitRow?.dataset?.circuitId;
        if (!circuitId) { return; }
        const payload = [];
        let walker = circuitRow.nextElementSibling;
        let orderIdx = 0;
        while (walker && !walker.classList.contains('circuit-row')) {
            if (walker.querySelector('.cool-down') || walker.classList.contains('custom-row')) {
                walker = walker.nextElementSibling;
                continue;
            }
            const id = walker?.dataset?.exerciseId;
            if (id) {
                payload.push({ id, order: orderIdx });
            }
            orderIdx++;
            walker = walker.nextElementSibling;
        }
        if (!payload.length) { return; }
        ajax(`${BASE_PROGRAM_BUILDER_URL}/circuits/${circuitId}/exercises/reorder`, {
            method: 'POST',
            body: JSON.stringify({ exercises: payload })
        }).then(() => {
            // silent success
        }).catch(() => {});
    }

    // =====================================================================
    // Render & CRUD: Exercise with Autosave
    // - Row is created first; persistence occurs on first valid input
    // - Subsequent changes trigger update via debounced autosave
    // =====================================================================
    function addExercise(dayId, isPartOfCircuit, insertAfterRowId = null) {
        exerciseCounter[dayId]++;
        const exerciseId = `${dayId}-exercise-${exerciseCounter[dayId]}`;

        const cells = columnConfig.map(col => {
            const isExercise = col.id === 'exercise';
            const isSet = col.id.startsWith('set');
            const isNotes = col.id === 'notes';
            const placeholder = isExercise ? 'Enter exercise name' : (isSet ? 'e.g., 12 / 40kg' : (isNotes ? 'Optional notes' : ''));
            const titleAttr = isExercise ? 'Type to match a workout or enter a custom name' : (isSet ? 'Enter reps / weight (e.g., 12 / 40kg)' : '');
            return `<td><input type="text" placeholder="${placeholder}" class="form-control form-control-sm border-0 ${isSet ? 'text-center' : ''}" data-bs-toggle="tooltip" title="${titleAttr}"></td>`;
        }).join('');

        const exerciseHtml = `
        <tr id="${exerciseId}">
            <td class="action-cell">
                <div class="d-flex flex-row gap-1">
                    <button class="btn btn-outline-primary btn-icon btn-sm" onclick="moveExerciseUp('${exerciseId}')"
                            data-bs-toggle="tooltip" title="Move Up">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button class="btn btn-outline-primary btn-icon btn-sm" onclick="moveExerciseDown('${exerciseId}')"
                            data-bs-toggle="tooltip" title="Move Down">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-icon btn-sm" onclick="removeExercise('${exerciseId}')"
                            data-bs-toggle="tooltip" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
            ${cells}
        </tr>
    `;

        if (insertAfterRowId) {
            const afterEl = document.getElementById(insertAfterRowId);
            if (afterEl && afterEl.parentElement) {
                afterEl.insertAdjacentHTML('afterend', exerciseHtml);
            } else {
                document.getElementById(`${dayId}-exercises`).insertAdjacentHTML('beforeend', exerciseHtml);
            }
        } else {
            document.getElementById(`${dayId}-exercises`).insertAdjacentHTML('beforeend', exerciseHtml);
        }

        const row = document.getElementById(exerciseId);
        bindExerciseRowAutosave(row, dayId);
        initTooltips(row);
    }

    function removeExercise(exerciseId) {
        if (confirm('Remove this row?')) {
            const row = document.getElementById(exerciseId);
            const dayEl = row?.closest('.day-container');
            const backendDayId = dayEl?.dataset?.dayId;
            const backendId = row?.dataset?.exerciseId;
            // Handle removal for persisted exercise rows
            if (backendId) {
                ajax(`${BASE_PROGRAM_BUILDER_URL}/exercises/${backendId}`, { method: 'DELETE' })
                .then(() => {
                    row.remove();
                    showNotification('success', 'Exercise removed');
                }).catch(err => {
                    showAjaxError(err, 'Failed to remove exercise');
                });
            } else if (row.querySelector('.cool-down')) {
                // Removing day-level cool down; clear in backend
                row.remove();
                if (backendDayId) {
                    ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}`, {
                        method: 'PUT',
                        body: JSON.stringify({ cool_down: null })
                    }).then(() => {
                        showNotification('success', 'Cool down removed');
                    }).catch(err => {
                        showAjaxError(err, 'Failed to remove cool down');
                    });
                }
            } else if (row.classList.contains('custom-row')) {
                // Removing a custom row; persist remaining custom rows
                row.remove();
                if (backendDayId) {
                    try {
                        const values = Array.from(dayEl.querySelectorAll('tr.custom-row textarea'))
                            .map(t => (t.value || '').trim())
                            .filter(v => v.length > 0);
                        ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}`, {
                            method: 'PUT',
                            body: JSON.stringify({ custom_rows: values })
                        }).then(() => {
                            showNotification('success', 'Custom row removed');
                        }).catch(err => {
                            showAjaxError(err, 'Failed to update custom rows');
                        });
                    } catch (e) {
                        showAjaxError(e, 'Failed to update custom rows');
                    }
                }
            } else {
                // Unpersisted, non day-level row
                row.remove();
            }
        }
    }

    function moveExerciseUp(exerciseId) {
        const row = document.getElementById(exerciseId);
        const prevRow = row.previousElementSibling;
        if (prevRow && !prevRow.classList.contains('circuit-row')) {
            row.parentNode.insertBefore(row, prevRow);
            persistExercisesReorderForRow(row);
        } else {
            showNotification('info', 'Row already at top of circuit');
        }
    }

    function moveExerciseDown(exerciseId) {
        const row = document.getElementById(exerciseId);
        const nextRow = row.nextElementSibling;
        if (nextRow && !nextRow.classList.contains('circuit-row')) {
            row.parentNode.insertBefore(nextRow, row);
            persistExercisesReorderForRow(row);
        } else {
            showNotification('info', 'Row already at bottom of circuit');
        }
    }

    function persistExercisesReorderForRow(row) {
        try {
            // Find circuit context for this row
            let prev = row.previousElementSibling;
            let circuitRow = null;
            while (prev) {
                if (prev.classList.contains('circuit-row')) {
                    circuitRow = prev;
                    break;
                }
                prev = prev.previousElementSibling;
            }
            const circuitId = circuitRow?.dataset?.circuitId;
            if (!circuitId) {
                // Local reorder only; cannot persist without circuit
                showNotification('info', 'Reorder applied locally. Persist after saving within a circuit.');
                return;
            }
            // Collect persisted exercises within this circuit in visual order
            const payload = [];
            let walker = circuitRow.nextElementSibling;
            let orderIdx = 0;
            while (walker && !walker.classList.contains('circuit-row')) {
                // Skip day-level rows from order calculation
                if (walker.querySelector('.cool-down') || walker.classList.contains('custom-row')) {
                    walker = walker.nextElementSibling;
                    continue;
                }
                const id = walker?.dataset?.exerciseId;
                if (id) {
                    payload.push({ id: Number(id), order: orderIdx });
                    orderIdx++;
                } else {
                    // Unpersisted rows still contribute to visual order but are skipped for backend
                    orderIdx++;
                }
                walker = walker.nextElementSibling;
            }
            if (!payload.length) { return; }
            ajax(`${BASE_PROGRAM_BUILDER_URL}/circuits/${circuitId}/exercises/reorder`, {
                method: 'POST',
                body: JSON.stringify({ exercises: payload })
            }).then(() => {
                showNotification('success', 'Row order updated');
            }).catch(err => {
                showAjaxError(err, 'Failed to reorder exercises');
            });
        } catch (e) {
            showAjaxError(e, 'Failed to reorder exercises');
        }
    }

    function addCoolDown(dayId) {
        // Only allow one cool down per day (matches single DB field)
        const dayEl = document.getElementById(dayId);
        if (dayEl && dayEl.querySelector('td.cool-down')) {
            showNotification('info', 'Cool down already added for this day');
            return;
        }
        exerciseCounter[dayId]++;
        const coolDownHtml = `
        <tr id="${dayId}-exercise-${exerciseCounter[dayId]}">
            <td class="action-cell">
                <button class="btn btn-outline-danger btn-icon btn-sm" onclick="removeExercise('${dayId}-exercise-${exerciseCounter[dayId]}')"
                        data-bs-toggle="tooltip" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <td colspan="${columnConfig.length}" class="cool-down">
                <i class="bi bi-wind me-2"></i>
                <input type="text" placeholder="Cool Down Activity" class="form-control form-control-sm border-0 text-center d-inline-block" style="width: 300px; background: transparent; font-style: italic; font-weight: 500;">
            </td>
        </tr>
    `;

        const tbody = document.getElementById(`${dayId}-exercises`);
        tbody.insertAdjacentHTML('beforeend', coolDownHtml);
        const row = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
        try { bindDaySpecialRowAutosave(row, dayId); } catch (e) { console.warn('Bind cool down autosave warn:', e); }
    }

    function addCustomRow(dayId) {
        exerciseCounter[dayId]++;
        const customHtml = `
        <tr id="${dayId}-exercise-${exerciseCounter[dayId]}" class="custom-row" style="background: #fff3cd;">
            <td class="action-cell">
                <button class="btn btn-outline-danger btn-icon btn-sm" onclick="removeExercise('${dayId}-exercise-${exerciseCounter[dayId]}')"
                        data-bs-toggle="tooltip" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <td colspan="${columnConfig.length}">
                <textarea class="form-control border-0" placeholder="Add custom notes or instructions..." rows="2" style="background: transparent; resize: vertical;"></textarea>
            </td>
        </tr>
    `;

        const tbody = document.getElementById(`${dayId}-exercises`);
        tbody.insertAdjacentHTML('beforeend', customHtml);
        const row = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
        try { bindDaySpecialRowAutosave(row, dayId); } catch (e) { console.warn('Bind custom row autosave warn:', e); }
    }

    function openColumnSettings(dayId) {
        currentDayId = dayId;
        renderColumnSettings();
        const modal = new bootstrap.Modal(document.getElementById('columnSettingsModal'));
        modal.show();
    }

    function renderColumnSettings() {
        const html = columnConfig.map((col, index) => `
        <div class="column-config-item" draggable="true" data-index="${index}">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-grip-vertical drag-handle fs-4"></i>
                <div class="flex-grow-1">
                    <input type="text" class="form-control mb-2" value="${col.name}" 
                           onchange="updateColumnName(${index}, this.value)" 
                           placeholder="Column name">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" onchange="updateColumnWidth(${index}, this.value)">
                                <option value="10%" ${col.width === '10%' ? 'selected' : ''}>10% width</option>
                                <option value="12%" ${col.width === '12%' ? 'selected' : ''}>12% width</option>
                                <option value="15%" ${col.width === '15%' ? 'selected' : ''}>15% width</option>
                                <option value="20%" ${col.width === '20%' ? 'selected' : ''}>20% width</option>
                                <option value="25%" ${col.width === '25%' ? 'selected' : ''}>25% width</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select form-select-sm" onchange="updateColumnType(${index}, this.value)">
                                <option value="text" ${col.type === 'text' ? 'selected' : ''}>Text</option>
                                <option value="number" ${col.type === 'number' ? 'selected' : ''}>Number</option>
                            </select>
                        </div>
                    </div>
                </div>
                ${!col.required ? `
                    <button class="btn btn-outline-danger btn-sm" onclick="removeColumn(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                ` : '<span class="badge badge-custom">Required</span>'}
            </div>
        </div>
    `).join('');

        document.getElementById('columnList').innerHTML = html;
    }

    // Day Title Autosave
    function bindDayTitleAutosave(dayId) {
        const input = document.getElementById(`${dayId}-name`);
        if (!input) { return; }
        let timer = null;
        const handler = () => {
            if (timer) { clearTimeout(timer); }
            timer = setTimeout(async () => {
                try {
                    const dayEl = document.getElementById(dayId);
                    const backendDayId = dayEl?.dataset?.dayId;
                    if (!backendDayId) { return; }
                    const badgeText = dayEl.querySelector('.badge')?.textContent || '';
                    const dayNumberMatch = badgeText.match(/\d+/);
                    const dayNumber = dayNumberMatch ? parseInt(dayNumberMatch[0], 10) : null;
                    await ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}`, {
                        method: 'PUT',
                        body: JSON.stringify({ day_number: dayNumber, title: input.value })
                    });
                    showNotification('success', 'Day name saved');
                } catch (e) {
                    showAjaxError(e, 'Failed to save day name');
                }
            }, 600);
        };
        input.addEventListener('input', handler);
        input.addEventListener('blur', handler);
    }

    // Week Title/Description Autosave
    function bindWeekAutosave(weekId) {
        const weekEl = document.getElementById(`week-${weekId}`);
        if (!weekEl) { return; }
        const titleInput = document.getElementById(`week-${weekId}-title`);
        const descInput = document.getElementById(`week-${weekId}-description`);
        const backendWeekId = weekEl?.dataset?.weekId;

        if (!titleInput && !descInput) { return; }

        let timer = null;
        const handler = () => {
            if (timer) { clearTimeout(timer); }
            timer = setTimeout(async () => {
                try {
                    const bId = weekEl?.dataset?.weekId;
                    if (!bId) { return; }
                    const headerText = weekEl.querySelector('.week-header h4')?.textContent || '';
                    const numMatch = headerText.match(/\d+/);
                    const weekNumber = numMatch ? parseInt(numMatch[0], 10) : null;
                    await ajax(`${BASE_PROGRAM_BUILDER_URL}/weeks/${bId}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            week_number: weekNumber,
                            title: titleInput ? titleInput.value : null,
                            description: descInput ? descInput.value : null
                        })
                    });
                    showNotification('success', 'Week details saved');
                } catch (e) {
                    showAjaxError(e, 'Failed to save week details');
                }
            }, 600);
        };

        if (titleInput) {
            titleInput.addEventListener('input', handler);
            titleInput.addEventListener('blur', handler);
        }
        if (descInput) {
            descInput.addEventListener('input', handler);
            descInput.addEventListener('blur', handler);
        }
    }

    function addColumn() {
        const setNumbers = columnConfig
            .map(c => /^set(\d+)$/.exec(c.id))
            .filter(m => !!m)
            .map(m => parseInt(m[1], 10))
            .filter(n => !isNaN(n));
        const nextSetNumber = (setNumbers.length ? Math.max(...setNumbers) : 0) + 1;
        const newCol = {
            id: `set${nextSetNumber}`,
            name: `Set ${nextSetNumber} - reps / w`,
            width: '12%',
            type: 'text',
            required: false
        };
        const prevConfig = columnConfig.slice();
        columnConfig.push(newCol);
        renderColumnSettings();
        refreshAllTables(prevConfig);
        saveColumnConfigRemote();
    }

    function removeColumn(index) {
        if (confirm('Remove this column?')) {
            columnConfig.splice(index, 1);
            renderColumnSettings();
            saveColumnConfigRemote();
        }
    }

    function updateColumnName(index, value) {
        columnConfig[index].name = value;
        saveColumnConfigRemote();
    }

    function updateColumnWidth(index, value) {
        columnConfig[index].width = value;
        saveColumnConfigRemote();
    }

    function updateColumnType(index, value) {
        columnConfig[index].type = value;
        saveColumnConfigRemote();
    }

    function removeColumnFromTable(columnId) {
        const index = columnConfig.findIndex(col => col.id === columnId);
        if (index !== -1 && !columnConfig[index].required) {
            if (confirm('Remove this column from all tables?')) {
                const prevConfig = columnConfig.slice();
                columnConfig.splice(index, 1);
                refreshAllTables(prevConfig);
                saveColumnConfigRemote();
            }
        }
    }

    function applyColumnSettings() {
        // Reorder columnConfig according to visual order in the modal
        const list = document.getElementById('columnList');
        const children = Array.from(list.children);
        const order = children.map(ch => parseInt(ch.dataset.index, 10)).filter(n => !isNaN(n));
        const current = columnConfig.slice();
        columnConfig = order.map(i => current[i]);
        refreshAllTables(current);
        initTooltips();
        saveColumnConfigRemote();
        showNotification('success', 'Column settings applied');
        bootstrap.Modal.getInstance(document.getElementById('columnSettingsModal')).hide();
    }

    function refreshAllTables(prevConfig = null) {
        document.querySelectorAll('[id$="-table"]').forEach(table => {
            const thead = table.querySelector('thead tr');
            const actionHeader = thead.querySelector('th:first-child');
            thead.innerHTML = '';
            thead.appendChild(actionHeader);
            thead.innerHTML += generateColumnHeaders();

            const tbody = table.querySelector('tbody');
            const dayId = table.id.replace(/-table$/, '');
            tbody.querySelectorAll('tr').forEach(row => {
                if (!row.classList.contains('circuit-row') && !row.classList.contains('custom-row') && row.querySelector('td:not(.cool-down):not(.action-cell)')) {
                    const actionCell = row.querySelector('.action-cell');
                    const prevInputs = Array.from(row.querySelectorAll('td:not(.action-cell) input, td:not(.action-cell) textarea'));
                    const prevValuesById = {};
                    const basePrev = Array.isArray(prevConfig) ? prevConfig : columnConfig.slice();
                    for (let i = 0; i < basePrev.length; i++) {
                        const prevCol = basePrev[i];
                        const val = prevInputs[i] ? prevInputs[i].value : '';
                        prevValuesById[prevCol.id] = val;
                    }
                    row.innerHTML = '';
                    if (actionCell) row.appendChild(actionCell);

                    columnConfig.forEach(col => {
                        const cell = document.createElement('td');
                        const isExercise = col.id === 'exercise';
                        const isSet = col.id && col.id.startsWith('set');
                        const isNotes = col.id === 'notes';
                        const placeholder = isExercise ? 'Enter exercise name' : (isSet ? 'e.g., 12 / 40kg' : (isNotes ? 'Optional notes' : ''));
                        const titleAttr = isExercise ? 'Type to match a workout or enter a custom name' : (isSet ? 'Enter reps / weight (e.g., 12 / 40kg)' : '');
                        cell.innerHTML = '<input type="text" class="form-control form-control-sm border-0">';
                        const input = cell.querySelector('input');
                        input.value = prevValuesById[col.id] ?? '';
                        input.setAttribute('placeholder', placeholder);
                        input.classList.toggle('text-center', !!isSet);
                        if (titleAttr) {
                            input.setAttribute('data-bs-toggle', 'tooltip');
                            input.setAttribute('title', titleAttr);
                        } else {
                            input.removeAttribute('title');
                        }
                        row.appendChild(cell);
                    });

                    try { bindExerciseRowAutosave(row, dayId); } catch (e) { console.warn('Rebind autosave warning:', e); }
                }
            });
            initTooltips(table);
        });
    }

    // Drag and Drop for Column Reordering
    document.addEventListener('dragstart', function(e) {
        if (e.target.classList.contains('column-config-item')) {
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }
    });

    document.addEventListener('dragend', function(e) {
        if (e.target.classList.contains('column-config-item')) {
            e.target.classList.remove('dragging');
        }
    });

    document.addEventListener('dragover', function(e) {
        e.preventDefault();
        const draggingItem = document.querySelector('.dragging');
        if (!draggingItem) return;

        const afterElement = getDragAfterElement(e.clientY);
        const container = document.getElementById('columnList');

        if (afterElement == null) {
            container.appendChild(draggingItem);
        } else {
            container.insertBefore(draggingItem, afterElement);
        }
    });

    function getDragAfterElement(y) {
        const draggableElements = [...document.querySelectorAll('.column-config-item:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return {
                    offset: offset,
                    element: child
                };
            } else {
                return closest;
            }
        }, {
            offset: Number.NEGATIVE_INFINITY
        }).element;
    }

    // =====================================================================
    // Autosave Binding & Payload Builders
    // =====================================================================
    function parseSetValue(val) {
        if (!val) { return { reps: null, weight: null }; }
        const parts = String(val).split('/');
        const reps = parts[0] ? parseInt(parts[0].replace(/\D+/g, ''), 10) : null;
        const weight = parts[1] ? parseFloat(parts[1].replace(/[^0-9.\-]+/g, '')) : null;
        return { reps: isNaN(reps) ? null : reps, weight: isNaN(weight) ? null : weight };
    }

    function buildExercisePayloadFromRow(row, dayId) {
        const inputs = Array.from(row.querySelectorAll('td:not(.action-cell) input, td:not(.action-cell) textarea'));
        const exerciseNameInput = inputs[0];
        const workoutId = resolveWorkoutIdByName(exerciseNameInput?.value || '');
        const notesInput = inputs.find((el, idx) => columnConfig[idx]?.id === 'notes');

        // Build sets from set columns in order
        const sets = [];
        let setNumber = 1;
        for (let i = 0; i < columnConfig.length; i++) {
            const col = columnConfig[i];
            if (col.id.startsWith('set')) {
                const { reps, weight } = parseSetValue(inputs[i]?.value || '');
                // Only include a set if any value is provided; keep sequence numbering
                if (reps !== null || weight !== null) {
                    sets.push({ set_number: setNumber, reps, weight });
                }
                setNumber++;
            }
        }
        // Ensure at least one placeholder set to satisfy validation when creating
        if (sets.length === 0) {
            sets.push({ set_number: 1, reps: null, weight: null });
        }

        // Tempo & rest interval removed; handled at exercise-level notes/sets only

        // Determine order within its circuit grouping
        const tbody = row.parentElement;
        let circuitId = null;
        let order = 0;
        const rows = Array.from(tbody.children);
        let currentCircuitId = null;
        rows.forEach(tr => {
            if (tr.classList.contains('circuit-row')) {
                currentCircuitId = tr.dataset.circuitId || null;
            } else if (!tr.querySelector('.cool-down') && !tr.classList.contains('custom-row')) {
                if (tr === row) {
                    circuitId = currentCircuitId;
                }
                if (currentCircuitId === circuitId) {
                    order++;
                }
            }
        });

        return {
            name: (exerciseNameInput?.value || '').trim() || null,
            workout_id: workoutId,
            order: order - 1,
            notes: notesInput ? notesInput.value : null,
            sets
        };
    }

    function bindExerciseRowAutosave(row, dayId) {
        let timer = null;
        const handler = () => {
            if (timer) { clearTimeout(timer); }
            timer = setTimeout(async () => {
                try {
                    const payload = buildExercisePayloadFromRow(row, dayId);
                    // If not persisted yet, require either a workout match or a non-empty name
                    if (!row.dataset.exerciseId) {
                        const hasName = !!(payload.name && payload.name.trim());
                        if (!payload.workout_id && !hasName) {
                            showNotification('error', 'Enter an exercise name or select a workout');
                            return;
                        }
                        // Resolve the circuit id; wait briefly if circuit creation is pending
                        const resolveCircuitIdForRow = async () => {
                            let prev = row.previousElementSibling;
                            let circuitRow = null;
                            while (prev) {
                                if (prev.classList.contains('circuit-row')) { circuitRow = prev; break; }
                                prev = prev.previousElementSibling;
                            }
                            if (!circuitRow) { return null; }
                            // If pending, wait up to 2s for backend to return id
                            const start = Date.now();
                            while (Date.now() - start < 2000) {
                                const cid = circuitRow.dataset.circuitId;
                                if (cid && cid !== 'pending') { return cid; }
                                await new Promise(r => setTimeout(r, 150));
                            }
                            return circuitRow.dataset.circuitId && circuitRow.dataset.circuitId !== 'pending' ? circuitRow.dataset.circuitId : null;
                        };
                        let circuitId = await resolveCircuitIdForRow();
                    if (!circuitId) {
                        // Auto-create a circuit in backend and link current row
                        const dayEl = document.getElementById(dayId);
                        const backendDayId = dayEl?.dataset?.dayId;
                        if (!backendDayId) { showNotification('error', 'Day not persisted yet'); return; }
                        const circuitNum = (Array.from(row.parentElement.querySelectorAll('.circuit-row')).length || 0) + 1;
                        const respCircuit = await ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}/circuits`, {
                            method: 'POST',
                            body: JSON.stringify({ circuit_number: circuitNum })
                        });
                        circuitId = respCircuit?.circuit?.id;
                        // Insert a visible circuit header above if not exists (visual continuity)
                        const circuitHtml = `
                                <tr class="circuit-row">
                                    <td class="action-cell">
                                        <button class="btn btn-outline-danger btn-icon btn-sm" data-bs-toggle="tooltip" title="Remove Circuit" onclick="removeCircuit('${dayId}-exercise-dummy')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <td class="circuit-label" colspan="${columnConfig.length}">
                                        <div class="d-flex justify-content-between align-items-center px-2">
                                            <span><i class="bi bi-diagram-3 me-2" style="color: rgb(255, 106, 0);"></i>Circuit ${circuitNum}</span>
                                        </div>
                                    </td>
                                </tr>`;
                        row.insertAdjacentHTML('beforebegin', circuitHtml);
                        const header = row.previousElementSibling;
                        if (header) { header.dataset.circuitId = String(circuitId); }
                    }
                        const resp = await ajax(`${BASE_PROGRAM_BUILDER_URL}/circuits/${circuitId}/exercises`, {
                            method: 'POST',
                            body: JSON.stringify(payload)
                        });
                        if (resp?.program_exercise?.id) {
                            row.dataset.exerciseId = String(resp.program_exercise.id);
                            showNotification('success', 'Exercise created');
                        }
                    } else {
                        // Update existing exercise
                        const id = row.dataset.exerciseId;
                        // Update workout mapping (allow clearing by sending null)
                        await ajax(`${BASE_PROGRAM_BUILDER_URL}/exercises/${id}/workout`, {
                            method: 'PUT',
                            body: JSON.stringify({ workout_id: payload.workout_id ?? null })
                        });
                        await ajax(`${BASE_PROGRAM_BUILDER_URL}/exercises/${id}`, {
                            method: 'PUT',
                            body: JSON.stringify({
                                name: payload.name,
                                notes: payload.notes,
                                sets: payload.sets
                            })
                        });
                        showNotification('success', 'Changes saved');
                    }
                } catch (e) {
                    showAjaxError(e, 'Autosave failed');
                }
            }, 800);
        };
        // Bind to input changes within row. Avoid duplicate listeners by marking elements.
        row.querySelectorAll('input, textarea').forEach(el => {
            if (el.dataset.autosaveBound === '1') { return; }
            el.addEventListener('input', handler);
            el.addEventListener('blur', handler);
            el.dataset.autosaveBound = '1';
        });
    }

    // Day-level cool down & custom rows autosave
    function collectCustomRowsForDay(dayId) {
        const dayEl = document.getElementById(dayId);
        if (!dayEl) { return []; }
        return Array.from(dayEl.querySelectorAll('tr.custom-row textarea'))
            .map(t => (t.value || '').trim())
            .filter(v => v.length > 0);
    }

    function bindDaySpecialRowAutosave(row, dayId) {
        const dayEl = document.getElementById(dayId);
        if (!dayEl) { return; }
        const backendDayId = dayEl?.dataset?.dayId;
        if (!backendDayId) { return; }
        const input = row.classList.contains('custom-row')
            ? row.querySelector('textarea')
            : row.querySelector('td.cool-down input, td.cool-down textarea');
        if (!input) { return; }
        let timer = null;
        const handler = () => {
            if (timer) { clearTimeout(timer); }
            timer = setTimeout(async () => {
                try {
                    const coolDownEl = dayEl.querySelector('td.cool-down input, td.cool-down textarea');
                    const coolDownVal = coolDownEl ? (coolDownEl.value || '').trim() : '';
                    const customRowsVals = collectCustomRowsForDay(dayId);
                    await ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            cool_down: coolDownVal || null,
                            custom_rows: customRowsVals
                        })
                    });
                    showNotification('success', 'Day details saved');
                } catch (e) {
                    showAjaxError(e, 'Failed to save day details');
                }
            }, 600);
        };
        input.addEventListener('input', handler);
        input.addEventListener('blur', handler);
    }

    // Initialize: Render existing backend data
    document.addEventListener('DOMContentLoaded', async function() {
        // Load column configuration from server (per program)
        await loadColumnConfigRemote();
        function renderProgram(program) {
            const weeks = (program.weeks || []).sort((a,b) => (a.week_number||0) - (b.week_number||0));
            weeks.forEach(w => {
                addWeek();
                const weekEl = document.getElementById(`week-${weekCounter}`);
                weekEl.dataset.weekId = String(w.id);
                weekEl.querySelector('.week-header h4').innerHTML = `<i class="bi bi-calendar3 me-2"></i>Week ${w.week_number}`;
                const wTitleEl = document.getElementById(`week-${weekCounter}-title`);
                const wDescEl = document.getElementById(`week-${weekCounter}-description`);
                if (wTitleEl) { wTitleEl.value = w.title || ''; }
                if (wDescEl) { wDescEl.value = w.description || ''; }
                bindWeekAutosave(weekCounter);
                const days = (w.days || []).sort((a,b) => (a.day_number||0) - (b.day_number||0));
                days.forEach(d => {
                    addDay(weekCounter);
                    const dayId = `week-${weekCounter}-day-${dayCounter[weekCounter]}`;
                    const dayEl = document.getElementById(dayId);
                    dayEl.dataset.dayId = String(d.id);
                    dayEl.querySelector('.badge').textContent = `Day ${d.day_number}`;
                    const nameInput = dayEl.querySelector(`#${dayId}-name`);
                    if (nameInput) { nameInput.value = d.title || ''; }
                    bindDayTitleAutosave(dayId);
                    const circuits = (d.circuits || []).sort((a,b) => (a.circuit_number||0) - (b.circuit_number||0));
                    circuits.forEach(c => {
                        addCircuit(dayId);
                        const circuitRow = document.getElementById(`${dayId}-exercise-${exerciseCounter[dayId]}`);
                        circuitRow.dataset.circuitId = String(c.id);
                        // Render exercises within circuit
                        const exercises = (c.program_exercises || c.programExercises || []).sort((a,b) => (a.order||0) - (b.order||0));
                        exercises.forEach(ex => {
                            addExercise(dayId, true);
                            const rowId = `${dayId}-exercise-${exerciseCounter[dayId]}`;
                            const row = document.getElementById(rowId);
                            row.dataset.exerciseId = String(ex.id);
                            const cellsInputs = Array.from(row.querySelectorAll('td:not(.action-cell) input, td:not(.action-cell) textarea'));
                            // Exercise name (prefer linked workout name/title; fallback to free-form name)
                            if (cellsInputs[0]) { cellsInputs[0].value = (ex.workout?.name || ex.workout?.title || ex.name || ''); }
                            // Sets
                            const sets = ex.exercise_sets || ex.exerciseSets || [];
                            let si = 0;
                            for (let i = 0; i < columnConfig.length; i++) {
                                if (columnConfig[i].id.startsWith('set')) {
                                    const s = sets[si];
                                    if (cellsInputs[i]) {
                                        cellsInputs[i].value = s ? `${s.reps ?? ''} / ${s.weight ?? ''}` : '';
                                    }
                                    si++;
                                }
                            }
                            // Notes
                            const notesIndex = columnConfig.findIndex(c => c.id === 'notes');
                            if (notesIndex >= 0 && cellsInputs[notesIndex]) {
                                cellsInputs[notesIndex].value = ex.notes || '';
                            }
                            bindExerciseRowAutosave(row, dayId);
                        });
                    });

                    // Render custom rows (if any)
                    try {
                        const customRows = Array.isArray(d.custom_rows) ? d.custom_rows : [];
                        customRows.forEach(text => {
                            addCustomRow(dayId);
                            const rowId = `${dayId}-exercise-${exerciseCounter[dayId]}`;
                            const row = document.getElementById(rowId);
                            const ta = row.querySelector('textarea');
                            if (ta) { ta.value = text || ''; }
                            bindDaySpecialRowAutosave(row, dayId);
                        });
                    } catch (e) { console.warn('Render custom rows warning:', e); }

                    // Render cool down (if present)
                    try {
                        if (d.cool_down) {
                            addCoolDown(dayId);
                            const rowId = `${dayId}-exercise-${exerciseCounter[dayId]}`;
                            const row = document.getElementById(rowId);
                            const cd = row.querySelector('td.cool-down input, td.cool-down textarea');
                            if (cd) { cd.value = d.cool_down || ''; }
                            bindDaySpecialRowAutosave(row, dayId);
                        }
                    } catch (e) { console.warn('Render cool down warning:', e); }
                });
            });
        }

        // During initial render, suppress backend POST/PUT calls in addWeek/addDay/addCircuit
        SUPPRESS_PERSIST = true;
        renderProgram(@json($program));
        SUPPRESS_PERSIST = false;

        // Initialize tooltips
        initTooltips();

        // Default accordion state: collapse all, show latest week/day/circuit only
        try {
            if (weekCounter > 0) {
                collapseAllWeeksExcept(weekCounter);
                const lastDayId = `week-${weekCounter}-day-${dayCounter[weekCounter] || 1}`;
                collapseAllDaysExcept(weekCounter, lastDayId);
                collapseAllCircuitsExcept(lastDayId);
            }
        } catch (e) {
            // Non-blocking: accordion initialization failures should not prevent page load
            console.warn('Accordion init warning:', e);
        }
    });

    function reindexWeeksAndDays() {
        const weeks = Array.from(document.querySelectorAll('.week-container'));
        let wNum = 1;
        const promises = [];
        weeks.forEach(weekEl => {
            const header = weekEl.querySelector('.week-header h4');
            if (header) { header.innerHTML = `<i class="bi bi-calendar3 me-2"></i>Week ${wNum}`; }
            const bId = weekEl?.dataset?.weekId;
            if (bId) {
                promises.push(ajax(`${BASE_PROGRAM_BUILDER_URL}/weeks/${bId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ week_number: wNum })
                }));
            }
            reindexDaysInWeek(weekEl);
            wNum++;
        });
        Promise.allSettled(promises).then(() => {
            showNotification('success', 'Weeks and days renumbered');
        }).catch(() => {
            // silent
        });
    }

    function reindexDaysInWeek(weekEl) {
        if (!weekEl) { return; }
        const days = Array.from(weekEl.querySelectorAll('.day-container'));
        let dNum = 1;
        const promises = [];
        days.forEach(dayEl => {
            const badge = dayEl.querySelector('.badge');
            if (badge) { badge.textContent = `Day ${dNum}`; }
            const bId = dayEl?.dataset?.dayId;
            if (bId) {
                promises.push(ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${bId}`, {
                    method: 'PUT',
                    body: JSON.stringify({ day_number: dNum })
                }));
            }
            dNum++;
        });
        Promise.allSettled(promises).then(() => {
            // no-op
        }).catch(() => {});
    }

    // Save and Export Functions
    function saveWorkout() {
        const workoutData = {
            weeks: [],
            columns: columnConfig,
            createdAt: new Date().toISOString()
        };

        document.querySelectorAll('.week-container').forEach(weekEl => {
            const weekData = {
                weekNumber: weekEl.querySelector('.week-header h4').textContent.match(/\d+/)[0],
                days: []
            };

            weekEl.querySelectorAll('.day-container').forEach(dayEl => {
                const dayData = {
                    dayNumber: dayEl.querySelector('.badge').textContent.match(/\d+/)[0],
                    name: dayEl.querySelector('.day-name-input').value,
                    exercises: []
                };

                dayEl.querySelectorAll('tbody tr').forEach(row => {
                    if (row.classList.contains('circuit-row')) {
                        dayData.exercises.push({
                            type: 'circuit',
                            name: row.textContent.trim()
                        });
                    } else if (row.querySelector('.cool-down')) {
                        dayData.exercises.push({
                            type: 'cooldown',
                            content: row.querySelector('input, textarea')?.value || ''
                        });
                    } else if (row.classList.contains('custom-row')) {
                        dayData.exercises.push({
                            type: 'custom',
                            content: row.querySelector('textarea')?.value || ''
                        });
                    } else {
                        const inputs = Array.from(row.querySelectorAll('input, textarea'));
                        const exerciseData = {
                            type: 'exercise',
                            data: {}
                        };

                        columnConfig.forEach((col, index) => {
                            if (inputs[index]) {
                                exerciseData.data[col.id] = inputs[index].value;
                            }
                        });

                        dayData.exercises.push(exerciseData);
                    }
                });

                weekData.days.push(dayData);
            });

            workoutData.weeks.push(weekData);
        });

        return workoutData;
    }

    function exportToJSON() {
        const data = saveWorkout();
        const blob = new Blob([JSON.stringify(data, null, 2)], {
            type: 'application/json'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `workout-program-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function exportToCSV() {
        const data = saveWorkout();
        let csv = 'Week,Day,Day Name,Exercise Type,Exercise Name,';
        csv += columnConfig.map(col => col.name).join(',') + '\n';

        data.weeks.forEach(week => {
            week.days.forEach(day => {
                day.exercises.forEach(exercise => {
                    csv += `${week.weekNumber},${day.dayNumber},"${day.name}",${exercise.type},`;

                    if (exercise.type === 'exercise') {
                        csv += columnConfig.map(col => `"${exercise.data[col.id] || ''}"`).join(',');
                    } else if (exercise.type === 'cooldown') {
                        csv += `"${exercise.content}"`;
                    } else if (exercise.type === 'custom') {
                        csv += `"${exercise.content}"`;
                    } else {
                        csv += `"${exercise.name}"`;
                    }

                    csv += '\n';
                });
            });
        });

        const blob = new Blob([csv], {
            type: 'text/csv'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `workout-program-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function printWorkout() {
        window.print();
    }

    // Add export buttons to the main header
    document.addEventListener('DOMContentLoaded', function() {
        const headerCard = document.querySelector('.card-body');
        if (headerCard) {
            const exportButtons = `
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <button class="btn btn-gradient-primary btn-sm shadow-sm" onclick="saveAllChanges()">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
                <button class="btn btn-sm" style="border: 2px solid rgb(255, 106, 0); color: rgb(255, 106, 0); background: white;" onclick="exportToJSON()">
                    <i class="bi bi-download me-1"></i>Export JSON
                </button>
                <button class="btn btn-outline-dark btn-sm" onclick="exportToCSV()">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="printWorkout()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="clearAll()">
                    <i class="bi bi-trash me-1"></i>Clear All
                </button>
            </div>
        `;
            headerCard.insertAdjacentHTML('beforeend', exportButtons);
        }
    });

    function clearAll() {
        if (confirm('Are you sure you want to clear all weeks and start over? This cannot be undone.')) {
            document.getElementById('weeksContainer').innerHTML = '';
            weekCounter = 0;
            dayCounter = {};
            exerciseCounter = {};
            addWeek();
            addDay(1);
        }
    }

    // Batch Save: Iterate all rows and persist changes (create/update)
    async function saveAllChanges() {
        try {
            showNotification('info', 'Saving changes...');
            const days = Array.from(document.querySelectorAll('.day-container'));
            for (const dayEl of days) {
                const dayId = dayEl.id;
                const backendDayId = dayEl?.dataset?.dayId;
                if (!backendDayId) { continue; }
                // Persist day-level fields: cool_down and custom_rows
                try {
                    const coolDownEl = dayEl.querySelector('td.cool-down input, td.cool-down textarea');
                    const coolDownVal = coolDownEl ? (coolDownEl.value || '').trim() : '';
                    const customRowsVals = Array.from(dayEl.querySelectorAll('tr.custom-row textarea'))
                        .map(t => (t.value || '').trim())
                        .filter(v => v.length > 0);
                    await ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            cool_down: coolDownVal || null,
                            custom_rows: customRowsVals
                        })
                    });
                } catch (e) {
                    console.warn('Day-level save warning:', e);
                }
                const tbody = dayEl.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                let currentCircuitId = null;
                for (const row of rows) {
                    if (row.classList.contains('circuit-row')) {
                        currentCircuitId = row.dataset.circuitId || null;
                        continue;
                    }
                    if (row.querySelector('.cool-down')) { continue; }
                    if (row.classList.contains('custom-row')) { continue; }
                    const payload = buildExercisePayloadFromRow(row, dayId);
                    // Skip empty rows only if both name and workout_id are missing
                    if (!row.dataset.exerciseId && !payload.workout_id && !(payload.name && payload.name.trim())) { continue; }
                    if (!row.dataset.exerciseId) {
                        // Ensure circuit exists
                        let circuitId = currentCircuitId;
                        if (!circuitId) {
                            const circuitNum = (Array.from(tbody.querySelectorAll('.circuit-row')).length || 0) + 1;
                            const respCircuit = await ajax(`${BASE_PROGRAM_BUILDER_URL}/days/${backendDayId}/circuits`, {
                                method: 'POST',
                                body: JSON.stringify({ circuit_number: circuitNum })
                            });
                            circuitId = respCircuit?.circuit?.id;
                            // Insert a circuit header visually
                            const circuitHtml = `
                                <tr class="circuit-row">
                                    <td class="action-cell">
                                        <button class="btn btn-outline-danger btn-icon btn-sm" data-bs-toggle="tooltip" title="Remove Circuit" onclick="removeCircuit('${dayId}-exercise-dummy')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <td class="circuit-label" colspan="${columnConfig.length}">
                                        <div class="d-flex justify-content-between align-items-center px-2">
                                            <span><i class="bi bi-diagram-3 me-2" style="color: rgb(255, 106, 0);"></i>Circuit ${circuitNum}</span>
                                        </div>
                                    </td>
                                </tr>`;
                            tbody.insertAdjacentHTML('afterbegin', circuitHtml);
                            const header = tbody.querySelector('.circuit-row');
                            if (header) { header.dataset.circuitId = String(circuitId); }
                            currentCircuitId = circuitId;
                        }
                        const resp = await ajax(`${BASE_PROGRAM_BUILDER_URL}/circuits/${circuitId}/exercises`, {
                            method: 'POST',
                            body: JSON.stringify(payload)
                        });
                        if (resp?.program_exercise?.id) {
                            row.dataset.exerciseId = String(resp.program_exercise.id);
                        }
                    } else {
                        const id = row.dataset.exerciseId;
                        // Update workout mapping (allow clearing)
                        await ajax(`${BASE_PROGRAM_BUILDER_URL}/exercises/${id}/workout`, {
                            method: 'PUT',
                            body: JSON.stringify({ workout_id: payload.workout_id ?? null })
                        });
                        await ajax(`${BASE_PROGRAM_BUILDER_URL}/exercises/${id}`, {
                            method: 'PUT',
                            body: JSON.stringify({
                                name: payload.name,
                                notes: payload.notes,
                                sets: payload.sets
                            })
                        });
                    }
                }
            }
            showNotification('success', 'All changes saved');
        } catch (e) {
            showAjaxError(e, 'Failed to save all changes');
        }
    }
</script>
@endsection
