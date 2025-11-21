@extends('layouts.master')

@section('content')
  
<div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Goals list
                                    </div>
                                    <div class="prism-toggle">
                                        <a href="{{ route('goals.create')}}" class="btn btn-sm btn-primary-light">Add New</a>
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
                                    <div class="table-responsive">
                                        <table class="table text-nowrap table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">ID</th>
                                                    <th scope="col">name</th>
                                                    <th scope="col">status</th>
                                                    <th scope="col">Created At</th>
                                                    <th scope="col">Updated At</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ( $goals  as  $goal )
                                                <tr>
                                                    <th scope="row">{{ $loop->iteration }}</th>
                                                    <td>{{ $goal->name }}</td>
                                                    <td>{!! $goal->status ? '<span class="badge bg-success-transparent">Active</span>' : '<span class="badge bg-light text-dark">Inactive</span>' !!}</td>
                                                    <td>{{ $goal->created_at->format('d-m-Y') }}</td>
                                                    <td>{{ $goal->updated_at->format('d-m-Y') }}</td>
                                                    <td>
                                                        <a href="{{ route('goals.edit', $goal->id) }}" class="btn btn-sm btn-success btn-wave waves-effect waves-light">
                                                            <i class="ri-edit-2-line align-middle me-2 d-inline-block"></i>Edit
                                                        </a>
                                                        <form action="{{ route('goals.destroy', $goal->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger btn-wave waves-effect waves-light">
                                                                <i class="ri-delete-bin-5-line align-middle me-2 d-inline-block"></i>Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
@endsection