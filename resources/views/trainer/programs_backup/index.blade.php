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
            <p class="mb-0 text-muted">Create and manage your workout programs</p>
        </div>
        <a href="{{ route('trainer.programs.create') }}" class="btn btn-primary">
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
                                Template Programs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="template-programs">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                                Total Weeks
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-weeks">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
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

    <!-- PDFMake -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/vfs_fonts.min.js"></script>

    <script>
        let programsTable;

        $(document).ready(function() {
            // Initialize DataTable
            programsTable = $('#programsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('trainer.programs.index') }}",
                    type: "GET",
                    error: function(xhr, status, error) {
                        console.error('DataTables Error:', error);
                        console.error('Response:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'client', name: 'client' },
                    { data: 'duration', name: 'duration' },
                    { data: 'weeks_count', name: 'weeks_count', orderable: false, searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false, render: function(data) {
                        return data;
                    }}
                ],
                order: [[6, 'desc']],
                pageLength: 25,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
                }
            });

            // Load statistics
            loadStatistics();

            // PDF handlers
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
                url: "{{ route('trainer.programs.stats') }}",
                type: 'GET',
                success: function(data) {
                    $('#total-programs').text(data.total_programs);
                    $('#active-programs').text(data.active_programs);
                    $('#template-programs').text(data.template_programs);
                    $('#total-weeks').text(data.total_weeks);
                },
                error: function(xhr) {
                    console.error('Error loading statistics:', xhr);
                    $('#total-programs, #active-programs, #template-programs, #total-weeks').html('<span class="text-danger">Error</span>');
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
                        url: `/trainer/programs/${id}`,
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
                                programsTable.ajax.reload();
                                loadStatistics();
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr) {
                            console.error('Error deleting program:', xhr);
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
                    url: '/trainer/programs/' + id + '/pdf-data',
                    type: 'GET',
                    success: function(resp) {
                        if (resp && resp.success && resp.data) {
                            resolve(resp.data);
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
                    { width: 'auto', text: 'Created: ' + (program.created_at || 'N/A') }
                ]
            });
            content.push({ text: '' });

            // Weeks
            if (program.weeks && program.weeks.length > 0) {
                program.weeks.forEach(function(week, wIndex) {
                    content.push({ text: 'Week ' + (wIndex + 1), style: 'heading2', pageBreak: 'before' });
                    
                    if (week.days && week.days.length > 0) {
                        week.days.forEach(function(day) {
                            content.push({ text: day.name, style: 'heading3' });
                            
                            if (day.circuits && day.circuits.length > 0) {
                                var circuitTable = [];
                                day.circuits.forEach(function(circuit) {
                                    var exercises = (circuit.exercises || []).map(function(ex) {
                                        return ex.name + (ex.sets ? ' (' + ex.sets + ' sets)' : '');
                                    }).join(', ');
                                    circuitTable.push([
                                        'Circuit ' + circuit.circuit_number,
                                        exercises || 'No exercises'
                                    ]);
                                });
                                content.push({
                                    table: {
                                        headerRows: 0,
                                        widths: ['20%', '80%'],
                                        body: circuitTable
                                    },
                                    margin: [0, 5, 0, 15]
                                });
                            }
                        });
                    }
                });
            }

            return {
                content: content,
                styles: {
                    title: { fontSize: 24, bold: true, margin: [0, 0, 0, 10] },
                    heading2: { fontSize: 14, bold: true, margin: [0, 10, 0, 5], fillColor: '#e9ecef' },
                    heading3: { fontSize: 12, bold: true, margin: [0, 8, 0, 3], color: '#495057' }
                },
                defaultStyle: { fontSize: 10 }
            };
        }
    </script>
@endsection
