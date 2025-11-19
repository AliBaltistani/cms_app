@extends('layouts.master')

@section('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Workout Programs</h1>
            <p class="mb-0 text-muted">Manage workout programs and assign them to clients</p>
        </div>
        <a href="{{ route('programs.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create New Program
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Programs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-programs">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Programs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-programs">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Assigned Programs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="assigned-programs">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Unassigned Programs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="unassigned-programs">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Programs List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="programsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Program Name</th>
                            <th>Trainer</th>
                            <th>Client</th>
                            <th>Duration</th>
                            <th>Weeks</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#programsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('programs.index') }}",
                    type: "GET"
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'trainer', name: 'trainer' },
                    { data: 'client', name: 'client' },
                    { data: 'duration', name: 'duration' },
                    { data: 'weeks_count', name: 'weeks_count', orderable: false, searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
                }
            });

            // Load statistics
            loadStatistics();

            $(document).on('click', '.program-pdf-show', function() {
                var id = $(this).data('program-id');
                fetchProgramPdfData(id).then(function(program) {
                    var doc = buildProgramPdfDoc(program);
                    pdfMake.createPdf(doc).open();
                }).catch(function() {
                    Swal.fire('Error', 'Failed to generate PDF', 'error');
                });
            });

            $(document).on('click', '.program-pdf-download', function() {
                var id = $(this).data('program-id');
                fetchProgramPdfData(id).then(function(program) {
                    var doc = buildProgramPdfDoc(program);
                    var filename = (program.name || 'program') + '-' + program.id + '.pdf';
                    pdfMake.createPdf(doc).download(filename);
                }).catch(function() {
                    Swal.fire('Error', 'Failed to generate PDF', 'error');
                });
            });
        });

        function loadStatistics() {
            $.ajax({
                url: "{{ route('programs.stats') }}",
                type: 'GET',
                success: function(data) {
                    $('#total-programs').text(data.total_programs);
                    $('#active-programs').text(data.active_programs);
                    $('#assigned-programs').text(data.assigned_programs);
                    $('#unassigned-programs').text(data.unassigned_programs);
                },
                error: function() {
                    $('#total-programs, #active-programs, #assigned-programs, #unassigned-programs').text('Error');
                }
            });
        }

        function deleteProgram(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this! This will also delete all associated weeks, days, circuits, and exercises.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/programs/${id}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                                $('#programsTable').DataTable().ajax.reload();
                                loadStatistics();
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'An error occurred while deleting the program.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        function fetchProgramPdfData(id) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '/admin/programs/' + id + '/pdf-data',
                    type: 'GET',
                    success: function(resp) {
                        if (resp && resp.success && resp.data && resp.data.program) {
                            resolve(resp.data.program);
                        } else {
                            reject();
                        }
                    },
                    error: function() { reject(); }
                });
            });
        }

        function buildProgramPdfDoc(program) {
            var content = [];
            content.push({ text: program.name || 'Program', style: 'title' });
            content.push({
                columns: [
                    { width: 'auto', text: 'Trainer: ' + (program.trainer ? (program.trainer.name || 'N/A') : 'N/A') },
                    { width: '*', text: '' },
                    { width: 'auto', text: 'Client: ' + (program.client ? (program.client.name || 'Unassigned') : 'Unassigned') }
                ]
            });
            content.push({
                columns: [
                    { width: 'auto', text: 'Duration: ' + ((program.duration || 0) + ' weeks') },
                    { width: '*', text: '' },
                    { width: 'auto', text: 'Status: ' + (program.is_active ? 'Active' : 'Inactive') }
                ],
                margin: [0, 2, 0, 10]
            });
            if (program.description) {
                content.push({ text: program.description, margin: [0, 0, 0, 10] });
            }
            if (program.weeks && program.weeks.length) {
                var weekItems = program.weeks.map(function(week) {
                    var weekText = 'Week ' + week.week_number + (week.title ? ' – ' + week.title : '');
                    var dayItems = (week.days || []).map(function(day) {
                        var dayText = 'Day ' + day.day_number + (day.title ? ' – ' + day.title : '');
                        var circuitItems = (day.circuits || []).map(function(circuit) {
                            var circuitText = 'Circuit ' + circuit.circuit_number + (circuit.title ? ' – ' + circuit.title : '');
                            var exerciseItems = (circuit.program_exercises || []).map(function(ex) {
                                var title = ex.name || (ex.workout && (ex.workout.name || ex.workout.title)) || 'Exercise';
                                var detailItems = [];
                                if (ex.tempo) { detailItems.push('Tempo: ' + ex.tempo); }
                                if (ex.rest_interval) { detailItems.push('Rest: ' + ex.rest_interval); }
                                if (ex.exercise_sets && ex.exercise_sets.length) {
                                    var setLines = ex.exercise_sets.map(function(s) {
                                        var t = 'Set ' + s.set_number + ': ' + (s.reps != null ? s.reps : '-') + ' reps';
                                        if (s.weight != null) { t += ' @ ' + s.weight; }
                                        return t;
                                    });
                                    detailItems.push({ text: 'Sets', ul: setLines });
                                }
                                if (ex.notes) { detailItems.push('Notes: ' + ex.notes); }
                                if (detailItems.length) {
                                    return { text: title, ul: detailItems };
                                } else {
                                    return title;
                                }
                            });
                            var circuitNode = { text: circuitText };
                            if (circuit.description) { exerciseItems.unshift(circuit.description); }
                            if (exerciseItems.length) { circuitNode.ul = exerciseItems; }
                            return circuitNode;
                        });
                        var dayNode = { text: dayText };
                        var dayUl = [];
                        if (day.description) { dayUl.push(day.description); }
                        dayUl = dayUl.concat(circuitItems);
                        var customRows = Array.isArray(day.custom_rows) ? day.custom_rows : [];
                        if (customRows.length) { dayUl.push({ text: 'Custom Rows', ul: customRows }); }
                        if (day.cool_down) { dayUl.push({ text: 'Cool Down: ' + day.cool_down, style: 'coolDown' }); }
                        if (dayUl.length) { dayNode.ul = dayUl; }
                        return dayNode;
                    });
                    var weekNode = { text: weekText };
                    var weekUl = [];
                    if (week.description) { weekUl.push(week.description); }
                    weekUl = weekUl.concat(dayItems);
                    if (weekUl.length) { weekNode.ul = weekUl; }
                    return weekNode;
                });
                content.push({ ul: weekItems });
            }
            return {
                content: content,
                styles: {
                    title: { fontSize: 18, bold: true },
                    coolDown: { italics: true, color: '#666666' }
                },
                defaultStyle: { fontSize: 10 }
            };
        }
    </script>
@endsection