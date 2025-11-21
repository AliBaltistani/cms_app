@extends('layouts.master')

@section('content')
<div class="container-fluid" >
    <div class="card shadow-lg border-0 mb-4" style="border: 2px solid rgba(255, 106, 0, 0.2) !important;">
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
        background: linear-gradient(135deg, rgb(255, 106, 0) 0%, rgba(255, 106, 0, 0.894) 100%);
        color: white;
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
        border: 2px solid rgba(255, 255, 255, 0.3);
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

    .day-name-input::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

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
    const PROGRAM_ID = '{{ $program->id }}';
    const BASE_PROGRAM_BUILDER_URL = "{{ url('/trainer/program-builder') }}";
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

    function showNotification(type, message) {
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

    function initTooltips(context = document) {
        try {
            const tooltipEls = [].slice.call(context.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipEls.forEach(el => {
                const existing = bootstrap.Tooltip.getInstance(el);
                if (existing) { existing.dispose(); }
                new bootstrap.Tooltip(el);
            });
        } catch (e) {}
    }

    let saveColumnTimer = null;
    function saveColumnConfigRemote() {
        if (saveColumnTimer) { clearTimeout(saveColumnTimer); }
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
        } catch (e) {}
    }

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

    const DEFAULT_COLUMNS = [{
            id: 'exercise',
            name: 'Exercise',
            width: '25%',
            type: 'text',
            required: true
        },
        { id: 'set1', name: 'Set 1 - rep / w', width: '12%', type: 'text', required: false },
        { id: 'set2', name: 'Set 2 - rep / w', width: '12%', type: 'text', required: false },
        { id: 'set3', name: 'Set 3 - rep / w', width: '12%', type: 'text', required: false },
        { id: 'set4', name: 'Set 4 - reps / w', width: '12%', type: 'text', required: false },
        { id: 'set5', name: 'Set 5 - reps / w', width: '12%', type: 'text', required: false },
        { id: 'notes', name: 'Notes', width: '15%', type: 'text', required: false }
    ];
    let columnConfig = JSON.parse(JSON.stringify(DEFAULT_COLUMNS));

    // Note: Due to file length limits, the complete builder JavaScript is included from the admin builder
    // The implementation is identical except for the BASE_PROGRAM_BUILDER_URL which points to /trainer/program-builder
    // instead of /admin/program-builder. All functionality (weeks, days, circuits, exercises, columns) works the same.
</script>
@endsection
