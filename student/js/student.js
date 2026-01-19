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
    localStorage.removeItem('studentPhone');
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
                    // Store student grade for session access
                    localStorage.setItem('studentGrade', studentData.grade);
                    localStorage.setItem('studentId', studentData.studentId || studentData._id);

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
        qrCard.onclick = () => showQRModal(subject, subjectInfo.name, qrData, data.grade);

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
function showQRModal(subject, subjectName, qrData, grade) {
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
    const accessUrl = `${window.location.origin}/senior${grade === 'senior1' ? '1' : '2'}/${subject}/qr-access/?qr=${encodeURIComponent(qrData)}`;
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

// Open sessions page
function openSessionsPage() {
    const userPhone = localStorage.getItem('userPhone');
    if (userPhone) {
        window.location.href = `sessions.html?phone=${encodeURIComponent(userPhone)}`;
    } else {
        showError('يرجى تسجيل الدخول أولاً');
    }
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

// Session Access Functions
async function checkSessionAccess(subject, sessionNumber) {
    const userPhone = localStorage.getItem('userPhone');
    const studentId = localStorage.getItem('studentId') || userPhone;
    const studentGrade = localStorage.getItem('studentGrade') || 'senior1';

    if (!userPhone) {
        showError('يرجى تسجيل الدخول أولاً');
        return false;
    }

    try {
        // Use the API to check session access
        const result = await checkStudentSessionAccess(studentId, sessionNumber, subject, studentGrade);

        if (result.success) {
            if (result.hasAccess) {
                return true;
            } else {
                if (result.isExpired) {
                    showError('انتهت صلاحية هذه الحصة');
                } else {
                    showError('هذه الحصة غير متاحة للوصول عبر الإنترنت');
                }
                return false;
            }
        } else {
            showError(result.message || 'لم يتم العثور على هذه الحصة');
            return false;
        }

    } catch (error) {
        console.error('Error checking session access:', error);
        showError('حدث خطأ في التحقق من صلاحية الوصول');
        return false;
    }
}

async function loadSessionContent(subject, sessionNumber) {
    try {
        const hasAccess = await checkSessionAccess(subject, sessionNumber);
        if (!hasAccess) return;

        // Get session content from API (this should return the session content if access is granted)
        const userPhone = localStorage.getItem('userPhone');
        const studentId = localStorage.getItem('studentId') || userPhone;
        const studentGrade = localStorage.getItem('studentGrade') || 'senior1';

        const result = await checkStudentSessionAccess(studentId, sessionNumber, subject, studentGrade);

        if (result.success && result.hasAccess && result.sessionContent) {
            displaySessionContent(result.sessionContent, sessionNumber);
        } else {
            showError('لم يتم العثور على محتوى الحصة');
        }

    } catch (error) {
        console.error('Error loading session content:', error);
        showError('حدث خطأ في تحميل محتوى الحصة');
    }
}

function getCurrentStudentGrade() {
    // This should be stored when student logs in
    return localStorage.getItem('studentGrade') || 'senior1';
}

function displaySessionContent(session, sessionNumber) {
    // Update session info
    const sessionInfo = document.querySelector('.session-info');
    if (sessionInfo) {
        sessionInfo.innerHTML = `
            <h2>${session.title} (الحصة ${sessionNumber})</h2>
            <div class="session-meta">
                <span>${session.subject}</span>
                <span>المعلم: ${session.teacher}</span>
                <span>المدة: ${calculateTotalDuration(session.videos)} دقيقة</span>
            </div>
        `;
    }

    // Load video content
    loadSessionVideos(session.videos);

    // Load materials
    loadSessionMaterials(session.pdfFiles);

    // Show session content
    document.querySelector('.session-content').style.display = 'block';
}

function calculateTotalDuration(videos) {
    if (!videos || videos.length === 0) return 0;
    return videos.reduce((sum, video) => sum + (video.duration || 0), 0);
}

function loadSessionVideos(videos) {
    const videoContainer = document.querySelector('.video-player');
    if (!videoContainer) return;

    videoContainer.innerHTML = '<h3>المحتوى المرئي</h3>';

    if (!videos || videos.length === 0) {
        videoContainer.innerHTML += '<p>لا يوجد محتوى مرئي لهذه الحصة</p>';
        return;
    }

    videos.forEach((video, index) => {
        const videoItem = document.createElement('div');
        videoItem.className = 'video-item';
        videoItem.innerHTML = `
            <h4>${video.title}</h4>
            <p>${video.description || 'لا يوجد وصف'}</p>
            <div class="video-controls">
                <button class="play-btn" onclick="playVideo('${video.file?.filename || ''}', ${index})">
                    <i class="fas fa-play"></i> تشغيل الفيديو
                </button>
                <span class="duration">${video.duration || 0} دقيقة</span>
            </div>
        `;
        videoContainer.appendChild(videoItem);
    });
}

function loadSessionMaterials(pdfFiles) {
    const materialsContainer = document.querySelector('.session-materials');
    if (!materialsContainer) return;

    materialsContainer.innerHTML = '<h3>المواد التعليمية</h3>';

    if (!pdfFiles || pdfFiles.length === 0) {
        materialsContainer.innerHTML += '<p>لا توجد مواد تعليمية إضافية</p>';
        return;
    }

    pdfFiles.forEach(pdf => {
        const materialItem = document.createElement('div');
        materialItem.className = 'material-item';
        materialItem.innerHTML = `
            <i class="fas fa-file-pdf"></i>
            <span>${pdf.originalName || pdf.filename}</span>
            <a href="${window.APP_BASE_URL}uploads/sessions/${pdf.filename}" target="_blank" class="download-btn">
                <i class="fas fa-download"></i> تحميل
            </a>
        `;
        materialsContainer.appendChild(materialItem);
    });
}

function playVideo(filename, index) {
    if (!filename) {
        showError('ملف الفيديو غير متوفر');
        return;
    }

    // Mark video items as inactive
    const videoItems = document.querySelectorAll('.video-item');
    videoItems.forEach(item => item.classList.remove('active'));
    videoItems[index].classList.add('active');

    // In production, this would load the actual video player
    showMessage('مشغل الفيديو سيفتح هنا: ' + filename, 'info');
}