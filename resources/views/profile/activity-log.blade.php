@extends('layouts.master')

@section('styles')
<style>
.activity-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid #f1f3f4;
}
.activity-item:last-child {
    border-bottom: none;
}
.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}
.activity-content {
    flex: 1;
}
.activity-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.activity-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}
.activity-time {
    color: #adb5bd;
    font-size: 0.8rem;
}
.activity-meta {
    text-align: right;
    flex-shrink: 0;
    margin-left: 1rem;
}
.filter-tabs {
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 1.5rem;
}
.filter-tab {
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}
.filter-tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
}
.filter-tab:hover {
    color: #007bff;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}
.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}
.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
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
                <li class="breadcrumb-item active" aria-current="page">Activity Log</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Activity Log</h1>
    </div>
    <div class="btn-list">
        <button class="btn btn-outline-info" onclick="exportActivityLog()">
            <i class="ri-download-line me-1"></i>Export Log
        </button>
        <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Back to Profile
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Activity Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number">{{ \Carbon\Carbon::parse($user->created_at)->diffInDays() + 1 }}</div>
        <div class="stat-label">Days Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">15</div>
        <div class="stat-label">Total Logins</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">3</div>
        <div class="stat-label">Profile Updates</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">1</div>
        <div class="stat-label">Password Changes</div>
    </div>
</div>

<!-- Activity Log -->
<div class="activity-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="ri-history-line me-2"></i>Recent Activity</h5>
        <div class="btn-group" role="group">
            <button class="btn btn-outline-secondary btn-sm" onclick="refreshLog()">
                <i class="ri-refresh-line"></i>
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="clearLog()">
                <i class="ri-delete-bin-line"></i>
            </button>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-tab active" onclick="filterActivities('all')">All Activities</button>
        <button class="filter-tab" onclick="filterActivities('login')">Logins</button>
        <button class="filter-tab" onclick="filterActivities('profile')">Profile Changes</button>
        <button class="filter-tab" onclick="filterActivities('security')">Security</button>
        <button class="filter-tab" onclick="filterActivities('settings')">Settings</button>
    </div>
    
    <!-- Activity Items -->
    <div id="activityList">
        <!-- Current Login -->
        <div class="activity-item" data-category="login">
            <div class="activity-icon" style="background-color: #28a745;">
                <i class="ri-login-circle-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Successful Login</div>
                <div class="activity-description">Logged in from {{ request()->ip() }}</div>
                <div class="activity-time">{{ now()->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-success">Current Session</span>
            </div>
        </div>
        
        <!-- Profile View -->
        <div class="activity-item" data-category="profile">
            <div class="activity-icon" style="background-color: #007bff;">
                <i class="ri-user-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Profile Viewed</div>
                <div class="activity-description">Accessed profile page</div>
                <div class="activity-time">{{ now()->subMinutes(5)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-info">View</span>
            </div>
        </div>
        
        <!-- Account Created -->
        <div class="activity-item" data-category="profile">
            <div class="activity-icon" style="background-color: #6f42c1;">
                <i class="ri-user-add-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Account Created</div>
                <div class="activity-description">Welcome to {{ config('app.name') }}! Your account was successfully created.</div>
                <div class="activity-time">{{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-primary">Account</span>
            </div>
        </div>
        
        <!-- Sample Previous Login -->
        <div class="activity-item" data-category="login">
            <div class="activity-icon" style="background-color: #28a745;">
                <i class="ri-login-circle-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Previous Login</div>
                <div class="activity-description">Logged in from 192.168.1.100</div>
                <div class="activity-time">{{ now()->subDays(1)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-secondary">Completed</span>
            </div>
        </div>
        
        <!-- Sample Profile Update -->
        <div class="activity-item" data-category="profile">
            <div class="activity-icon" style="background-color: #fd7e14;">
                <i class="ri-edit-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Profile Updated</div>
                <div class="activity-description">Updated profile information including phone number</div>
                <div class="activity-time">{{ now()->subDays(2)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-warning">Update</span>
            </div>
        </div>
        
        <!-- Sample Password Change -->
        <div class="activity-item" data-category="security">
            <div class="activity-icon" style="background-color: #dc3545;">
                <i class="ri-lock-password-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Password Changed</div>
                <div class="activity-description">Successfully updated account password</div>
                <div class="activity-time">{{ now()->subDays(7)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-danger">Security</span>
            </div>
        </div>
        
        <!-- Sample Settings Change -->
        <div class="activity-item" data-category="settings">
            <div class="activity-icon" style="background-color: #6c757d;">
                <i class="ri-settings-3-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Settings Modified</div>
                <div class="activity-description">Updated notification preferences</div>
                <div class="activity-time">{{ now()->subDays(10)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-secondary">Settings</span>
            </div>
        </div>
        
        <!-- Sample Failed Login -->
        <div class="activity-item" data-category="security">
            <div class="activity-icon" style="background-color: #dc3545;">
                <i class="ri-error-warning-line text-white"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">Failed Login Attempt</div>
                <div class="activity-description">Unsuccessful login attempt from 203.0.113.1</div>
                <div class="activity-time">{{ now()->subDays(15)->format('M d, Y g:i A') }}</div>
            </div>
            <div class="activity-meta">
                <span class="badge bg-danger">Failed</span>
            </div>
        </div>
    </div>
    
    <!-- Load More Button -->
    <div class="text-center mt-4">
        <button class="btn btn-outline-primary" onclick="loadMoreActivities()">
            <i class="ri-arrow-down-line me-2"></i>Load More Activities
        </button>
    </div>
</div>

<!-- Security Notice -->
<div class="alert alert-info">
    <h6 class="alert-heading"><i class="ri-information-line me-2"></i>Security Notice</h6>
    <p class="mb-2">We keep track of your account activities to help protect your security. If you notice any suspicious activity, please contact support immediately.</p>
    <hr>
    <p class="mb-0">
        <strong>IP Address:</strong> {{ request()->ip() }} | 
        <strong>Browser:</strong> {{ request()->userAgent() ? substr(request()->userAgent(), 0, 50) . '...' : 'Unknown' }}
    </p>
</div>

@endsection

@section('scripts')
<script>
// Filter Activities
function filterActivities(category) {
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Filter activity items
    const activities = document.querySelectorAll('.activity-item');
    activities.forEach(activity => {
        if (category === 'all' || activity.dataset.category === category) {
            activity.style.display = 'flex';
        } else {
            activity.style.display = 'none';
        }
    });
    
    // Update count
    const visibleCount = document.querySelectorAll('.activity-item[style*="flex"], .activity-item:not([style*="none"])').length;
    console.log(`Showing ${visibleCount} activities for category: ${category}`);
}

// Refresh Log
function refreshLog() {
    // Show loading state
    const refreshBtn = event.target.closest('button');
    const originalContent = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="ri-loader-4-line"></i>';
    refreshBtn.disabled = true;
    
    // Simulate refresh delay
    setTimeout(() => {
        refreshBtn.innerHTML = originalContent;
        refreshBtn.disabled = false;
        showNotification('Activity log refreshed successfully!', 'success');
    }, 1000);
}

// Clear Log
function clearLog() {
    if (confirm('Are you sure you want to clear the activity log? This action cannot be undone.')) {
        // In a real application, you would send a request to clear the log
        showNotification('Activity log clearing is not implemented in this demo.', 'info');
    }
}

// Load More Activities
function loadMoreActivities() {
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="ri-loader-4-line"></i> Loading...';
    button.disabled = true;
    
    // Simulate loading delay
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.disabled = false;
        showNotification('No more activities to load.', 'info');
    }, 1000);
}

// Export Activity Log
function exportActivityLog() {
    // Create CSV content
    const activities = document.querySelectorAll('.activity-item');
    let csvContent = 'Date,Activity,Description,Category\n';
    
    activities.forEach(activity => {
        const title = activity.querySelector('.activity-title').textContent;
        const description = activity.querySelector('.activity-description').textContent;
        const time = activity.querySelector('.activity-time').textContent;
        const category = activity.dataset.category;
        
        csvContent += `"${time}","${title}","${description}","${category}"\n`;
    });
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `activity-log-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification('Activity log exported successfully!', 'success');
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
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        const alert = content.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 3000);
}

// Real-time updates (simulated)
document.addEventListener('DOMContentLoaded', function() {
    // Simulate real-time activity updates every 30 seconds
    setInterval(() => {
        // In a real application, you would fetch new activities from the server
        console.log('Checking for new activities...');
    }, 30000);
    
    // Add hover effects to activity items
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'all 0.2s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
            this.style.transform = 'translateX(0)';
        });
    });
});
</script>
@endsection