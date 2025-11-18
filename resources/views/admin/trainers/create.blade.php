@extends('layouts.master')

@section('styles')
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond/filepond.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css')}}">
@endsection

@section('scripts')
<script src="{{asset('build/assets/libs/filepond/filepond.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-crop/filepond-plugin-image-crop.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-resize/filepond-plugin-image-resize.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-transform/filepond-plugin-image-transform.min.js')}}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateSize,
        FilePondPluginFileEncode,
        FilePondPluginImageEdit,
        FilePondPluginFileValidateType,
        FilePondPluginImageCrop,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );

    const profileImageInput = document.querySelector('#profile_image');
    if (profileImageInput) {
        FilePond.create(profileImageInput, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
            maxFileSize: '2MB',
            imagePreviewHeight: 120,
            imageCropAspectRatio: '1:1',
            imageResizeTargetWidth: 200,
            imageResizeTargetHeight: 200,
            stylePanelLayout: 'compact',
            labelIdle: 'Drag & Drop profile image or <span class="filepond--label-action">Browse</span>',
        });
    }

    const businessLogoInput = document.querySelector('#business_logo');
    if (businessLogoInput) {
        FilePond.create(businessLogoInput, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
            maxFileSize: '2MB',
            imagePreviewHeight: 120,
            imageCropAspectRatio: '1:1',
            imageResizeTargetWidth: 300,
            imageResizeTargetHeight: 300,
            stylePanelLayout: 'compact',
            labelIdle: 'Drag & Drop business logo or <span class="filepond--label-action">Browse</span>',
        });
    }
});
</script>
@endsection

@section('content')
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Add New Trainer</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.trainers.index') }}">Trainers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-primary btn-wave">
            <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to List
        </a>
    </div>
    </div>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Trainer Information</div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trainers.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-xl-12 mb-4">
                            <div class="text-center">
                                <label class="form-label">Profile Image</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                <small class="text-muted d-block mt-2">Upload a profile image (JPEG, PNG, JPG, GIF). Max size: 2MB</small>
                                @error('profile_image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-xl-12 mb-4">
                            <div class="text-center">
                                <label class="form-label">Business Logo</label>
                                <input type="file" id="business_logo" name="business_logo" accept="image/*">
                                <small class="text-muted d-block mt-2">Upload a business logo (JPEG, PNG, JPG, GIF). Max size: 2MB</small>
                                @error('business_logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter full name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter email address" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation') }}" placeholder="Enter designation" required>
                            @error('designation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="experience" class="form-label">Experience (Years) <span class="text-danger">*</span></label>
                            <select class="form-select @error('experience') is-invalid @enderror" id="experience" name="experience" required>
                                <option value="">Select Experience Level</option>
                                <option value="less_than_1_year" {{ old('experience') == 'less_than_1_year' ? 'selected' : '' }}>Less than 1 year</option>
                                <option value="1_year" {{ old('experience') == '1_year' ? 'selected' : '' }}>1 year</option>
                                <option value="2_years" {{ old('experience') == '2_years' ? 'selected' : '' }}>2 years</option>
                                <option value="3_years" {{ old('experience') == '3_years' ? 'selected' : '' }}>3 years</option>
                                <option value="4_years" {{ old('experience') == '4_years' ? 'selected' : '' }}>4 years</option>
                                <option value="5_years" {{ old('experience') == '5_years' ? 'selected' : '' }}>5 years</option>
                                <option value="6_years" {{ old('experience') == '6_years' ? 'selected' : '' }}>6 years</option>
                                <option value="7_years" {{ old('experience') == '7_years' ? 'selected' : '' }}>7 years</option>
                                <option value="8_years" {{ old('experience') == '8_years' ? 'selected' : '' }}>8 years</option>
                                <option value="9_years" {{ old('experience') == '9_years' ? 'selected' : '' }}>9 years</option>
                                <option value="10_years" {{ old('experience') == '10_years' ? 'selected' : '' }}>10 years</option>
                                <option value="more_than_10_years" {{ old('experience') == 'more_than_10_years' ? 'selected' : '' }}>More than 10 years</option>
                            </select>
                            @error('experience')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-12 mb-3">
                            <label for="about" class="form-label">About <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('about') is-invalid @enderror" id="about" name="about" rows="4" placeholder="Tell us about the trainer" required>{{ old('about') }}</textarea>
                            @error('about')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-12 mb-3">
                            <label for="training_philosophy" class="form-label">Training Philosophy</label>
                            <textarea class="form-control @error('training_philosophy') is-invalid @enderror" id="training_philosophy" name="training_philosophy" rows="3" placeholder="Training philosophy (optional)">{{ old('training_philosophy') }}</textarea>
                            @error('training_philosophy')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-12 mb-3">
                            <label for="specializations" class="form-label">Specializations</label>
                            <select class="form-select @error('specializations') is-invalid @enderror" id="specializations" name="specializations">
                                <option value="" disabled>Select Specialization</option>
                                @php $specializations = \App\Models\Specialization::where('status', 1)->orderBy('name')->get(); @endphp
                                @foreach($specializations as $specialization)
                                    <option value="{{ $specialization->id }}" {{ old('specializations') == $specialization->id ? 'selected' : '' }}>
                                        {{ $specialization->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('specializations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="(function(){const f=document.getElementById('password');f.type=f.type==='password'?'text':'password';})();"><i class="ri-eye-line"></i></button>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-xl-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm password" required>
                        </div>

                        <div class="col-xl-12">
                            <button type="submit" class="btn btn-primary">Create Trainer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection