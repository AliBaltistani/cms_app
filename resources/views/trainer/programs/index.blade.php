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
                    url: "{{ route('trainer.programs.index') }}",
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
                columnDefs: [
                    { targets: 2, visible: false }
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
                window.open('/trainer/programs/' + id + '/pdf-view', '_blank');
            });

            $(document).on('click', '.program-pdf-download', function() {
                var id = $(this).data('program-id');
                fetchProgramPdfUrl(id).then(function(url) {
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
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

        function fetchProgramPdfUrl(id) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: '/trainer/programs/' + id + '/pdf-data',
                    type: 'GET',
                    success: function(resp) {
                        if (resp && resp.success && resp.data && resp.data.pdf_view_url) {
                            resolve(resp.data.pdf_view_url);
                        } else {
                            reject();
                        }
                    },
                    error: function() { reject(); }
                });
            });
        }

        
    </script>
@endsection