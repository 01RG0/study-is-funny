/* ============================================
   ADMIN DASHBOARD - JAVASCRIPT
   Comprehensive admin functionality
   ============================================ */

// ==================== AUTHENTICATION ====================

// Check admin session on page load
document.addEventListener('DOMContentLoaded', () => {
    const adminSession = localStorage.getItem('adminSession');
    
    if (!adminSession && !window.location.pathname.includes('login.html')) {
        window.location.href = 'login.html';
        return;
    }

    if (adminSession) {
        const session = JSON.parse(adminSession);
        document.getElementById('admin-username-display').textContent = `@${session.username}`;
        document.getElementById('admin-role-display').textContent = `[${session.role}]`;
    }

    initializeDashboard();
});

// Logout function
document.getElementById('logout-btn')?.addEventListener('click', () => {
    if (confirm('Logout? All unsaved changes will be lost.')) {
        localStorage.removeItem('adminSession');
        window.location.href = 'login.html';
    }
});

// ==================== NAVIGATION ====================

function switchSection(sectionName) {
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-section="${sectionName}"]`)?.classList.add('active');

    // Update content sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`section-${sectionName}`)?.classList.add('active');

    // Update page title
    const titles = {
        dashboard: 'Dashboard',
        users: 'User Management',
        content: 'Content Management',
        subscriptions: 'Subscriptions & Revenue',
        analytics: 'Analytics & Reports',
        settings: 'System Settings'
    };
    document.getElementById('page-title').textContent = titles[sectionName] || sectionName;

    // Load section data
    loadSectionData(sectionName);
}

// Navigation event listeners
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const section = item.getAttribute('data-section');
        switchSection(section);
    });
});

// ==================== MOCK DATA STORAGE ====================

// Initialize mock database in localStorage
function initializeDatabase() {
    if (!localStorage.getItem('adminDB')) {
        const initialData = {
            users: generateMockUsers(50),
            content: generateMockContent(30),
            subscriptions: generateMockSubscriptions(80),
            analytics: generateMockAnalytics(),
            settings: {
                siteName: 'Study is Funny',
                whatsappNumber: '+201234567890',
                darkMode: 'auto',
                maintenanceMode: 'off',
                scriptUrls: {
                    subscription: 'https://script.google.com/...',
                    tracking: 'https://script.google.com/...',
                    users: 'https://script.google.com/...'
                }
            }
        };
        localStorage.setItem('adminDB', JSON.stringify(initialData));
    }
}

function getDatabase() {
    return JSON.parse(localStorage.getItem('adminDB') || '{}');
}

function saveDatabase(data) {
    localStorage.setItem('adminDB', JSON.stringify(data));
}

// ==================== MOCK DATA GENERATORS ====================

function generateMockUsers(count) {
    const users = [];
    const grades = ['senior1', 'senior2', 'senior3'];
    const statuses = ['subscribed', 'free', 'subscribed', 'subscribed'];
    
    for (let i = 1; i <= count; i++) {
        users.push({
            id: i,
            phone: `+2010${String(Math.floor(10000000 + Math.random() * 90000000))}`,
            name: `Student ${i}`,
            grade: grades[Math.floor(Math.random() * grades.length)],
            status: statuses[Math.floor(Math.random() * statuses.length)],
            registered: new Date(Date.now() - Math.random() * 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            lastActive: new Date(Date.now() - Math.random() * 7 * 24 * 60 * 60 * 1000).toISOString()
        });
    }
    return users;
}

function generateMockContent(count) {
    const content = [];
    const subjects = ['maths', 'physics', 'statistics'];
    const grades = ['senior1', 'senior2', 'senior3'];
    const types = ['session', 'revision', 'video'];
    const statuses = ['published', 'draft', 'published', 'published'];
    
    for (let i = 1; i <= count; i++) {
        const subject = subjects[Math.floor(Math.random() * subjects.length)];
        const grade = grades[Math.floor(Math.random() * grades.length)];
        content.push({
            id: i,
            title: `${subject.charAt(0).toUpperCase() + subject.slice(1)} - Chapter ${Math.ceil(i/5)} Lecture ${i}`,
            subject: subject,
            grade: grade,
            type: types[Math.floor(Math.random() * types.length)],
            status: statuses[Math.floor(Math.random() * statuses.length)],
            views: Math.floor(Math.random() * 1000),
            videoUrl: 'https://youtube.com/embed/...',
            pdfUrl: 'https://drive.google.com/...',
            date: new Date(Date.now() - Math.random() * 60 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        });
    }
    return content;
}

function generateMockSubscriptions(count) {
    const subscriptions = [];
    const subjects = ['maths', 'physics', 'statistics'];
    const grades = ['senior1', 'senior2', 'senior3'];
    
    for (let i = 1; i <= count; i++) {
        subscriptions.push({
            id: i,
            userPhone: `+2010${String(Math.floor(10000000 + Math.random() * 90000000))}`,
            contentTitle: `Session ${Math.ceil(i/3)}`,
            grade: grades[Math.floor(Math.random() * grades.length)],
            subject: subjects[Math.floor(Math.random() * subjects.length)],
            amount: [50, 75, 100, 150][Math.floor(Math.random() * 4)],
            date: new Date(Date.now() - Math.random() * 60 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            paymentMethod: ['Vodafone Cash', 'Fawry', 'InstaPay'][Math.floor(Math.random() * 3)]
        });
    }
    return subscriptions;
}

function generateMockAnalytics() {
    return {
        userGrowth: Array.from({length: 30}, (_, i) => ({
            date: new Date(Date.now() - (29-i) * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            users: Math.floor(10 + Math.random() * 20)
        })),
        engagement: Array.from({length: 7}, (_, i) => ({
            day: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][i],
            sessions: Math.floor(50 + Math.random() * 150)
        }))
    };
}

// ==================== DASHBOARD FUNCTIONS ====================

function initializeDashboard() {
    initializeDatabase();
    updateSystemTime();
    loadDashboardMetrics();
    loadRecentActivity();
    loadUserGrowthChart();
    
    // Refresh button
    document.getElementById('refresh-btn')?.addEventListener('click', () => {
        loadSectionData(getCurrentSection());
        showNotification('Data refreshed', 'success');
    });
}

function updateSystemTime() {
    const updateTime = () => {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { hour12: false });
        const dateString = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        document.getElementById('system-time').textContent = `[${dateString} ${timeString}]`;
    };
    updateTime();
    setInterval(updateTime, 1000);
}

function getCurrentSection() {
    const activeNav = document.querySelector('.nav-item.active');
    return activeNav?.getAttribute('data-section') || 'dashboard';
}

function loadSectionData(section) {
    switch(section) {
        case 'dashboard':
            loadDashboardMetrics();
            loadRecentActivity();
            loadUserGrowthChart();
            break;
        case 'users':
            loadUsersTable();
            break;
        case 'content':
            loadContentTable();
            break;
        case 'subscriptions':
            loadSubscriptionsTable();
            loadRevenueMetrics();
            break;
        case 'analytics':
            loadAnalytics();
            break;
        case 'settings':
            loadSettings();
            break;
    }
}

function loadDashboardMetrics() {
    const db = getDatabase();
    
    document.getElementById('metric-users').textContent = db.users.length;
    document.getElementById('metric-subs').textContent = db.users.filter(u => u.status === 'subscribed').length;
    
    const totalRevenue = db.subscriptions.reduce((sum, sub) => sum + sub.amount, 0);
    document.getElementById('metric-revenue').textContent = `${totalRevenue.toLocaleString()} EGP`;
    
    document.getElementById('metric-sessions').textContent = db.content.filter(c => c.type === 'session').length;
}

function loadRecentActivity() {
    const db = getDatabase();
    const activities = [];
    
    // Get recent users
    const recentUsers = db.users.slice(-3).reverse();
    recentUsers.forEach(user => {
        const time = new Date(user.registered).toLocaleTimeString('en-US', { hour12: false });
        activities.push({
            time: `[${time}]`,
            text: `User registered: ${user.phone}`
        });
    });
    
    // Get recent subscriptions
    const recentSubs = db.subscriptions.slice(-2).reverse();
    recentSubs.forEach(sub => {
        activities.push({
            time: `[${new Date().toLocaleTimeString('en-US', { hour12: false })}]`,
            text: `New subscription: ${sub.grade} ${sub.subject}`
        });
    });
    
    const activityHTML = activities.slice(0, 5).map(a => 
        `<div class="activity-item">
            <span class="activity-time">${a.time}</span>
            <span class="activity-text">${a.text}</span>
        </div>`
    ).join('');
    
    document.getElementById('recent-activity').innerHTML = activityHTML;
}

function loadUserGrowthChart() {
    const db = getDatabase();
    const data = db.analytics.userGrowth.slice(-14); // Last 14 days
    
    const maxUsers = Math.max(...data.map(d => d.users));
    const chart = data.map(d => {
        const bars = Math.round((d.users / maxUsers) * 20);
        const bar = '█'.repeat(bars);
        const label = new Date(d.date).toLocaleDateString('en-US', { month: 'numeric', day: 'numeric' });
        return `${label.padEnd(6)} | ${bar} ${d.users}`;
    }).join('\n');
    
    document.getElementById('user-growth-chart').textContent = chart;
}

// ==================== USER MANAGEMENT ====================

let currentUsersPage = 1;
const usersPerPage = 10;

function loadUsersTable(page = 1) {
    const db = getDatabase();
    let users = db.users;
    
    // Apply filters
    const searchTerm = document.getElementById('user-search')?.value.toLowerCase() || '';
    const gradeFilter = document.getElementById('user-grade-filter')?.value || '';
    const statusFilter = document.getElementById('user-status-filter')?.value || '';
    
    users = users.filter(user => {
        const matchesSearch = user.phone.includes(searchTerm) || user.name.toLowerCase().includes(searchTerm);
        const matchesGrade = !gradeFilter || user.grade === gradeFilter;
        const matchesStatus = !statusFilter || user.status === statusFilter;
        return matchesSearch && matchesGrade && matchesStatus;
    });
    
    // Pagination
    const totalPages = Math.ceil(users.length / usersPerPage);
    const startIndex = (page - 1) * usersPerPage;
    const paginatedUsers = users.slice(startIndex, startIndex + usersPerPage);
    
    // Render table
    const tbody = document.getElementById('users-table-body');
    tbody.innerHTML = paginatedUsers.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>${user.phone}</td>
            <td>${user.name}</td>
            <td>${user.grade}</td>
            <td><span class="status-badge ${user.status}">${user.status}</span></td>
            <td>${user.registered}</td>
            <td>
                <button class="btn-icon" onclick="editUser(${user.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon" onclick="viewUserActivity(${user.id})" title="Activity">
                    <i class="fas fa-chart-line"></i>
                </button>
                <button class="btn-icon danger" onclick="deleteUser(${user.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    // Render pagination
    renderPagination('users-pagination', page, totalPages, loadUsersTable);
    
    currentUsersPage = page;
}

// Filter event listeners
document.getElementById('user-search')?.addEventListener('input', () => loadUsersTable(1));
document.getElementById('user-grade-filter')?.addEventListener('change', () => loadUsersTable(1));
document.getElementById('user-status-filter')?.addEventListener('change', () => loadUsersTable(1));

function editUser(userId) {
    const db = getDatabase();
    const user = db.users.find(u => u.id === userId);
    
    showModal('Edit User', `
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" id="edit-phone" value="${user.phone}">
        </div>
        <div class="form-group">
            <label>Name</label>
            <input type="text" id="edit-name" value="${user.name}">
        </div>
        <div class="form-group">
            <label>Grade</label>
            <select id="edit-grade">
                <option value="senior1" ${user.grade === 'senior1' ? 'selected' : ''}>Senior 1</option>
                <option value="senior2" ${user.grade === 'senior2' ? 'selected' : ''}>Senior 2</option>
                <option value="senior3" ${user.grade === 'senior3' ? 'selected' : ''}>Senior 3</option>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select id="edit-status">
                <option value="free" ${user.status === 'free' ? 'selected' : ''}>Free</option>
                <option value="subscribed" ${user.status === 'subscribed' ? 'selected' : ''}>Subscribed</option>
                <option value="banned" ${user.status === 'banned' ? 'selected' : ''}>Banned</option>
            </select>
        </div>
    `, () => {
        user.phone = document.getElementById('edit-phone').value;
        user.name = document.getElementById('edit-name').value;
        user.grade = document.getElementById('edit-grade').value;
        user.status = document.getElementById('edit-status').value;
        saveDatabase(db);
        loadUsersTable(currentUsersPage);
        closeModal();
        showNotification('User updated successfully', 'success');
    });
}

function deleteUser(userId) {
    if (confirm('Delete this user? This action cannot be undone.')) {
        const db = getDatabase();
        db.users = db.users.filter(u => u.id !== userId);
        saveDatabase(db);
        loadUsersTable(currentUsersPage);
        showNotification('User deleted', 'success');
    }
}

function viewUserActivity(userId) {
    const db = getDatabase();
    const user = db.users.find(u => u.id === userId);
    const userSubs = db.subscriptions.filter(s => s.userPhone === user.phone);
    
    showModal(`User Activity: ${user.name}`, `
        <div class="panel-content">
            <p><strong>Phone:</strong> ${user.phone}</p>
            <p><strong>Grade:</strong> ${user.grade}</p>
            <p><strong>Status:</strong> ${user.status}</p>
            <p><strong>Registered:</strong> ${user.registered}</p>
            <p><strong>Last Active:</strong> ${new Date(user.lastActive).toLocaleString()}</p>
            <hr style="border-color: var(--border-color); margin: 20px 0;">
            <h3 style="color: var(--accent-cyan); margin-bottom: 15px;">Subscriptions (${userSubs.length})</h3>
            ${userSubs.length > 0 ? `
                <table class="simple-table">
                    <thead>
                        <tr>
                            <th>Content</th>
                            <th>Subject</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${userSubs.map(s => `
                            <tr>
                                <td>${s.contentTitle}</td>
                                <td>${s.subject}</td>
                                <td>${s.amount} EGP</td>
                                <td>${s.date}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            ` : '<p style="color: var(--text-muted);">No subscriptions yet.</p>'}
        </div>
    `);
}

function showAddUserModal() {
    showModal('Add New User', `
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" id="new-phone" placeholder="+201234567890">
        </div>
        <div class="form-group">
            <label>Name</label>
            <input type="text" id="new-name" placeholder="Student Name">
        </div>
        <div class="form-group">
            <label>Grade</label>
            <select id="new-grade">
                <option value="senior1">Senior 1</option>
                <option value="senior2">Senior 2</option>
                <option value="senior3">Senior 3</option>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select id="new-status">
                <option value="free">Free</option>
                <option value="subscribed">Subscribed</option>
            </select>
        </div>
    `, () => {
        const db = getDatabase();
        const newUser = {
            id: Math.max(...db.users.map(u => u.id)) + 1,
            phone: document.getElementById('new-phone').value,
            name: document.getElementById('new-name').value,
            grade: document.getElementById('new-grade').value,
            status: document.getElementById('new-status').value,
            registered: new Date().toISOString().split('T')[0],
            lastActive: new Date().toISOString()
        };
        db.users.push(newUser);
        saveDatabase(db);
        loadUsersTable(1);
        closeModal();
        showNotification('User added successfully', 'success');
    });
}

function exportUsers() {
    const db = getDatabase();
    const csv = convertToCSV(db.users, ['id', 'phone', 'name', 'grade', 'status', 'registered']);
    downloadFile(csv, 'users-export.csv', 'text/csv');
    showNotification('Users exported', 'success');
}

// ==================== CONTENT MANAGEMENT ====================

let currentContentPage = 1;
const contentPerPage = 10;

function loadContentTable(page = 1) {
    const db = getDatabase();
    let content = db.content;
    
    // Apply filters
    const subjectFilter = document.getElementById('content-subject-filter')?.value || '';
    const gradeFilter = document.getElementById('content-grade-filter')?.value || '';
    const typeFilter = document.getElementById('content-type-filter')?.value || '';
    
    content = content.filter(item => {
        const matchesSubject = !subjectFilter || item.subject === subjectFilter;
        const matchesGrade = !gradeFilter || item.grade === gradeFilter;
        const matchesType = !typeFilter || item.type === typeFilter;
        return matchesSubject && matchesGrade && matchesType;
    });
    
    // Pagination
    const totalPages = Math.ceil(content.length / contentPerPage);
    const startIndex = (page - 1) * contentPerPage;
    const paginatedContent = content.slice(startIndex, startIndex + contentPerPage);
    
    // Render table
    const tbody = document.getElementById('content-table-body');
    tbody.innerHTML = paginatedContent.map(item => `
        <tr>
            <td><input type="checkbox" class="content-checkbox" data-id="${item.id}"></td>
            <td>${item.id}</td>
            <td>${item.title}</td>
            <td>${item.subject}</td>
            <td>${item.grade}</td>
            <td>${item.type}</td>
            <td><span class="status-badge ${item.status}">${item.status}</span></td>
            <td>${item.views}</td>
            <td>
                <button class="btn-icon" onclick="editContent(${item.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon" onclick="togglePublish(${item.id})" title="Toggle Publish">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-icon" onclick="duplicateContent(${item.id})" title="Duplicate">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="btn-icon danger" onclick="deleteContent(${item.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    // Render pagination
    renderPagination('content-pagination', page, totalPages, loadContentTable);
    
    currentContentPage = page;
}

// Filter event listeners
document.getElementById('content-subject-filter')?.addEventListener('change', () => loadContentTable(1));
document.getElementById('content-grade-filter')?.addEventListener('change', () => loadContentTable(1));
document.getElementById('content-type-filter')?.addEventListener('change', () => loadContentTable(1));

// Select all checkbox
document.getElementById('select-all-content')?.addEventListener('change', (e) => {
    document.querySelectorAll('.content-checkbox').forEach(cb => {
        cb.checked = e.target.checked;
    });
});

function editContent(contentId) {
    const db = getDatabase();
    const content = db.content.find(c => c.id === contentId);
    
    showModal('Edit Content', `
        <div class="form-group">
            <label>Title</label>
            <input type="text" id="edit-title" value="${content.title}">
        </div>
        <div class="form-group">
            <label>Subject</label>
            <select id="edit-subject">
                <option value="maths" ${content.subject === 'maths' ? 'selected' : ''}>Mathematics</option>
                <option value="physics" ${content.subject === 'physics' ? 'selected' : ''}>Physics</option>
                <option value="statistics" ${content.subject === 'statistics' ? 'selected' : ''}>Statistics</option>
            </select>
        </div>
        <div class="form-group">
            <label>Grade</label>
            <select id="edit-content-grade">
                <option value="senior1" ${content.grade === 'senior1' ? 'selected' : ''}>Senior 1</option>
                <option value="senior2" ${content.grade === 'senior2' ? 'selected' : ''}>Senior 2</option>
                <option value="senior3" ${content.grade === 'senior3' ? 'selected' : ''}>Senior 3</option>
            </select>
        </div>
        <div class="form-group">
            <label>Type</label>
            <select id="edit-type">
                <option value="session" ${content.type === 'session' ? 'selected' : ''}>Session</option>
                <option value="revision" ${content.type === 'revision' ? 'selected' : ''}>Revision</option>
                <option value="video" ${content.type === 'video' ? 'selected' : ''}>Video</option>
            </select>
        </div>
        <div class="form-group">
            <label>Video URL</label>
            <input type="text" id="edit-video-url" value="${content.videoUrl}">
        </div>
        <div class="form-group">
            <label>PDF URL</label>
            <input type="text" id="edit-pdf-url" value="${content.pdfUrl}">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select id="edit-content-status">
                <option value="draft" ${content.status === 'draft' ? 'selected' : ''}>Draft</option>
                <option value="published" ${content.status === 'published' ? 'selected' : ''}>Published</option>
            </select>
        </div>
    `, () => {
        content.title = document.getElementById('edit-title').value;
        content.subject = document.getElementById('edit-subject').value;
        content.grade = document.getElementById('edit-content-grade').value;
        content.type = document.getElementById('edit-type').value;
        content.videoUrl = document.getElementById('edit-video-url').value;
        content.pdfUrl = document.getElementById('edit-pdf-url').value;
        content.status = document.getElementById('edit-content-status').value;
        saveDatabase(db);
        loadContentTable(currentContentPage);
        closeModal();
        showNotification('Content updated successfully', 'success');
    });
}

function togglePublish(contentId) {
    const db = getDatabase();
    const content = db.content.find(c => c.id === contentId);
    content.status = content.status === 'published' ? 'draft' : 'published';
    saveDatabase(db);
    loadContentTable(currentContentPage);
    showNotification(`Content ${content.status}`, 'success');
}

function duplicateContent(contentId) {
    const db = getDatabase();
    const content = db.content.find(c => c.id === contentId);
    const duplicate = {
        ...content,
        id: Math.max(...db.content.map(c => c.id)) + 1,
        title: `${content.title} (Copy)`,
        status: 'draft',
        views: 0
    };
    db.content.push(duplicate);
    saveDatabase(db);
    loadContentTable(currentContentPage);
    showNotification('Content duplicated', 'success');
}

function deleteContent(contentId) {
    if (confirm('Delete this content? This action cannot be undone.')) {
        const db = getDatabase();
        db.content = db.content.filter(c => c.id !== contentId);
        saveDatabase(db);
        loadContentTable(currentContentPage);
        showNotification('Content deleted', 'success');
    }
}

function showAddContentModal() {
    showModal('Add New Content', `
        <div class="form-group">
            <label>Title</label>
            <input type="text" id="new-title" placeholder="Chapter 1 Lecture 1">
        </div>
        <div class="form-group">
            <label>Subject</label>
            <select id="new-subject">
                <option value="maths">Mathematics</option>
                <option value="physics">Physics</option>
                <option value="statistics">Statistics</option>
            </select>
        </div>
        <div class="form-group">
            <label>Grade</label>
            <select id="new-content-grade">
                <option value="senior1">Senior 1</option>
                <option value="senior2">Senior 2</option>
                <option value="senior3">Senior 3</option>
            </select>
        </div>
        <div class="form-group">
            <label>Type</label>
            <select id="new-type">
                <option value="session">Session</option>
                <option value="revision">Revision</option>
                <option value="video">Video</option>
            </select>
        </div>
        <div class="form-group">
            <label>Video URL</label>
            <input type="text" id="new-video-url" placeholder="https://youtube.com/embed/...">
        </div>
        <div class="form-group">
            <label>PDF URL</label>
            <input type="text" id="new-pdf-url" placeholder="https://drive.google.com/...">
        </div>
    `, () => {
        const db = getDatabase();
        const newContent = {
            id: Math.max(...db.content.map(c => c.id)) + 1,
            title: document.getElementById('new-title').value,
            subject: document.getElementById('new-subject').value,
            grade: document.getElementById('new-content-grade').value,
            type: document.getElementById('new-type').value,
            videoUrl: document.getElementById('new-video-url').value,
            pdfUrl: document.getElementById('new-pdf-url').value,
            status: 'draft',
            views: 0,
            date: new Date().toISOString().split('T')[0]
        };
        db.content.push(newContent);
        saveDatabase(db);
        loadContentTable(1);
        closeModal();
        showNotification('Content added successfully', 'success');
    });
}

function bulkPublish() {
    const selectedIds = Array.from(document.querySelectorAll('.content-checkbox:checked'))
        .map(cb => parseInt(cb.getAttribute('data-id')));
    
    if (selectedIds.length === 0) {
        showNotification('No items selected', 'warning');
        return;
    }
    
    const db = getDatabase();
    selectedIds.forEach(id => {
        const content = db.content.find(c => c.id === id);
        if (content) content.status = 'published';
    });
    saveDatabase(db);
    loadContentTable(currentContentPage);
    showNotification(`${selectedIds.length} items published`, 'success');
}

// ==================== SUBSCRIPTIONS MANAGEMENT ====================

let currentSubsPage = 1;
const subsPerPage = 10;

function loadSubscriptionsTable(page = 1) {
    const db = getDatabase();
    let subscriptions = db.subscriptions;
    
    // Apply filters
    const searchTerm = document.getElementById('sub-search')?.value.toLowerCase() || '';
    const dateFrom = document.getElementById('sub-date-from')?.value || '';
    const dateTo = document.getElementById('sub-date-to')?.value || '';
    
    subscriptions = subscriptions.filter(sub => {
        const matchesSearch = sub.userPhone.includes(searchTerm);
        const matchesDateFrom = !dateFrom || sub.date >= dateFrom;
        const matchesDateTo = !dateTo || sub.date <= dateTo;
        return matchesSearch && matchesDateFrom && matchesDateTo;
    });
    
    // Pagination
    const totalPages = Math.ceil(subscriptions.length / subsPerPage);
    const startIndex = (page - 1) * subsPerPage;
    const paginatedSubs = subscriptions.slice(startIndex, startIndex + subsPerPage);
    
    // Render table
    const tbody = document.getElementById('subscriptions-table-body');
    tbody.innerHTML = paginatedSubs.map(sub => `
        <tr>
            <td>${sub.id}</td>
            <td>${sub.userPhone}</td>
            <td>${sub.contentTitle}</td>
            <td>${sub.grade}</td>
            <td>${sub.subject}</td>
            <td>${sub.amount} EGP</td>
            <td>${sub.date}</td>
            <td>
                <button class="btn-icon" onclick="viewSubscriptionDetails(${sub.id})" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-icon danger" onclick="revokeSubscription(${sub.id})" title="Revoke">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    // Render pagination
    renderPagination('subscriptions-pagination', page, totalPages, loadSubscriptionsTable);
    
    currentSubsPage = page;
}

// Filter event listeners
document.getElementById('sub-search')?.addEventListener('input', () => loadSubscriptionsTable(1));
document.getElementById('sub-date-from')?.addEventListener('change', () => loadSubscriptionsTable(1));
document.getElementById('sub-date-to')?.addEventListener('change', () => loadSubscriptionsTable(1));

function loadRevenueMetrics() {
    const db = getDatabase();
    
    const totalRevenue = db.subscriptions.reduce((sum, sub) => sum + sub.amount, 0);
    document.getElementById('total-revenue').textContent = `${totalRevenue.toLocaleString()} EGP`;
    
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    const monthRevenue = db.subscriptions
        .filter(sub => {
            const subDate = new Date(sub.date);
            return subDate.getMonth() === currentMonth && subDate.getFullYear() === currentYear;
        })
        .reduce((sum, sub) => sum + sub.amount, 0);
    document.getElementById('month-revenue').textContent = `${monthRevenue.toLocaleString()} EGP`;
    
    const avgRevenue = db.users.length > 0 ? Math.round(totalRevenue / db.users.length) : 0;
    document.getElementById('avg-revenue').textContent = `${avgRevenue.toLocaleString()} EGP`;
}

function viewSubscriptionDetails(subId) {
    const db = getDatabase();
    const sub = db.subscriptions.find(s => s.id === subId);
    
    showModal('Subscription Details', `
        <div class="panel-content">
            <p><strong>ID:</strong> ${sub.id}</p>
            <p><strong>User Phone:</strong> ${sub.userPhone}</p>
            <p><strong>Content:</strong> ${sub.contentTitle}</p>
            <p><strong>Grade:</strong> ${sub.grade}</p>
            <p><strong>Subject:</strong> ${sub.subject}</p>
            <p><strong>Amount:</strong> ${sub.amount} EGP</p>
            <p><strong>Date:</strong> ${sub.date}</p>
            <p><strong>Payment Method:</strong> ${sub.paymentMethod}</p>
        </div>
    `);
}

function revokeSubscription(subId) {
    if (confirm('Revoke this subscription?')) {
        const db = getDatabase();
        db.subscriptions = db.subscriptions.filter(s => s.id !== subId);
        saveDatabase(db);
        loadSubscriptionsTable(currentSubsPage);
        showNotification('Subscription revoked', 'success');
    }
}

function showGrantSubModal() {
    showModal('Grant Access', `
        <div class="form-group">
            <label>User Phone</label>
            <input type="text" id="grant-phone" placeholder="+201234567890">
        </div>
        <div class="form-group">
            <label>Content/Session</label>
            <input type="text" id="grant-content" placeholder="Session 1">
        </div>
        <div class="form-group">
            <label>Grade</label>
            <select id="grant-grade">
                <option value="senior1">Senior 1</option>
                <option value="senior2">Senior 2</option>
                <option value="senior3">Senior 3</option>
            </select>
        </div>
        <div class="form-group">
            <label>Subject</label>
            <select id="grant-subject">
                <option value="maths">Mathematics</option>
                <option value="physics">Physics</option>
                <option value="statistics">Statistics</option>
            </select>
        </div>
        <div class="form-group">
            <label>Amount (0 for free)</label>
            <input type="number" id="grant-amount" value="0">
        </div>
    `, () => {
        const db = getDatabase();
        const newSub = {
            id: Math.max(...db.subscriptions.map(s => s.id)) + 1,
            userPhone: document.getElementById('grant-phone').value,
            contentTitle: document.getElementById('grant-content').value,
            grade: document.getElementById('grant-grade').value,
            subject: document.getElementById('grant-subject').value,
            amount: parseInt(document.getElementById('grant-amount').value) || 0,
            date: new Date().toISOString().split('T')[0],
            paymentMethod: 'Manual Grant'
        };
        db.subscriptions.push(newSub);
        saveDatabase(db);
        loadSubscriptionsTable(1);
        loadRevenueMetrics();
        closeModal();
        showNotification('Access granted successfully', 'success');
    });
}

function exportSubscriptions() {
    const db = getDatabase();
    const csv = convertToCSV(db.subscriptions, ['id', 'userPhone', 'contentTitle', 'grade', 'subject', 'amount', 'date', 'paymentMethod']);
    downloadFile(csv, 'subscriptions-export.csv', 'text/csv');
    showNotification('Subscriptions exported', 'success');
}

// ==================== ANALYTICS ====================

function loadAnalytics() {
    loadTopSessions();
    loadTopUsers();
    loadEngagementChart();
    loadSubjectDistribution();
}

function loadTopSessions() {
    const db = getDatabase();
    const topSessions = db.content
        .sort((a, b) => b.views - a.views)
        .slice(0, 10);
    
    const tbody = document.getElementById('top-sessions-body');
    tbody.innerHTML = topSessions.map((session, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${session.title}</td>
            <td>${session.subject}</td>
            <td>${session.views}</td>
        </tr>
    `).join('');
}

function loadTopUsers() {
    const db = getDatabase();
    const userActivity = {};
    
    db.subscriptions.forEach(sub => {
        userActivity[sub.userPhone] = (userActivity[sub.userPhone] || 0) + 1;
    });
    
    const topUsers = Object.entries(userActivity)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10)
        .map(([phone, count]) => {
            const user = db.users.find(u => u.phone === phone);
            return { phone, count, grade: user?.grade || 'N/A' };
        });
    
    const tbody = document.getElementById('top-users-body');
    tbody.innerHTML = topUsers.map((user, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${user.phone}</td>
            <td>${user.grade}</td>
            <td>${user.count}</td>
        </tr>
    `).join('');
}

function loadEngagementChart() {
    const db = getDatabase();
    const data = db.analytics.engagement;
    
    const maxSessions = Math.max(...data.map(d => d.sessions));
    const chart = data.map(d => {
        const bars = Math.round((d.sessions / maxSessions) * 30);
        const bar = '█'.repeat(bars);
        return `${d.day.padEnd(4)} | ${bar} ${d.sessions}`;
    }).join('\n');
    
    document.getElementById('engagement-chart').textContent = chart;
}

function loadSubjectDistribution() {
    const db = getDatabase();
    const distribution = {};
    
    db.content.forEach(item => {
        distribution[item.subject] = (distribution[item.subject] || 0) + item.views;
    });
    
    const total = Object.values(distribution).reduce((sum, val) => sum + val, 0);
    const maxViews = Math.max(...Object.values(distribution));
    
    const html = Object.entries(distribution).map(([subject, views]) => {
        const percentage = total > 0 ? Math.round((views / total) * 100) : 0;
        const width = total > 0 ? (views / maxViews) * 100 : 0;
        return `
            <div class="bar-item">
                <div class="bar-label">${subject}</div>
                <div class="bar-container">
                    <div class="bar-fill" style="width: ${width}%"></div>
                </div>
                <div class="bar-value">${views} (${percentage}%)</div>
            </div>
        `;
    }).join('');
    
    document.getElementById('subject-distribution').innerHTML = html;
}

function generateReport() {
    showNotification('Report generation feature coming soon', 'info');
}

// ==================== SETTINGS ====================

function loadSettings() {
    const db = getDatabase();
    const settings = db.settings;
    
    document.getElementById('site-name').value = settings.siteName;
    document.getElementById('whatsapp-number').value = settings.whatsappNumber;
    document.getElementById('default-dark-mode').value = settings.darkMode;
    document.getElementById('maintenance-mode').value = settings.maintenanceMode;
    document.getElementById('script-url-1').value = settings.scriptUrls.subscription;
    document.getElementById('script-url-2').value = settings.scriptUrls.tracking;
    document.getElementById('script-url-3').value = settings.scriptUrls.users;
}

function saveSettings() {
    const db = getDatabase();
    
    db.settings.siteName = document.getElementById('site-name').value;
    db.settings.whatsappNumber = document.getElementById('whatsapp-number').value;
    db.settings.darkMode = document.getElementById('default-dark-mode').value;
    db.settings.maintenanceMode = document.getElementById('maintenance-mode').value;
    db.settings.scriptUrls.subscription = document.getElementById('script-url-1').value;
    db.settings.scriptUrls.tracking = document.getElementById('script-url-2').value;
    db.settings.scriptUrls.users = document.getElementById('script-url-3').value;
    
    saveDatabase(db);
    showNotification('Settings saved successfully', 'success');
}

function changePassword() {
    const current = document.getElementById('current-password').value;
    const newPass = document.getElementById('new-password').value;
    const confirm = document.getElementById('confirm-password').value;
    
    if (!current || !newPass || !confirm) {
        showNotification('All password fields are required', 'error');
        return;
    }
    
    if (newPass !== confirm) {
        showNotification('New passwords do not match', 'error');
        return;
    }
    
    // In production, verify current password with backend
    showNotification('Password changed successfully (demo mode)', 'success');
    document.getElementById('current-password').value = '';
    document.getElementById('new-password').value = '';
    document.getElementById('confirm-password').value = '';
}

function showAddAdminModal() {
    showModal('Add Admin User', `
        <div class="form-group">
            <label>Username</label>
            <input type="text" id="new-admin-username">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="new-admin-password">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select id="new-admin-role">
                <option value="moderator">Moderator</option>
                <option value="superadmin">Super Admin</option>
            </select>
        </div>
    `, () => {
        showNotification('Admin user created (demo mode)', 'success');
        closeModal();
    });
}

function editAdmin(username) {
    showNotification(`Edit admin: ${username} (demo mode)`, 'info');
}

function deleteAdmin(username) {
    if (confirm(`Delete admin user: ${username}?`)) {
        showNotification('Admin user deleted (demo mode)', 'success');
    }
}

function exportAllData() {
    const db = getDatabase();
    const json = JSON.stringify(db, null, 2);
    downloadFile(json, 'admin-backup.json', 'application/json');
    showNotification('Data exported successfully', 'success');
}

function importData() {
    const fileInput = document.getElementById('import-file');
    const file = fileInput.files[0];
    
    if (!file) {
        showNotification('Please select a file', 'warning');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = JSON.parse(e.target.result);
            localStorage.setItem('adminDB', JSON.stringify(data));
            showNotification('Data imported successfully. Refreshing...', 'success');
            setTimeout(() => location.reload(), 1500);
        } catch (error) {
            showNotification('Invalid backup file', 'error');
        }
    };
    reader.readAsText(file);
}

function clearAllData() {
    if (confirm('WARNING: This will delete ALL data. Type "DELETE" to confirm:') && 
        prompt('Type DELETE to confirm:') === 'DELETE') {
        localStorage.removeItem('adminDB');
        initializeDatabase();
        location.reload();
    }
}

// ==================== UTILITY FUNCTIONS ====================

function renderPagination(containerId, currentPage, totalPages, loadFunction) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let html = `
        <button ${currentPage === 1 ? 'disabled' : ''} onclick="${loadFunction.name}(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Show page numbers
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    if (startPage > 1) {
        html += `<button onclick="${loadFunction.name}(1)">1</button>`;
        if (startPage > 2) html += '<span>...</span>';
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="${i === currentPage ? 'active' : ''}" onclick="${loadFunction.name}(${i})">${i}</button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span>...</span>';
        html += `<button onclick="${loadFunction.name}(${totalPages})">${totalPages}</button>`;
    }
    
    html += `
        <button ${currentPage === totalPages ? 'disabled' : ''} onclick="${loadFunction.name}(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    container.innerHTML = html;
}

function showModal(title, bodyHTML, onConfirm = null) {
    const modal = document.getElementById('modal-overlay');
    const content = document.getElementById('modal-content');
    
    content.innerHTML = `
        <div class="modal-header">
            <div class="modal-title">${title}</div>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            ${bodyHTML}
        </div>
        ${onConfirm ? `
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" id="modal-confirm-btn">Confirm</button>
            </div>
        ` : ''}
    `;
    
    if (onConfirm) {
        document.getElementById('modal-confirm-btn').addEventListener('click', onConfirm);
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('active');
}

// Close modal on overlay click
document.getElementById('modal-overlay')?.addEventListener('click', (e) => {
    if (e.target.id === 'modal-overlay') {
        closeModal();
    }
});

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `message ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        max-width: 500px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function convertToCSV(data, columns) {
    const header = columns.join(',');
    const rows = data.map(item => 
        columns.map(col => `"${item[col] || ''}"`).join(',')
    );
    return header + '\n' + rows.join('\n');
}

function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

function showExportModal() {
    showModal('Export Data', `
        <div class="panel-content">
            <p>Select data to export:</p>
            <div style="margin: 20px 0;">
                <label style="display: block; margin-bottom: 10px;">
                    <input type="checkbox" id="export-users" checked> Users
                </label>
                <label style="display: block; margin-bottom: 10px;">
                    <input type="checkbox" id="export-content" checked> Content
                </label>
                <label style="display: block; margin-bottom: 10px;">
                    <input type="checkbox" id="export-subs" checked> Subscriptions
                </label>
            </div>
        </div>
    `, () => {
        const db = getDatabase();
        const exportData = {};
        
        if (document.getElementById('export-users').checked) exportData.users = db.users;
        if (document.getElementById('export-content').checked) exportData.content = db.content;
        if (document.getElementById('export-subs').checked) exportData.subscriptions = db.subscriptions;
        
        const json = JSON.stringify(exportData, null, 2);
        downloadFile(json, 'data-export.json', 'application/json');
        closeModal();
        showNotification('Data exported successfully', 'success');
    });
}

function showBackupModal() {
    showModal('Database Backup', `
        <div class="panel-content">
            <p>Create a full backup of all database contents.</p>
            <p style="color: var(--text-muted); margin-top: 10px;">
                This will download a JSON file containing all users, content, subscriptions, and settings.
            </p>
        </div>
    `, () => {
        exportAllData();
        closeModal();
    });
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);
