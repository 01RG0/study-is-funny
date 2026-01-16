// Student Dashboard JavaScript

// Check if student is logged in
function checkStudentAuth() {
    const isLoggedIn = localStorage.getItem('accessGranted');
    const userPhone = localStorage.getItem('userPhone');

    if (!isLoggedIn || !userPhone) {
        window.location.href = '../login';
        return false;
    }
    return true;
}

// Logout function
function logout() {
    localStorage.removeItem('accessGranted');
    localStorage.removeItem('userPhone');
    window.location.href = '../login';
}

// Initialize student dashboard
document.addEventListener('DOMContentLoaded', function() {
    if (!checkStudentAuth()) return;

    const userPhone = localStorage.getItem('userPhone');

    // Show loading overlay
    showLoading();

    // Load student data from MongoDB
    loadStudentData(userPhone);
});

// Load student data from MongoDB
async function loadStudentData(phone) {
    try {
        // In a real implementation, this would fetch from MongoDB Atlas Data API
        // For now, we'll simulate the data structure

        const studentData = await getStudentDataFromMongoDB(phone);

        if (studentData) {
            displayStudentInfo(studentData);
            generateStudentQRCodes(studentData);
            loadRecentActivity(studentData);
            loadAvailableSessions(studentData);
        } else {
            showError('لم يتم العثور على بيانات الطالب');
        }
    } catch (error) {
        console.error('Error loading student data:', error);
        showError('حدث خطأ في تحميل البيانات');
    } finally {
        hideLoading();
    }
}

// Simulate MongoDB data fetch
async function getStudentDataFromMongoDB(phone) {
    // Simulate API call delay
    await new Promise(resolve => setTimeout(resolve, 1500));

    // Mock student data - in real implementation, this would come from MongoDB
    const mockData = {
        name: 'أحمد محمد',
        phone: phone,
        grade: 'Senior 1',
        joinDate: '2024-01-10',
        totalSessions: 25,
        watchedSessions: 18,
        totalWatchTime: '45h 30m',
        subjects: ['physics', 'mathematics', 'statistics'],
        recentActivity: [
            {
                type: 'watch',
                message: 'شاهدت درس الفيزياء - الميكانيكا',
                time: 'منذ ساعتين',
                timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000)
            },
            {
                type: 'download',
                message: 'حملت ملف الرياضيات - الجبر',
                time: 'منذ يوم',
                timestamp: new Date(Date.now() - 24 * 60 * 60 * 1000)
            },
            {
                type: 'login',
                message: 'دخلت إلى النظام',
                time: 'منذ 3 أيام',
                timestamp: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000)
            }
        ],
        availableSessions: [
            {
                id: 1,
                title: 'مقدمة في الميكانيكا',
                subject: 'physics',
                progress: 100,
                lastWatched: '2024-01-15'
            },
            {
                id: 2,
                title: 'المتجهات والحركة',
                subject: 'physics',
                progress: 75,
                lastWatched: '2024-01-14'
            },
            {
                id: 3,
                title: 'أساسيات الجبر',
                subject: 'mathematics',
                progress: 60,
                lastWatched: '2024-01-13'
            },
            {
                id: 4,
                title: 'إحصائيات وصفية',
                subject: 'statistics',
                progress: 30,
                lastWatched: '2024-01-12'
            }
        ]
    };

    return mockData;
}

// Display student information
function displayStudentInfo(data) {
    document.getElementById('studentName').textContent = data.name;
    document.getElementById('studentPhone').textContent = `رقم الهاتف: ${data.phone}`;
    document.getElementById('studentGrade').textContent = `الصف: ${data.grade}`;
    document.getElementById('joinDate').textContent = `تاريخ الانضمام: ${new Date(data.joinDate).toLocaleDateString('ar-EG')}`;

    document.getElementById('totalSessions').textContent = data.totalSessions;
    document.getElementById('watchedSessions').textContent = data.watchedSessions;
    document.getElementById('totalWatchTime').textContent = data.totalWatchTime;
}

// Generate QR codes for student's subjects
function generateStudentQRCodes(data) {
    const container = document.getElementById('qrCodesContainer');
    container.innerHTML = '';

    if (!data.subjects || data.subjects.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--student-text-light);">لا توجد مواد متاحة حالياً</p>';
        return;
    }

    const subjectNames = {
        physics: { name: 'الفيزياء', icon: 'fas fa-atom', color: '#667eea' },
        mathematics: { name: 'الرياضيات', icon: 'fas fa-calculator', color: '#f093fb' },
        statistics: { name: 'الإحصاء', icon: 'fas fa-chart-bar', color: '#4facfe' }
    };

    data.subjects.forEach(subject => {
        const subjectInfo = subjectNames[subject];
        if (!subjectInfo) return;

        // Create QR code data - in real implementation, this would include encrypted student info
        const qrData = JSON.stringify({
            studentPhone: data.phone,
            subject: subject,
            grade: data.grade,
            timestamp: Date.now(),
            accessToken: generateAccessToken(data.phone, subject)
        });

        // Generate access URL for QR code
        const accessUrl = `${window.location.origin}/senior${data.grade === 'senior1' ? '1' : '2'}/${subject}/qr-access/?qr=${encodeURIComponent(qrData)}`;

        const qrCard = document.createElement('div');
        qrCard.className = 'qr-code-card';
        qrCard.setAttribute('data-subject', subject);
        qrCard.onclick = () => showQRModal(subject, subjectInfo.name, qrData);

        qrCard.innerHTML = `
            <div class="qr-subject-icon">
                <i class="${subjectInfo.icon}"></i>
            </div>
            <h3>${subjectInfo.name}</h3>
            <p>اضغط لعرض رمز QR الخاص بمادة ${subjectInfo.name}</p>
            <div class="qr-code-display">
                <canvas id="qr-${subject}"></canvas>
            </div>
            <button class="qr-view-btn" onclick="openSubjectSessions('${subject}', '${data.grade}')">
                <i class="fas fa-external-link-alt"></i> الوصول إلى الجلسات
            </button>
        `;

        container.appendChild(qrCard);

        // Generate QR code with access URL
        const accessUrl = `${window.location.origin}/senior${data.grade === 'senior1' ? '1' : '2'}/${subject}/qr-access/?qr=${encodeURIComponent(qrData)}`;
        QRCode.toCanvas(document.getElementById(`qr-${subject}`), accessUrl, {
            width: 150,
            height: 150,
            color: {
                dark: '#000000',
                light: '#FFFFFF'
            }
        });
    });
}

// Generate access token (simplified for demo)
function generateAccessToken(phone, subject) {
    // In real implementation, this would be a proper JWT or encrypted token
    return btoa(`${phone}:${subject}:${Date.now()}`).substring(0, 16);
}

// Show QR code modal
function showQRModal(subject, subjectName, qrData) {
    const modal = document.getElementById('qrModal');
    const modalContent = document.getElementById('qrModalContent');

    modalContent.innerHTML = `
        <div style="text-align: center;">
            <h3 style="color: var(--student-primary); margin-bottom: 20px;">
                رمز QR لمادة ${subjectName}
            </h3>
            <div style="background: white; padding: 20px; border-radius: 10px; display: inline-block;">
                <canvas id="qr-modal-canvas"></canvas>
            </div>
            <p style="margin-top: 15px; color: var(--student-text-light);">
                امسح رمز QR هذا للوصول إلى جلسات ${subjectName}
            </p>
        </div>
    `;

    modal.style.display = 'block';

    // Generate larger QR code for modal
    const accessUrl = `${window.location.origin}/senior${data.grade === 'senior1' ? '1' : '2'}/${subject}/qr-access/?qr=${encodeURIComponent(qrData)}`;
    QRCode.toCanvas(document.getElementById('qr-modal-canvas'), accessUrl, {
        width: 250,
        height: 250,
        color: {
            dark: '#000000',
            light: '#FFFFFF'
        }
    });
}

// Close QR modal
function closeQRModal() {
    document.getElementById('qrModal').style.display = 'none';
}

// Open QR scanner
function openQRScanner() {
    window.open('../qr-scanner.html', '_blank');
}

// Open subject sessions directly
function openSubjectSessions(subject, grade) {
    const gradeNum = grade === 'senior1' ? '1' : '2';
    window.location.href = `../senior${gradeNum}/${subject}/sessions`;
}

// Load recent activity
function loadRecentActivity(data) {
    const container = document.getElementById('recentActivity');

    if (!data.recentActivity || data.recentActivity.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--student-text-light);">لا توجد أنشطة حديثة</p>';
        return;
    }

    container.innerHTML = '';

    data.recentActivity.forEach(activity => {
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';
        activityItem.setAttribute('data-type', activity.type);

        activityItem.innerHTML = `
            <div class="activity-icon">
                <i class="fas fa-${activity.type === 'watch' ? 'play-circle' : activity.type === 'download' ? 'download' : 'sign-in-alt'}"></i>
            </div>
            <div class="activity-content">
                <p>${activity.message}</p>
                <span class="activity-time">${activity.time}</span>
            </div>
        `;

        container.appendChild(activityItem);
    });
}

// Load available sessions
function loadAvailableSessions(data) {
    const container = document.getElementById('availableSessions');

    if (!data.availableSessions || data.availableSessions.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: var(--student-text-light);">لا توجد جلسات متاحة حالياً</p>';
        return;
    }

    container.innerHTML = '';

    const subjectNames = {
        physics: 'الفيزياء',
        mathematics: 'الرياضيات',
        statistics: 'الإحصاء'
    };

    data.availableSessions.forEach(session => {
        const sessionCard = document.createElement('div');
        sessionCard.className = 'session-card';

        sessionCard.innerHTML = `
            <h3>${session.title}</h3>
            <p><strong>المادة:</strong> ${subjectNames[session.subject] || session.subject}</p>
            <p><strong>آخر مشاهدة:</strong> ${new Date(session.lastWatched).toLocaleDateString('ar-EG')}</p>
            <div class="session-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${session.progress}%"></div>
                </div>
                <small>${session.progress}% مكتمل</small>
            </div>
            <button class="session-watch-btn" onclick="watchSession(${session.id})">
                <i class="fas fa-play"></i> متابعة المشاهدة
            </button>
        `;

        container.appendChild(sessionCard);
    });
}

// Watch session function
function watchSession(sessionId) {
    // In real implementation, this would redirect to the session player
    alert(`سيتم توجيهك إلى جلسة رقم ${sessionId}`);
}

// Loading functions
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

// Error handling
function showError(message) {
    hideLoading();
    alert(message);
}

// Handle modal close when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('qrModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};