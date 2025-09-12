@extends('layouts.master')

@section('styles')
<style>
.settings-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f3f4;
}
.setting-item:last-child {
    border-bottom: none;
}
.setting-info {
    flex: 1;
}
.setting-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.setting-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}
.setting-control {
    margin-left: 1rem;
}
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider {
    background-color: #007bff;
}
input:checked + .slider:before {
    transform: translateX(26px);
}
.danger-zone {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
}
</style>
@endsection

@section('content')

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile.index') }}">Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Settings</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Account Settings</h1>
    </div>
    <div class="btn-list">
        <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Back to Profile
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Display Success Messages -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-circle-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Privacy & Security Settings -->
    <div class="col-xl-6 col-lg-12">
        <div class="settings-card">
            <h5 class="mb-4"><i class="ri-shield-check-line me-2"></i>Privacy & Security</h5>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Email Notifications</div>
                    <p class="setting-description">Receive email notifications for account activities</p>
                </div>
                <div class="setting-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="emailNotifications" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Two-Factor Authentication</div>
                    <p class="setting-description">Add an extra layer of security to your account</p>
                </div>
                <div class="setting-control">
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="ri-shield-keyhole-line me-1"></i>Enable 2FA
                    </button>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Login Alerts</div>
                    <p class="setting-description">Get notified when someone logs into your account</p>
                </div>
                <div class="setting-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="loginAlerts" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Profile Visibility</div>
                    <p class="setting-description">Control who can see your profile information</p>
                </div>
                <div class="setting-control">
                    <select class="form-select form-select-sm" style="width: auto;">
                        <option value="public">Public</option>
                        <option value="private" selected>Private</option>
                        <option value="team">Team Only</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification Preferences -->
    <div class="col-xl-6 col-lg-12">
        <div class="settings-card">
            <h5 class="mb-4"><i class="ri-notification-3-line me-2"></i>Notification Preferences</h5>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Password Reset Notifications</div>
                    <p class="setting-description">Get notified when password reset is requested</p>
                </div>
                <div class="setting-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="passwordResetNotif" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Account Updates</div>
                    <p class="setting-description">Receive notifications about account changes</p>
                </div>
                <div class="setting-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="accountUpdates" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Marketing Emails</div>
                    <p class="setting-description">Receive promotional emails and updates</p>
                </div>
                <div class="setting-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="marketingEmails">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Weekly Reports</div>
                    <p class="setting-description">Get weekly activity summaries via email</p>
                </div>
                <div class="setting-control">
                    <label class="toggle-switch">
                        <input type="checkbox" id="weeklyReports">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Account Information -->
    <div class="col-xl-6 col-lg-12">
        <div class="settings-card">
            <h5 class="mb-4"><i class="ri-user-settings-line me-2"></i>Account Information</h5>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Account Status</div>
                    <p class="setting-description">Your account is active and verified</p>
                </div>
                <div class="setting-control">
                    <span class="badge bg-success">Active</span>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Member Since</div>
                    <p class="setting-description">{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y') }}</p>
                </div>
                <div class="setting-control">
                    <span class="text-muted">{{ \Carbon\Carbon::parse($user->created_at)->diffForHumans() }}</span>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Last Login</div>
                    <p class="setting-description">Track your recent account access</p>
                </div>
                <div class="setting-control">
                    <span class="text-muted">{{ now()->format('M d, Y g:i A') }}</span>
                </div>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <div class="setting-title">Data Export</div>
                    <p class="setting-description">Download a copy of your account data</p>
                </div>
                <div class="setting-control">
                    <button class="btn btn-outline-info btn-sm">
                        <i class="ri-download-line me-1"></i>Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-xl-6 col-lg-12">
        <div class="settings-card">
            <h5 class="mb-4"><i class="ri-tools-line me-2"></i>Quick Actions</h5>
            
            <div class="d-grid gap-2">
                <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                    <i class="ri-edit-line me-2"></i>Edit Profile Information
                </a>
                
                <a href="{{ route('profile.change-password') }}" class="btn btn-outline-secondary">
                    <i class="ri-lock-password-line me-2"></i>Change Password
                </a>
                
                <button class="btn btn-outline-info" onclick="clearCache()">
                    <i class="ri-refresh-line me-2"></i>Clear Browser Cache
                </button>
                
                <a href="{{ route('profile.activity-log') }}" class="btn btn-outline-warning">
                    <i class="ri-history-line me-2"></i>View Activity Log
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Danger Zone -->
<div class="row">
    <div class="col-12">
        <div class="danger-zone">
            <h5 class="mb-3"><i class="ri-error-warning-line me-2"></i>Danger Zone</h5>
            <p class="mb-3">These actions are irreversible. Please proceed with caution.</p>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-light" onclick="deactivateAccount()">
                        <i class="ri-user-unfollow-line me-2"></i>Deactivate Account
                    </button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-light text-danger" onclick="deleteAccount()">
                        <i class="ri-delete-bin-line me-2"></i>Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Save Settings Function
function saveSettings() {
    // Collect all toggle states
    const settings = {
        emailNotifications: document.getElementById('emailNotifications').checked,
        loginAlerts: document.getElementById('loginAlerts').checked,
        passwordResetNotif: document.getElementById('passwordResetNotif').checked,
        accountUpdates: document.getElementById('accountUpdates').checked,
        marketingEmails: document.getElementById('marketingEmails').checked,
        weeklyReports: document.getElementById('weeklyReports').checked
    };
    
    // Here you would typically send an AJAX request to save settings
    console.log('Settings to save:', settings);
    
    // Show success message
    showNotification('Settings saved successfully!', 'success');
}

// Auto-save when toggles change
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('input[type="checkbox"]');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            // Auto-save after a short delay
            setTimeout(saveSettings, 500);
        });
    });
});

// Clear Cache Function
function clearCache() {
    if (confirm('Are you sure you want to clear your browser cache? This will log you out and you\'ll need to sign in again.')) {
        // Clear localStorage and sessionStorage
        localStorage.clear();
        sessionStorage.clear();
        
        // Show success message
        showNotification('Cache cleared successfully! Redirecting to login...', 'info');
        
        // Redirect to login after a delay
        setTimeout(() => {
            window.location.href = '{{ route("login") }}';
        }, 2000);
    }
}

// Deactivate Account Function
function deactivateAccount() {
    const confirmation = prompt('To deactivate your account, please type "DEACTIVATE" (in capital letters):');
    
    if (confirmation === 'DEACTIVATE') {
        if (confirm('Are you absolutely sure you want to deactivate your account? This action can be reversed by contacting support.')) {
            // Here you would send a request to deactivate the account
            showNotification('Account deactivation request submitted. You will be contacted by support.', 'warning');
        }
    } else if (confirmation !== null) {
        showNotification('Incorrect confirmation text. Account deactivation cancelled.', 'error');
    }
}

// Delete Account Function
function deleteAccount() {
    const confirmation = prompt('To permanently delete your account, please type "DELETE FOREVER" (in capital letters):');
    
    if (confirmation === 'DELETE FOREVER') {
        if (confirm('⚠️ FINAL WARNING: This will permanently delete your account and all associated data. This action CANNOT be undone. Are you absolutely sure?')) {
            // Here you would send a request to delete the account
            showNotification('Account deletion is not implemented in this demo for safety reasons.', 'info');
        }
    } else if (confirmation !== null) {
        showNotification('Incorrect confirmation text. Account deletion cancelled.', 'error');
    }
}

// Show Notification Function
function showNotification(message, type = 'info') {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Insert at the top of the content
    const content = document.querySelector('.main-content .container-fluid');
    content.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        const alert = content.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// Auto-hide existing alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
@endsection