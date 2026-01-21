// Admin Dashboard JavaScript
window.APP_BASE_URL = window.APP_BASE_URL || '../';

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
document.addEventListener('DOMContentLoaded', function () {
    checkAdminAuth();

    // Load dashboard data
    loadDashboardStats();
    loadRecentActivity();

    // Initialize form handlers
    initializeSessionUploadForm();
    initializeSessionManagement();
});

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        // Get session stats from real API
        const sessionStats = await getSessionStats();

        if (sessionStats.success) {
            document.getElementById('totalSessions').textContent = sessionStats.stats.totalSessions;
            document.getElementById('totalViews').textContent = sessionStats.stats.totalViews;
            document.getElementById('uploadsToday').textContent = sessionStats.stats.publishedSessions;
        } else {
            console.warn('Session stats API failed:', sessionStats.message);
            // Fallback values
            document.getElementById('totalSessions').textContent = '0';
            document.getElementById('totalViews').textContent = '0';
            document.getElementById('uploadsToday').textContent = '0';
        }

        // For now, keep student stats as demo data
        document.getElementById('totalStudents').textContent = '1250';
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        // Fallback values
        document.getElementById('totalSessions').textContent = '0';
        document.getElementById('totalStudents').textContent = '1250';
        document.getElementById('totalViews').textContent = '0';
        document.getElementById('uploadsToday').textContent = '0';
    }
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
    addVideoBtn.addEventListener('click', function () {
        videoCount++;
        const videoItem = createVideoUploadItem(videoCount);
        videosContainer.appendChild(videoItem);

        // Show remove button for first video if more than one video
        if (videoCount > 1) {
            videosContainer.querySelector('.remove-video-btn').style.display = 'inline-block';
        }
    });

    // Form submission
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        await submitSessionForm(new FormData(form));
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
    loadSessionsTableEnhanced();

    // Filter functionality
    const filterSubject = document.getElementById('filterSubject');
    const filterGrade = document.getElementById('filterGrade');
    const filterStatus = document.getElementById('filterStatus');

    if (filterSubject) filterSubject.addEventListener('change', loadSessionsTableEnhanced);
    if (filterGrade) filterGrade.addEventListener('change', loadSessionsTableEnhanced);
    if (filterStatus) filterStatus.addEventListener('change', loadSessionsTableEnhanced);
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
document.addEventListener('click', function (e) {
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

    // Remove after 5 seconds (longer for errors)
    const timeout = type === 'error' ? 8000 : 5000;
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, timeout);
}

// Session Management Functions
async function createSession(sessionData) {
    try {
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(sessionData)
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error creating session:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

async function getAllSessions(filters = {}) {
    try {
        const queryParams = new URLSearchParams(filters);
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=all&${queryParams}`);

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error fetching sessions:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

async function updateSession(sessionId, updates) {
    try {
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=update`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: sessionId, ...updates })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error updating session:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

async function deleteSession(sessionId) {
    try {
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=delete`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: sessionId })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error deleting session:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

async function publishSession(sessionId) {
    try {
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=publish`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: sessionId })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error publishing session:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

async function unpublishSession(sessionId) {
    try {
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=unpublish`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: sessionId })
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error unpublishing session:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

async function getSessionStats() {
    try {
        const response = await fetch(`${window.APP_BASE_URL}api/sessions.php?action=stats`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error fetching session stats:', error);
        return { success: false, message: 'Network error occurred' };
    }
}

// Enhanced form submission with API integration
async function submitSessionForm(formData) {
    try {
        // Create session object first
        const sessionData = {
            title: formData.get('sessionTitle'),
            subject: formData.get('subject'),
            grade: formData.get('grade'),
            teacher: formData.get('teacher'),
            description: formData.get('description') || '',
            sessionNumber: formData.get('sessionNumber') ? parseInt(formData.get('sessionNumber')) : null,
            status: formData.get('isPublished') || 'draft',
            isPublished: formData.get('isPublished') === 'published',
            isFeatured: formData.get('featured') === 'yes',
            publishDate: formData.get('publishDate') || null,
            expiryDate: formData.get('expiryDate') || null,
            maxViews: formData.get('maxViews') ? parseInt(formData.get('maxViews')) : null,
            downloadable: formData.get('downloadable') === 'yes',
            allowedStudentTypes: getSelectedStudentTypes(formData),
            difficulty: formData.get('difficulty') || 'intermediate',
            tags: formData.get('tags') ? formData.get('tags').split(',').map(tag => tag.trim()).filter(tag => tag.length > 0) : [],
            videos: [],
            pdfFiles: []
        };

        // Process videos
        const videoTypes = formData.getAll('videoType[]');
        const videoTitles = formData.getAll('videoTitle[]');
        const videoDescriptions = formData.getAll('videoDescription[]');
        const durations = formData.getAll('duration[]');

        videoTypes.forEach((type, index) => {
            if (videoTitles[index]) {
                sessionData.videos.push({
                    type: type,
                    title: videoTitles[index],
                    description: videoDescriptions[index] || '',
                    duration: durations[index] ? parseInt(durations[index]) : null
                });
            }
        });

        // Validate session data
        const validationErrors = validateSessionData(sessionData);
        if (validationErrors.length > 0) {
            showMessage('Validation errors:<br>' + validationErrors.join('<br>'), 'error');
            return;
        }

        // Show loading state
        const submitBtn = document.querySelector('#sessionUploadForm .submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        submitBtn.disabled = true;

        // Create session via API
        const result = await createSession(sessionData);

        if (result.success) {
            // Reset form
            const form = document.getElementById('sessionUploadForm');
            form.reset();
            resetVideoContainer();

            // Show success message
            showMessage('Session created successfully!', 'success');

            // Reload sessions table
            loadSessionsTable();
        } else {
            if (result.errors && result.errors.length > 0) {
                showMessage('Validation errors:<br>' + result.errors.join('<br>'), 'error');
            } else {
                showMessage(result.message || 'Failed to create session', 'error');
            }
        }

        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;

    } catch (error) {
        console.error('Error submitting session form:', error);
        showMessage('An error occurred while creating the session', 'error');

        // Reset button
        const submitBtn = document.querySelector('#sessionUploadForm .submit-btn');
        submitBtn.innerHTML = 'Create Session';
        submitBtn.disabled = false;
    }
}

function getSelectedStudentTypes(formData) {
    const studentTypes = [];
    const allTypes = formData.getAll('studentTypes');

    if (allTypes.includes('all')) {
        return ['all'];
    }

    if (allTypes.includes('registered')) studentTypes.push('registered');
    if (allTypes.includes('senior1')) studentTypes.push('senior1');
    if (allTypes.includes('senior2')) studentTypes.push('senior2');

    return studentTypes.length > 0 ? studentTypes : ['all'];
}

function resetVideoContainer() {
    const videosContainer = document.getElementById('videosContainer');
    videosContainer.innerHTML = '';
    videosContainer.appendChild(createVideoUploadItem(1));
}

// Enhanced session management
async function loadSessionsTableEnhanced() {
    const tableBody = document.getElementById('sessionsTableBody');
    tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">Loading sessions...</td></tr>';

    try {
        // Get filter values
        const filters = {
            subject: document.getElementById('filterSubject')?.value || '',
            grade: document.getElementById('filterGrade')?.value || '',
            status: document.getElementById('filterStatus')?.value || ''
        };

        const result = await getAllSessions(filters);

        if (result.success) {
            displaySessions(result.sessions);
        } else {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 40px; color: red;">${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: red;">Failed to load sessions</td></tr>';
    }
}

function displaySessions(sessions) {
    const tableBody = document.getElementById('sessionsTableBody');
    tableBody.innerHTML = '';

    if (sessions.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">No sessions found</td></tr>';
        return;
    }

    sessions.forEach(session => {
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${session.title}</td>
            <td>${session.subject}</td>
            <td>${session.grade}</td>
            <td><span class="status-badge status-${session.status}">${session.status}</span></td>
            <td>${session.views || 0}</td>
            <td>${new Date(session.createdAt || session.uploadDate).toLocaleDateString()}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editSession('${session.id}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="action-btn delete-btn" onclick="deleteSession('${session.id}')">
                    <i class="fas fa-trash"></i> Delete
                </button>
                ${session.status === 'draft' ?
                `<button class="action-btn publish-btn" onclick="publishSession('${session.id}')">
                        <i class="fas fa-globe"></i> Publish
                    </button>` :
                `<button class="action-btn unpublish-btn" onclick="unpublishSession('${session.id}')">
                        <i class="fas fa-eye-slash"></i> Unpublish
                    </button>`
            }
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// Enhanced session actions
async function publishSession(sessionId) {
    if (confirm('Are you sure you want to publish this session?')) {
        const result = await publishSession(sessionId);
        if (result.success) {
            showMessage('Session published successfully!', 'success');
            loadSessionsTableEnhanced();
        } else {
            showMessage(result.message || 'Failed to publish session', 'error');
        }
    }
}

async function unpublishSession(sessionId) {
    if (confirm('Are you sure you want to unpublish this session?')) {
        const result = await unpublishSession(sessionId);
        if (result.success) {
            showMessage('Session unpublished successfully!', 'success');
            loadSessionsTableEnhanced();
        } else {
            showMessage(result.message || 'Failed to unpublish session', 'error');
        }
    }
}

async function deleteSession(sessionId) {
    if (confirm('Are you sure you want to delete this session? This action cannot be undone.')) {
        const result = await deleteSession(sessionId);
        if (result.success) {
            showMessage('Session deleted successfully!', 'success');
            loadSessionsTableEnhanced();
        } else {
            showMessage(result.message || 'Failed to delete session', 'error');
        }
    }
}

// Modal functions for session creation
function openCreateSessionModal() {
    const modal = document.getElementById('createSessionModal');
    modal.style.display = 'block';

    // Reset form
    const form = document.getElementById('createSessionForm');
    form.reset();

    // Reset video container
    const videosContainer = document.getElementById('modalVideosContainer');
    videosContainer.innerHTML = '';
    videosContainer.appendChild(createModalVideoItem(1));

    // Initialize modal video management
    initializeModalVideoManagement();
}

function closeCreateSessionModal() {
    const modal = document.getElementById('createSessionModal');
    modal.style.display = 'none';
}

function createModalVideoItem(videoNumber) {
    const videoItem = document.createElement('div');
    videoItem.className = 'video-item';

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

        <div class="form-group">
            <label>Video Description</label>
            <textarea name="videoDescription[]" rows="2" placeholder="Brief description of this video..."></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Duration (minutes)</label>
                <input type="number" name="duration[]" min="1" placeholder="Estimated duration">
            </div>

            <div class="form-group">
                <button type="button" class="remove-video-btn" onclick="removeModalVideo(this)" style="display: ${videoNumber === 1 ? 'none' : 'inline-block'};">
                    <i class="fas fa-trash"></i> Remove Video
                </button>
            </div>
        </div>
    `;

    return videoItem;
}

function initializeModalVideoManagement() {
    const addVideoBtn = document.getElementById('modalAddVideoBtn');
    const videosContainer = document.getElementById('modalVideosContainer');

    if (addVideoBtn) {
        addVideoBtn.onclick = function () {
            const videoCount = videosContainer.children.length + 1;
            const videoItem = createModalVideoItem(videoCount);
            videosContainer.appendChild(videoItem);

            // Show remove button for first video if more than one video
            if (videoCount > 1) {
                videosContainer.querySelector('.remove-video-btn').style.display = 'inline-block';
            }
        };
    }
}

function removeModalVideo(button) {
    const videoItem = button.closest('.video-item');
    videoItem.remove();

    // Update video numbers
    const remainingVideos = document.querySelectorAll('#modalVideosContainer .video-item');
    remainingVideos.forEach((item, index) => {
        item.querySelector('h4').textContent = `Video ${index + 1}`;
    });
}

// Modal form submission
document.addEventListener('DOMContentLoaded', function () {
    const createSessionForm = document.getElementById('createSessionForm');
    if (createSessionForm) {
        createSessionForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            await submitModalSessionForm(new FormData(this));
        });
    }
});

async function submitModalSessionForm(formData) {
    try {
        // Show loading state
        const submitBtn = document.querySelector('#createSessionForm .submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        submitBtn.disabled = true;

        // Create session object
        const sessionData = {
            title: formData.get('sessionTitle'),
            subject: formData.get('subject'),
            grade: formData.get('grade'),
            teacher: formData.get('teacher'),
            description: formData.get('description') || '',
            status: formData.get('isPublished') || 'draft',
            isPublished: formData.get('isPublished') === 'published',
            isFeatured: formData.get('featured') === 'yes',
            publishDate: formData.get('publishDate') || null,
            expiryDate: formData.get('expiryDate') || null,
            maxViews: formData.get('maxViews') ? parseInt(formData.get('maxViews')) : null,
            downloadable: formData.get('downloadable') === 'yes',
            allowedStudentTypes: getSelectedStudentTypes(formData),
            difficulty: formData.get('difficulty') || 'intermediate',
            tags: formData.get('tags') ? formData.get('tags').split(',').map(tag => tag.trim()) : [],
            videos: [],
            pdfFiles: []
        };

        // Process videos
        const videoTypes = formData.getAll('videoType[]');
        const videoTitles = formData.getAll('videoTitle[]');
        const videoDescriptions = formData.getAll('videoDescription[]');
        const durations = formData.getAll('duration[]');

        videoTypes.forEach((type, index) => {
            if (videoTitles[index]) {
                sessionData.videos.push({
                    type: type,
                    title: videoTitles[index],
                    description: videoDescriptions[index] || '',
                    duration: durations[index] ? parseInt(durations[index]) : null
                });
            }
        });

        // Create session via API
        const result = await createSession(sessionData);

        if (result.success) {
            // Close modal
            closeCreateSessionModal();

            // Show success message
            showMessage('Session created successfully!', 'success');

            // Reload sessions table
            loadSessionsTableEnhanced();
        } else {
            showMessage(result.message || 'Failed to create session', 'error');
        }

        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;

    } catch (error) {
        console.error('Error submitting modal session form:', error);
        showMessage('An error occurred while creating the session', 'error');

        // Reset button
        const submitBtn = document.querySelector('#createSessionForm .submit-btn');
        submitBtn.innerHTML = 'Create Session';
        submitBtn.disabled = false;
    }
}

function editSession(sessionId) {
    // For now, show a simple edit modal
    const modal = document.getElementById('editSessionModal');
    modal.style.display = 'block';

    // Load edit form (simplified for demo)
    const modalBody = modal.querySelector('.modal-body');
    modalBody.innerHTML = `
        <p>Edit functionality for session ${sessionId} will be implemented here.</p>
        <p>This will include a comprehensive form to edit all session details.</p>
        <button onclick="document.getElementById('editSessionModal').style.display='none'" class="submit-btn">Close</button>
    `;
}

// Session validation functions
function validateSessionData(sessionData) {
    const errors = [];

    // Required field validation
    if (!sessionData.title || sessionData.title.trim().length < 3) {
        errors.push('Session title must be at least 3 characters long');
    }

    if (!sessionData.subject) {
        errors.push('Subject is required');
    }

    // Validate videos
    if (!sessionData.videos || sessionData.videos.length === 0) {
        errors.push('At least one video is required');
    } else {
        sessionData.videos.forEach((video, index) => {
            if (!video.title || video.title.trim().length < 2) {
                errors.push(`Video ${index + 1}: Title must be at least 2 characters long`);
            }
            if (!video.type) {
                errors.push(`Video ${index + 1}: Type is required`);
            }
            if (video.duration && (video.duration < 1 || video.duration > 480)) {
                errors.push(`Video ${index + 1}: Duration must be between 1-480 minutes`);
            }
        });
    }

    // Validate access control
    if (!sessionData.allowedStudentTypes || sessionData.allowedStudentTypes.length === 0) {
        errors.push('At least one student type must be allowed');
    }

    // Validate session number
    if (!sessionData.sessionNumber || sessionData.sessionNumber < 1 || sessionData.sessionNumber > 100) {
        errors.push('Session number must be between 1-100');
    }

    // Validate max views
    if (sessionData.maxViews && (sessionData.maxViews < 1 || sessionData.maxViews > 1000)) {
        errors.push('Maximum views must be between 1-1000');
    }

    // Validate dates
    if (sessionData.publishDate && sessionData.expiryDate) {
        const publishDate = new Date(sessionData.publishDate);
        const expiryDate = new Date(sessionData.expiryDate);
        if (expiryDate <= publishDate) {
            errors.push('Expiry date must be after publish date');
        }
    }

    // Validate tags
    if (sessionData.tags && sessionData.tags.length > 10) {
        errors.push('Maximum 10 tags allowed');
    }

    return errors;
}

// Enhanced form submission with validation
async function submitModalSessionForm(formData) {
    try {
        // Create session object first
        const sessionData = {
            title: formData.get('sessionTitle'),
            subject: formData.get('subject'),
            grade: formData.get('grade'),
            teacher: formData.get('teacher'),
            description: formData.get('description') || '',
            sessionNumber: formData.get('sessionNumber') ? parseInt(formData.get('sessionNumber')) : null,
            status: formData.get('isPublished') || 'draft',
            isPublished: formData.get('isPublished') === 'published',
            isFeatured: formData.get('featured') === 'yes',
            publishDate: formData.get('publishDate') || null,
            expiryDate: formData.get('expiryDate') || null,
            maxViews: formData.get('maxViews') ? parseInt(formData.get('maxViews')) : null,
            downloadable: formData.get('downloadable') === 'yes',
            allowedStudentTypes: getSelectedStudentTypes(formData),
            difficulty: formData.get('difficulty') || 'intermediate',
            tags: formData.get('tags') ? formData.get('tags').split(',').map(tag => tag.trim()).filter(tag => tag.length > 0) : [],
            videos: [],
            pdfFiles: []
        };

        // Process videos
        const videoTypes = formData.getAll('videoType[]');
        const videoTitles = formData.getAll('videoTitle[]');
        const videoDescriptions = formData.getAll('videoDescription[]');
        const durations = formData.getAll('duration[]');

        videoTypes.forEach((type, index) => {
            if (videoTitles[index]) {
                sessionData.videos.push({
                    type: type,
                    title: videoTitles[index],
                    description: videoDescriptions[index] || '',
                    duration: durations[index] ? parseInt(durations[index]) : null
                });
            }
        });

        // Validate session data
        const validationErrors = validateSessionData(sessionData);
        if (validationErrors.length > 0) {
            showMessage('Validation errors:<br>' + validationErrors.join('<br>'), 'error');
            return;
        }

        // Show loading state
        const submitBtn = document.querySelector('#createSessionForm .submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        submitBtn.disabled = true;

        // Create session via API
        const result = await createSession(sessionData);

        if (result.success) {
            // Close modal
            closeCreateSessionModal();

            // Show success message
            showMessage('Session created successfully!', 'success');

            // Reload sessions table
            loadSessionsTableEnhanced();
        } else {
            showMessage(result.message || 'Failed to create session', 'error');
        }

        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;

    } catch (error) {
        console.error('Error submitting modal session form:', error);
        showMessage('An error occurred while creating the session', 'error');

        // Reset button
        const submitBtn = document.querySelector('#createSessionForm .submit-btn');
        submitBtn.innerHTML = 'Create Session';
        submitBtn.disabled = false;
    }
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
document.addEventListener('change', function (e) {
    if (e.target.name === 'videoFile[]') {
        validateFileUpload(e.target, 500); // 500MB limit
    } else if (e.target.name === 'thumbnail[]') {
        validateFileUpload(e.target, 10); // 10MB limit for thumbnails
    }
});