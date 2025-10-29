
@extends('layouts.master')

@section('styles')
<style>
    .trainer-filter {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .calendar-container {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .fc-event {
        cursor: pointer;
        border-radius: 4px;
    }
    
    .fc-event:hover {
        opacity: 0.8;
    }
    
    .status-legend {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .status-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
    }
    
    .status-color {
        width: 12px;
        height: 12px;
        border-radius: 2px;
    }
    
    .modal-body .form-group {
        margin-bottom: 1rem;
    }
    
    .modal-body .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
</style>
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Trainer Schedule</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                <li class="breadcrumb-item active" aria-current="page">Schedule</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    <!-- Trainer Filter -->
    <div class="trainer-filter">
        <div class="row align-items-center">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Filter by Trainer:</label>
                <select class="form-select" id="trainerFilter">
                    <option value="">All Trainers</option>
                    @foreach($trainers as $trainer)
                        <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <div class="status-legend">
                    <div class="status-item">
                        <div class="status-color" style="background-color: #ffc107;"></div>
                        <span>Pending</span>
                    </div>
                    <div class="status-item">
                        <div class="status-color" style="background-color: #28a745;"></div>
                        <span>Confirmed</span>
                    </div>
                    <div class="status-item">
                        <div class="status-color" style="background-color: #dc3545;"></div>
                        <span>Cancelled</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-12">
            <div class="card custom-card">
                <div class="card-body calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>
    <!--End::row-1 -->

    <!-- Add/Edit Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="bookingModalLabel">Add Booking</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm">
                        <input type="hidden" id="bookingId" name="booking_id">
                        <div class="row gy-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="modalTrainer">Trainer:</label>
                                    <select class="form-select" id="modalTrainer" name="trainer_id" required>
                                        <option value="">Select Trainer</option>
                                        @foreach($trainers as $trainer)
                                            <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="modalClient">Client:</label>
                                    <select class="form-select" id="modalClient" name="client_id" required>
                                        <option value="">Select Client</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="modalStartDate">Start Date & Time:</label>
                                    <input type="datetime-local" class="form-control" id="modalStartDate" name="start" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="modalEndDate">End Date & Time:</label>
                                    <input type="datetime-local" class="form-control" id="modalEndDate" name="end" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="modalStatus">Status:</label>
                                    <select class="form-select" id="modalStatus" name="status">
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="form-group">
                                    <label class="form-label" for="modalNotes">Notes:</label>
                                    <textarea class="form-control" id="modalNotes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteBookingBtn" style="display: none;">Delete Booking</button>
                    <button type="button" class="btn btn-primary" id="saveBookingBtn">Save Booking</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="eventDetailsModalLabel">Booking Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="eventDetails">
                        <div class="mb-3">
                            <strong>Trainer:</strong> <span id="detailTrainer"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Client:</strong> <span id="detailClient"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Date:</strong> <span id="detailDate"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Time:</strong> <span id="detailTime"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong> <span id="detailStatus" class="badge"></span>
                        </div>
                        <div class="mb-3">
                            <strong>Notes:</strong> <span id="detailNotes"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" id="editBookingBtn">Edit</button>
                    <button type="button" class="btn btn-danger" id="deleteBookingBtn">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Moment JS -->
    <script src="{{asset('build/assets/libs/moment/min/moment.min.js')}}"></script>

    <!-- Fullcalendar JS -->
    <script src="{{asset('build/assets/libs/fullcalendar/index.global.min.js')}}"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const trainerFilter = document.getElementById('trainerFilter');
            const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
            const eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            
            let currentEvent = null;
            
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                weekends: true,
                
                events: function(fetchInfo, successCallback, failureCallback) {
                    const trainerId = trainerFilter.value;
                    const params = new URLSearchParams({
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr
                    });
                    
                    if (trainerId) {
                        params.append('trainer_id', trainerId);
                    }
                    
                    fetch(`{{ route('admin.bookings.events') }}?${params}`)
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => {
                            console.error('Error fetching events:', error);
                            failureCallback(error);
                        });
                },
                
                select: function(arg) {
                    openBookingModal(null, arg.start, arg.end);
                    calendar.unselect();
                },
                
                eventClick: function(arg) {
                    showEventDetails(arg.event);
                },
                
                eventDrop: function(arg) {
                    updateEventDateTime(arg.event);
                },
                
                eventResize: function(arg) {
                    updateEventDateTime(arg.event);
                }
            });
            
            calendar.render();
            
            // Trainer filter change
            trainerFilter.addEventListener('change', function() {
                calendar.refetchEvents();
            });
            
            // Open booking modal
            function openBookingModal(event = null, start = null, end = null) {
                const form = document.getElementById('bookingForm');
                form.reset();
                
                const deleteBtn = document.getElementById('deleteBookingBtn');
                
                if (event) {
                    // Edit mode
                    document.getElementById('bookingModalLabel').textContent = 'Edit Booking';
                    document.getElementById('bookingId').value = event.id;
                    document.getElementById('modalTrainer').value = event.extendedProps.trainer_id;
                    document.getElementById('modalClient').value = event.extendedProps.client_id;
                    document.getElementById('modalStartDate').value = moment(event.start).format('YYYY-MM-DDTHH:mm');
                    document.getElementById('modalEndDate').value = moment(event.end).format('YYYY-MM-DDTHH:mm');
                    document.getElementById('modalStatus').value = event.extendedProps.status;
                    document.getElementById('modalNotes').value = event.extendedProps.notes || '';
                    deleteBtn.style.display = 'inline-block'; // Show delete button in edit mode
                    currentEvent = event;
                } else {
                    // Add mode
                    document.getElementById('bookingModalLabel').textContent = 'Add Booking';
                    document.getElementById('bookingId').value = '';
                    if (start && end) {
                        document.getElementById('modalStartDate').value = moment(start).format('YYYY-MM-DDTHH:mm');
                        document.getElementById('modalEndDate').value = moment(end).format('YYYY-MM-DDTHH:mm');
                    }
                    deleteBtn.style.display = 'none'; // Hide delete button in add mode
                    currentEvent = null;
                }
                
                bookingModal.show();
            }
            
            // Show event details
            function showEventDetails(event) {
                document.getElementById('detailTrainer').textContent = event.extendedProps.trainer_name;
                document.getElementById('detailClient').textContent = event.extendedProps.client_name;
                document.getElementById('detailDate').textContent = moment(event.start).format('MMMM DD, YYYY');
                document.getElementById('detailTime').textContent = 
                    moment(event.start).format('HH:mm') + ' - ' + moment(event.end).format('HH:mm');
                
                const statusBadge = document.getElementById('detailStatus');
                statusBadge.textContent = event.extendedProps.status.charAt(0).toUpperCase() + event.extendedProps.status.slice(1);
                statusBadge.className = 'badge ' + getStatusBadgeClass(event.extendedProps.status);
                
                document.getElementById('detailNotes').textContent = event.extendedProps.notes || 'No notes';
                
                currentEvent = event;
                eventDetailsModal.show();
            }
            
            // Get status badge class
            function getStatusBadgeClass(status) {
                switch(status) {
                    case 'confirmed': return 'bg-success';
                    case 'pending': return 'bg-warning';
                    case 'cancelled': return 'bg-danger';
                    default: return 'bg-secondary';
                }
            }
            
            // Update event date/time via drag & drop or resize
            function updateEventDateTime(event) {
                const data = {
                    start: event.start.toISOString(),
                    end: event.end.toISOString()
                };
                
                fetch(`{{ route('admin.bookings.update-event', 'PLACEHOLDER') }}`.replace('PLACEHOLDER', event.id), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message);
                        calendar.refetchEvents(); // Revert changes
                    }
                })
                .catch(error => {
                    console.error('Error updating event:', error);
                    calendar.refetchEvents(); // Revert changes
                });
            }
            
            // Save booking
            document.getElementById('saveBookingBtn').addEventListener('click', function() {
                const form = document.getElementById('bookingForm');
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                
                const bookingId = document.getElementById('bookingId').value;
                const url = bookingId ? 
                    `{{ route('admin.bookings.update-event', 'PLACEHOLDER') }}`.replace('PLACEHOLDER', bookingId) : 
                    `{{ route('admin.bookings.create-event') }}`;
                const method = bookingId ? 'PUT' : 'POST';
                
                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bookingModal.hide();
                        calendar.refetchEvents();
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving booking:', error);
                    alert('Error saving booking');
                });
            });
            
            // Edit booking from details modal
            document.getElementById('editBookingBtn').addEventListener('click', function() {
                eventDetailsModal.hide();
                openBookingModal(currentEvent);
            });
            
            // Delete booking from modal
            document.querySelector('#bookingModal #deleteBookingBtn').addEventListener('click', function() {
                if (currentEvent && confirm('Are you sure you want to delete this booking? This will also remove the Google Calendar event if it exists.')) {
                    fetch(`{{ route('admin.bookings.delete-event', 'PLACEHOLDER') }}`.replace('PLACEHOLDER', currentEvent.id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            bookingModal.hide();
                            calendar.refetchEvents();
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting booking:', error);
                        alert('Error deleting booking');
                    });
                }
            });
            
            // Delete booking from details modal
            document.querySelector('#eventDetailsModal #deleteBookingBtn').addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this booking? This will also remove the Google Calendar event if it exists.')) {
                    fetch(`{{ route('admin.bookings.delete-event', 'PLACEHOLDER') }}`.replace('PLACEHOLDER', currentEvent.id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            eventDetailsModal.hide();
                            calendar.refetchEvents();
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting booking:', error);
                        alert('Error deleting booking');
                    });
                }
            });
        });
    </script>
@endsection
