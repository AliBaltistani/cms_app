@extends('layouts.master')

@section('content')
<form method="POST" action="{{ route('goals.update', $goal->id) }}">
                    @csrf
                    
  <div class="row">
                        <div class="col-xl-12">
                            <div class="card custom-card">
                                <div class="card-header justify-content-between">
                                    <div class="card-title">
                                        Goal Edit
                                    </div>
                                    <div class="prism-toggle">
                                        <a href="{{route('goals.index')}}" class="btn btn-sm btn-primary-light"> <i class=" "></i> back</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text"  class="form-control @error('name') is-invalid @enderror" name="name" placeholder="Enter goal name" aria-label="Name" value="{{ $goal->name  }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                       <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                                    <select id="inputState1" class="form-select @error('status') is-invalid @enderror" name="status" required>
                                                        <option selected="" disabled >Select status</option>
                                                        <option value="1" {{ $goal->status == 1 ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ $goal->status == 0 ? 'selected' : '' }}> Inactive</option>
                                                    </select>
                                                    @error('status')
                                                        <div class="invalid-feedback">
                                                            {{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>
                                                
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
</form>
@endsection