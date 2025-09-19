@extends('layouts.master')

@section('content')
  
<div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Workouts list
                                    </div>
                                    <div class="prism-toggle">
                                        <a href="{{ route('workouts.create')}}" class="btn btn-sm btn-primary-light">Add New</a>
                                    </div>
                                </div>
                                    <!-- Display Success Messages -->
                                    @if (session('success') || session('error'))
                                        <div class="card-body">
                                            @if(session('success'))
                                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
                                        </div>
                                    @endif
                                    @if(session('error'))
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <strong>Error!</strong> {{ session('error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
                                        </div>
                                    @endif
                                </div>
                                  @endif
                                

                                 <!-- Display Success Messages -->
                                <div class="card-body">
                                    <!-- Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <select class="form-select" id="statusFilter">
                                                <option value="">All Status</option>
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" id="trainerFilter">
                                                <option value="">All Trainers</option>
                                                @foreach($trainers as $trainer)
                                                    <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" id="searchFilter" placeholder="Search workouts...">
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">ID</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Trainer</th>
                                                    <th scope="col">Duration</th>
                                                    <th scope="col">Price</th>
                                                    <th scope="col">Videos</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Created At</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ( $workouts  as  $workout )
                                                <tr>
                                                    <th scope="row">{{ $loop->iteration }}</th>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($workout->thumbnail)
                                                                <img src="{{ Storage::url($workout->thumbnail) }}" alt="thumbnail" class="rounded me-2" width="40" height="40">
                                                            @endif
                                                            <div>
                                                                <strong>{{ $workout->name }}</strong>
                                                                @if($workout->description)
                                                                    <br><small class="text-muted">{{ Str::limit($workout->description, 50) }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($workout->user && $workout->user->profile_image)
                                                                <img src="{{ asset('storage/' . $workout->user->profile_image) }}" alt="trainer" class="rounded-circle me-2" width="30" height="30">
                                                            @else
                                                                <div class="avatar avatar-sm rounded-circle me-2 bg-primary-transparent">
                                                                    <i class="ri-user-line"></i>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <strong>{{ $workout->user->name ?? 'Unknown' }}</strong>
                                                                <br><small class="text-muted">{{ $workout->user->email ?? 'N/A' }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info-transparent">{{ $workout->formatted_duration }}</span>
                                                        @if($workout->repetitions > 1)
                                                            <br><small class="text-muted">{{ $workout->repetitions }} reps</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success-transparent">{{ $workout->formatted_price }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary-transparent">{{ $workout->videos->count() }} videos</span>
                                                    </td>
                                                    <td>{!! $workout->is_active ? '<span class="badge bg-success-transparent">Active</span>' : '<span class="badge bg-light text-dark">Inactive</span>' !!}</td>
                                                    <td>{{ $workout->created_at->format('d-m-Y') }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('workouts.show', $workout->id) }}" class="btn btn-sm btn-info btn-wave waves-effect waves-light">
                                                                <i class="ri-eye-line align-middle me-1 d-inline-block"></i>View
                                                            </a>
                                                            <a href="{{ route('workouts.edit', $workout->id) }}" class="btn btn-sm btn-success btn-wave waves-effect waves-light">
                                                                <i class="ri-edit-2-line align-middle me-1 d-inline-block"></i>Edit
                                                            </a>
                                                            <form action="{{ route('workouts.destroy', $workout->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this workout?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger btn-wave waves-effect waves-light">
                                                                    <i class="ri-delete-bin-5-line align-middle me-1 d-inline-block"></i>Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    @if($workouts->hasPages())
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div>
                                                Showing {{ $workouts->firstItem() }} to {{ $workouts->lastItem() }} of {{ $workouts->total() }} results
                                            </div>
                                            <div>
                                                {{ $workouts->links('pagination::bootstrap-5') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
@endsection