// Admin Dashboard JavaScript

// Check if admin is logged in
function checkAdminAuth() {
    const isLoggedIn = localStorage.getItem('adminLoggedIn');
    if (!isLoggedIn && window.location.pathname.includes('dashboard.html')) {
        window.location.href = 'login.html';
    }
}

// Logout function
function logout() {
    localStorage.removeItem('adminLoggedIn');
    localStorage.removeItem('adminUsername');
    window.location.href = 'login.html';
}

// Navigation between sections
function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.classList.remove('active'));

    // Remove active class from nav items
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => item.classList.remove('active'));

    // Show selected section
    document.getElementById(sectionId).classList.add('active');

    // Add active class to clicked nav item
    event.target.classList.add('active');
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    checkAdminAuth();

    // Load dashboard data
    loadDashboardStats();
    loadRecentActivity();

    // Initialize form handlers
    initializeSessionUploadForm();
    initializeSessionManagement();
});

// Load dashboard statistics
function loadDashboardStats() {
    // Simulate loading stats (in production, fetch from API)
    const stats = {
        totalSessions: 45,
        totalStudents: 1250,
        totalViews: 15420,
        uploadsToday: 3
    };

    document.getElementById('totalSessions').textContent = stats.totalSessions;
    document.getElementById('totalStudents').textContent = stats.totalStudents;
    document.getElementById('totalViews').textContent = stats.totalViews;
    document.getElementById('uploadsToday').textContent = stats.uploadsToday;
}

// Load recent activity
function loadRecentActivity() {
    const activities = [
        {
            type: 'upload',
            message: 'New session uploaded: "Physics - Mechanics"',
            time: '2 hours ago'
        },
        {
            type: 'student',
            message: 'New student registered: Ahmed Mohamed',
            time: '4 hours ago'
        },
        {
            type: 'view',
            message: 'Session "Mathematics - Algebra" viewed 25 times',
            time: '6 hours ago'
        }
    ];

    const activityContainer = document.getElementById('recentActivity');
    activityContainer.innerHTML = '';

    activities.forEach(activity => {
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';

        activityItem.innerHTML = `
            <div class="activity-icon">
                <i class="fas fa-${activity.type === 'upload' ? 'upload' : activity.type === 'student' ? 'user-plus' : 'eye'}"></i>
            </div>
            <div class="activity-content">
                <p>${activity.message}</p>
                <span class="activity-time">${activity.time}</span>
            </div>
        `;

        activityContainer.appendChild(activityItem);
    });
}

// Session Upload Form Handling
function initializeSessionUploadForm() {
    const form = document.getElementById('sessionUploadForm');
    const videosContainer = document.getElementById('videosContainer');
    const addVideoBtn = document.getElementById('addVideoBtn');

    let videoCount = 1;

    // Add video functionality
    addVideoBtn.addEventListener('click', function() {
        videoCount++;
        const videoItem = createVideoUploadItem(videoCount);
        videosContainer.appendChild(videoItem);

        // Show remove button for first video if more than one video
        if (videoCount > 1) {
            videosContainer.querySelector('.remove-video-btn').style.display = 'inline-block';
        }
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        submitBtn.disabled = true;

        // Simulate upload process
        setTimeout(() => {
            // Reset form
            form.reset();
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;

            // Reset video container to single video
            videoCount = 1;
            videosContainer.innerHTML = '';
            videosContainer.appendChild(createVideoUploadItem(1));

            // Show success message
            showMessage('Session uploaded successfully!', 'success');

            // Reload sessions table
            loadSessionsTable();
        }, 3000);
    });
}

// Create video upload item
function createVideoUploadItem(videoNumber) {
    const videoItem = document.createElement('div');
    videoItem.className = 'video-upload-item';

    videoItem.innerHTML = `
        <h4>Video ${videoNumber}</h4>
        <div class="form-row">
            <div class="form-group">
                <label>Video Type *</label>
                <select name="videoType[]" required>
                    <option value="lecture">Lecture</option>
                    <option value="questions">Questions</option>
                    <option value="summary">Summary</option>
                    <option value="exercise">Exercise</option>
                    <option value="homework">Homework</option>
                </select>
            </div>

            <div class="form-group">
                <label>Video Title *</label>
                <input type="text" name="videoTitle[]" required placeholder="e.g., Part ${videoNumber} - Introduction">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Video File *</label>
                <input type="file" name="videoFile[]" accept="video/*" required>
                <small class="file-info">Supported formats: MP4, AVI, MOV (Max: 500MB)</small>
            </div>

            <div class="form-group">
                <label>Thumbnail (Optional)</label>
                <input type="file" name="thumbnail[]" accept="image/*">
                <small class="file-info">Recommended: 1280x720px, JPG/PNG</small>
            </div>
        </div>

        <div class="form-group">
            <label>Video Description</label>
            <textarea name="videoDescription[]" rows="2" placeholder="Brief description of this video..."></textarea>
        </div>

        <div class="form-group">
            <label>Duration (minutes)</label>
            <input type="number" name="duration[]" min="1" placeholder="Estimated duration">
        </div>

        <button type="button" class="remove-video-btn" onclick="removeVideo(this)" style="display: ${videoNumber === 1 ? 'none' : 'inline-block'};">
            <i class="fas fa-trash"></i> Remove Video
        </button>
    `;

    return videoItem;
}

// Remove video function
function removeVideo(button) {
    const videoItem = button.closest('.video-upload-item');
    videoItem.remove();

    // Update video numbers
    const remainingVideos = document.querySelectorAll('.video-upload-item');
    remainingVideos.forEach((item, index) => {
        item.querySelector('h4').textContent = `Video ${index + 1}`;
    });
}

// Reset form
function resetForm() {
    const form = document.getElementById('sessionUploadForm');
    form.reset();

    // Reset video container
    const videosContainer = document.getElementById('videosContainer');
    videosContainer.innerHTML = '';
    videosContainer.appendChild(createVideoUploadItem(1));
}

// Session Management
function initializeSessionManagement() {
    loadSessionsTable();

    // Filter functionality
    document.getElementById('filterSubject').addEventListener('change', loadSessionsTable);
    document.getElementById('filterGrade').addEventListener('change', loadSessionsTable);
    document.getElementById('filterStatus').addEventListener('change', loadSessionsTable);
}

// Load sessions table
function loadSessionsTable() {
    const tableBody = document.getElementById('sessionsTableBody');
    tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">Loading sessions...</td></tr>';

    // Simulate loading sessions data
    setTimeout(() => {
        const sessions = [
            {
                id: 1,
                title: 'Introduction to Mechanics',
                subject: 'Physics',
                grade: 'Senior 1',
                status: 'published',
                views: 245,
                uploadDate: '2024-01-15'
            },
            {
                id: 2,
                title: 'Algebra Fundamentals',
                subject: 'Mathematics',
                grade: 'Senior 1',
                status: 'published',
                views: 189,
                uploadDate: '2024-01-14'
            },
            {
                id: 3,
                title: 'Statistics Basics',
                subject: 'Statistics',
                grade: 'Senior 2',
                status: 'draft',
                views: 0,
                uploadDate: '2024-01-13'
            }
        ];

        tableBody.innerHTML = '';

        sessions.forEach(session => {
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${session.title}</td>
                <td>${session.subject}</td>
                <td>${session.grade}</td>
                <td><span class="status-badge status-${session.status}">${session.status}</span></td>
                <td>${session.views}</td>
                <td>${new Date(session.uploadDate).toLocaleDateString()}</td>
                <td>
                    <button class="action-btn edit-btn" onclick="editSession(${session.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="action-btn delete-btn" onclick="deleteSession(${session.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            `;

            tableBody.appendChild(row);
        });
    }, 1000);
}

// Edit session
function editSession(sessionId) {
    const modal = document.getElementById('editSessionModal');
    modal.style.display = 'block';

    // Load edit form (simplified for demo)
    const modalBody = modal.querySelector('.modal-body');
    modalBody.innerHTML = `
        <p>Edit form for session ${sessionId} would be loaded here.</p>
        <p>In a full implementation, this would contain the same form as the upload form, pre-filled with existing data.</p>
    `;
}

// Delete session
function deleteSession(sessionId) {
    if (confirm('Are you sure you want to delete this session? This action cannot be undone.')) {
        // Simulate deletion
        showMessage('Session deleted successfully!', 'success');
        loadSessionsTable();
    }
}

// Close modal
document.addEventListener('click', function(e) {
    const modal = document.getElementById('editSessionModal');
    if (e.target.classList.contains('close-modal') || e.target === modal) {
        modal.style.display = 'none';
    }
});

// Utility functions
function showMessage(message, type) {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type}`;
    messageDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;

    // Add to page
    document.body.appendChild(messageDiv);

    // Remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// File upload validation
function validateFileUpload(input, maxSizeMB = 500) {
    const file = input.files[0];
    if (!file) return true;

    const maxSize = maxSizeMB * 1024 * 1024; // Convert to bytes

    if (file.size > maxSize) {
        alert(`File size exceeds ${maxSizeMB}MB limit.`);
        input.value = '';
        return false;
    }

    return true;
}

// Add file validation to video inputs
document.addEventListener('change', function(e) {
    if (e.target.name === 'videoFile[]') {
        validateFileUpload(e.target, 500); // 500MB limit
    } else if (e.target.name === 'thumbnail[]') {
        validateFileUpload(e.target, 10); // 10MB limit for thumbnails
    }
});