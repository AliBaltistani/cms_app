
@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="page-header-breadcrumb mb-3">
                        <div class="d-flex align-center justify-content-between flex-wrap">
                            <h1 class="page-title fw-medium fs-18 mb-0">Full Calendar</h1>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Apps</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Full Calendar</li>
                            </ol>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <div class="row">
                        <div class="col-xxl-3">
                            <div class="card custom-card">
                            <div class="card-header justify-content-between">
                                <div class="card-title">All Events</div>
                                <button class="btn btn-primary btn-wave" data-bs-toggle="modal"
                                data-bs-target="#addEvent"><i class="ri-add-line align-middle me-1 fw-medium d-inline-block"></i>Create New Event</button>
                            </div>
                            <div class="card-body">
                                <div id="external-events">
                                    <div class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-primary">
                                        <div class="fc-event-main text-fixed-white">Calendar Events</div>
                                    </div>
                                    <div
                                        class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-secondary"
                                        data-class="bg-secondary">
                                        <div class="fc-event-main text-fixed-white">Birthday Events</div>
                                    </div>
                                    <div class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-success"
                                        data-class="bg-success text-success">
                                        <div class="fc-event-main text-fixed-white">Holiday Calendar</div>
                                    </div>
                                    <div class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-info"
                                        data-class="bg-info text-info">
                                        <div class="fc-event-main text-fixed-white">Office Events</div>
                                    </div>
                                    <div
                                        class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-warning"
                                        data-class="bg-warning">
                                        <div class="fc-event-main text-fixed-white">Other Events</div>
                                    </div>
                                    <div class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-danger"
                                        data-class="bg-danger">
                                        <div class="fc-event-main text-fixed-white">Festival Events</div>
                                    </div>
                                    <div class="fc-event fc-h-event fc-daygrid-event fc-daygrid-block-event bg-teal"
                                        data-class="bg-teal">
                                        <div class="fc-event-main text-fixed-white">Timeline Events</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <div class="card custom-card">
                            <div class="card-body p-0">
                                <div class="p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="fw-medium mb-0">
                                    Upcoming Events
                                    </h6>
                                    <a href="javascript:void(0);" class="fs-13 text-muted text-decoration-underline">View All<i class="ti ti-arrow-narrow-right"></i></a>
                                </div>
                                </div>
                                <div class="p-3" id="full-calendar-activity">
                                <ul class="list-unstyled mb-0 fullcalendar-events-activity">
                                    <li>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <p class="mb-1 fw-medium">
                                        Annual School Day
                                        </p>
                                        <span class="badge bg-primary mb-1">02 Mar, 2025</span>
                                    </div>
                                    <p class="mb-0 text-muted fs-13">
                                        A celebration of the school year with various events and activities for students and staff.
                                    </p>
                                    </li>
                                    <li>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <p class="mb-1 fw-medium">
                                        Science Fair
                                        </p>
                                        <span class="badge bg-secondary mb-1">17 Mar, 2025</span>
                                    </div>
                                    <p class="mb-0 text-muted fs-13">
                                        Students will showcase their science projects. Open to all parents and students.
                                    </p>
                                    </li>
                                    <li>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <p class="mb-1 fw-medium">
                                        Parent-Teacher Meeting
                                        </p>
                                        <span class="badge bg-warning mb-1">15 Mar, 2025</span>
                                    </div>
                                    <p class="mb-0 text-muted fs-13">
                                        An important event where parents meet teachers to discuss the progress of their children.
                                    </p>
                                    </li>
                                    <li>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <p class="mb-1 fw-medium">
                                        Spring Break
                                        </p>
                                        <span class="badge bg-info mb-1">13 Mar,2025</span>
                                    </div>
                                    <p class="mb-0 text-muted fs-13">
                                        The students get a break for the spring holidays. No school during this period.
                                    </p>
                                    </li>
                                    <li>
                                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                                        <p class="mb-1 fw-medium">
                                        Holiday Celebrations
                                        </p>
                                        <span class="badge bg-success mb-1">Due Date</span>
                                    </div>
                                    <p class="mb-0 text-muted fs-13">
                                        Celebrating the upcoming national holiday with various cultural activities and festivities.
                                    </p>
                                    </li>
                                </ul>
                                </div>
                            </div>
                            </div>
                        </div>
                        <div class="col-xxl-9">
                            <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">Full Calendar</div>
                            </div>
                            <div class="card-body">
                                <div id='calendar'></div>
                            </div>
                        </div>
                        </div>
                    </div>
                    <!--End::row-1 -->

                    <!-- Add Event Modal -->
                    <div class="modal fade" id="addEvent" tabindex="-1" aria-labelledby="addEventLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="addEventLabel1">Add Event</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row gy-3">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="form-label" for="eventType">Event Type:</label>
                                                <select class="form-control" data-trigger id="eventType">
                                                    <option value="bg-primary">Primary</option>
                                                    <option value="bg-secondary">Secondary</option>
                                                    <option value="bg-success">Success</option>
                                                    <option value="bg-info">Info</option>
                                                    <option value="bg-warning">Warning</option>
                                                    <option value="bg-danger">Danger</option>
                                                    <option value="bg-teal">Teal</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="form-label" for="eventName">Event Name:</label>
                                                <input type="text" class="form-control" placeholder="Enter event" id="eventName">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="fromDate">From:</label>
                                                <input type="text" class="form-control" id="fromDate" placeholder="From Date">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="toDate">To:</label>
                                                <input type="text" class="form-control" id="toDate" placeholder="To Date">
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <div class="form-group">
                                                <label class="form-label" for="description">Description:</label>
                                                <textarea class="form-control" id="event-description" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" id="addEventButton">Add Event</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Add Event Modal -->

                    <!-- Event Details Modal -->
                    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-body p-5">
                                    <div id="eventDetails">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <h5 class="fw-semibold mb-0" id="modalEventName"></h5>
                                            <span class="badge bg-success-transparent">Full Day</span>
                                        </div>
                                        <span class="d-block text-muted mb-3" id="modalEventDescription"></span>
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="fs-10 text-muted lh-1">
                                                <i class="ti ti-circle align-middle text-success"></i>
                                            </div>
                                            <span class="fw-semibold" id="modalEventStart"></span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-0">
                                            <div class="fs-10 text-muted lh-1">
                                            <i class="ti ti-circle align-middle text-danger"></i>
                                            </div>
                                            <span class="fw-semibold" id="modalEventEnd"></span>
                                        </div>
                                    </div>
                                    <div class="btn-list float-end mt-3">
                                        <button type="button" class="btn btn-danger" id="deleteEventButton"><i class="ti ti-trash me-2"></i>Delete</button>
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i class="ti ti-x me-2 align-middle"></i>Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Event Modal -->

@endsection

@section('scripts')
	
        <!-- Moment JS -->
        <script src="{{asset('build/assets/libs/moment/min/moment.min.js')}}"></script>

        <!-- Fullcalendar JS -->
        <script src="{{asset('build/assets/libs/fullcalendar/index.global.min.js')}}"></script>
        @vite('resources/assets/js/fullcalendar.js')

@endsection
