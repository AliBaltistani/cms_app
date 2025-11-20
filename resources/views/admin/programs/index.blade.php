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
                    var logoUrl = businessLogoUrl(program);
                    var logoPromise = logoUrl ? getBase64ImageFromURL(logoUrl) : Promise.resolve(null);
                    logoPromise.then(function(logoData) {
                        var doc = buildProgramPdfDoc(program, logoData);
                        pdfMake.createPdf(doc).open();
                    }).catch(function() {
                        var doc = buildProgramPdfDoc(program, null);
                        pdfMake.createPdf(doc).open();
                    });
                }).catch(function() {
                    Swal.fire('Error', 'Failed to generate PDF', 'error');
                });
            });

            $(document).on('click', '.program-pdf-download', function() {
                var id = $(this).data('program-id');
                fetchProgramPdfData(id).then(function(program) {
                    var logoUrl = businessLogoUrl(program);
                    var logoPromise = logoUrl ? getBase64ImageFromURL(logoUrl) : Promise.resolve(null);
                    logoPromise.then(function(logoData) {
                        var doc = buildProgramPdfDoc(program, logoData);
                        var filename = (program.name || 'program') + '-' + program.id + '.pdf';
                        pdfMake.createPdf(doc).download(filename);
                    }).catch(function() {
                        var doc = buildProgramPdfDoc(program, null);
                        var filename = (program.name || 'program') + '-' + program.id + '.pdf';
                        pdfMake.createPdf(doc).download(filename);
                    });
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

        function getBase64ImageFromURL(url) {
            return new Promise(function(resolve, reject) {
                try {
                    var img = new Image();
                    img.crossOrigin = 'Anonymous';
                    img.onload = function() {
                        var canvas = document.createElement('canvas');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0);
                        resolve(canvas.toDataURL('image/png'));
                    };
                    img.onerror = function() { resolve(null); };
                    img.src = url;
                } catch (e) {
                    resolve(null);
                }
            });
        }

        function businessLogoUrl(program) {
            var p = program && program.trainer && program.trainer.business_logo;
            if (!p) { return null; }
            if (p.indexOf('/storage/') === 0) { return p; }
            return '/storage/' + p;
        }

        function buildProgramPdfDoc(program, logoDataUrl) {
            var content = [];
            if (logoDataUrl) {
                content.push({ image: logoDataUrl, width: 80, alignment: 'center', margin: [0, 0, 0, 8] });
            }
            content.push({ text: (program.name || 'Program'), style: 'title', alignment: 'center' });
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
            if (Array.isArray(program.weeks) && program.weeks.length) {
                program.weeks.forEach(function(week) {
                    var weekText = 'Week ' + week.week_number + (week.title ? ' – ' + week.title : '');
                    var weekStack = [{ text: weekText, style: 'weekTitle', margin: [0, 8, 0, 4] }];
                    if (week.description) {
                        weekStack.push({ text: week.description, margin: [0, 0, 0, 6] });
                    }
                    (Array.isArray(week.days) ? week.days : []).forEach(function(day) {
                        var dayText = 'Day ' + day.day_number + (day.title ? ' – ' + day.title : '');
                        var dayStack = [{ text: dayText, style: 'dayTitle', margin: [0, 6, 0, 4] }];
                        if (day.description) {
                            dayStack.push({ text: day.description, margin: [0, 0, 0, 4] });
                        }
                        (Array.isArray(day.circuits) ? day.circuits : []).forEach(function(circuit) {
                            var circuitText = 'Circuit ' + circuit.circuit_number + (circuit.title ? ' – ' + circuit.title : '');
                            dayStack.push({ text: circuitText, style: 'circuitTitle', margin: [0, 4, 0, 2] });
                            if (circuit.description) {
                                dayStack.push({ text: circuit.description, margin: [0, 0, 0, 4] });
                            }
                            (Array.isArray(circuit.program_exercises) ? circuit.program_exercises : []).forEach(function(ex) {
                                var title = ex.name || (ex.workout && (ex.workout.name || ex.workout.title)) || 'Exercise';
                                var exStack = [{ text: title, style: 'exerciseTitle' }];
                                var metaLine = [];
                                if (ex.tempo) { metaLine.push('Tempo: ' + ex.tempo); }
                                if (ex.rest_interval) { metaLine.push('Rest: ' + ex.rest_interval); }
                                if (metaLine.length) {
                                    exStack.push({ text: metaLine.join('  |  '), style: 'metaLine', margin: [0, 2, 0, 2] });
                                }
                                var sets = Array.isArray(ex.exercise_sets) ? ex.exercise_sets : [];
                                if (sets.length) {
                                    var body = [ ['Set', 'Reps', 'Weight'] ];
                                    sets.forEach(function(s) {
                                        body.push([
                                            'Set ' + (s.set_number != null ? s.set_number : ''),
                                            (s.reps != null ? String(s.reps) : ''),
                                            (s.weight != null ? String(s.weight) : '')
                                        ]);
                                    });
                                    exStack.push({
                                        table: { headerRows: 1, widths: ['auto','auto','*'], body: body },
                                        layout: 'lightHorizontalLines',
                                        margin: [0, 2, 0, 2]
                                    });
                                }
                                if (ex.notes) {
                                    exStack.push({ text: 'Notes: ' + ex.notes, style: 'notes', margin: [0, 2, 0, 6] });
                                } else {
                                    exStack.push({ text: '', margin: [0, 0, 0, 6] });
                                }
                                dayStack.push({ stack: exStack });
                            });
                        });
                        var customRows = Array.isArray(day.custom_rows) ? day.custom_rows : [];
                        if (customRows.length) {
                            dayStack.push({ text: 'Custom Rows', style: 'customHeader', margin: [0, 4, 0, 2] });
                            dayStack.push({ ul: customRows, margin: [0, 0, 0, 4] });
                        }
                        if (day.cool_down) {
                            dayStack.push({ text: 'Cool Down: ' + day.cool_down, style: 'coolDown', margin: [0, 2, 0, 6] });
                        }
                        weekStack.push({ stack: dayStack });
                    });
                    content.push({ stack: weekStack });
                });
            }
            return {
                content: content,
                styles: {
                    title: { fontSize: 18, bold: true },
                    weekTitle: { fontSize: 13, bold: true },
                    dayTitle: { fontSize: 12, bold: true },
                    circuitTitle: { fontSize: 11, bold: true },
                    exerciseTitle: { fontSize: 10, bold: true },
                    metaLine: { fontSize: 9, color: '#444444' },
                    notes: { fontSize: 9, italics: true },
                    coolDown: { italics: true, color: '#666666' },
                    customHeader: { bold: true }
                },
                defaultStyle: { fontSize: 10 }
            };
        }
    </script>
@endsection