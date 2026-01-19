/**
 * Demo Database - Local JSON fallback for testing
 * Replace with actual MongoDB Atlas Data API when configured
 */

// Demo student data - Your phone number included!
const DEMO_STUDENTS = [
    {
        "_id": "507f1f77bcf86cd799439012",
        "name": "فاطمة أحمد",
        "phone": "01234567891",
        "password": "123456",
        "grade": "senior2",
        "subjects": ["physics", "mathematics"],
        "joinDate": "2024-01-10T09:00:00Z",
        "lastLogin": "2024-01-15T16:20:00Z",
        "isActive": true,
        "totalSessionsViewed": 3,
        "totalWatchTime": 120
    },
    {
        "_id": "507f1f77bcf86cd799439013",
        "name": "محمد علي",
        "phone": "01111111111",
        "password": "123456",
        "grade": "senior1",
        "subjects": ["statistics"],
        "joinDate": "2024-01-12T11:30:00Z",
        "lastLogin": "2024-01-14T13:15:00Z",
        "isActive": true,
        "totalSessionsViewed": 2,
        "totalWatchTime": 90
    }
];

// Demo sessions data
const DEMO_SESSIONS = [
    {
        "_id": "sess_001",
        "title": "Introduction to Mechanics",
        "subject": "physics",
        "grade": "senior1",
        "teacher": "shadyelsharqawy",
        "description": "Fundamental concepts of mechanics including motion, forces, and energy",
        "sessionNumber": 1,
        "videos": [
            {
                "type": "lecture",
                "title": "Part 1 - Motion and Forces",
                "description": "Understanding basic motion and force concepts",
                "duration": 45,
                "file": { "filename": "mechanics_lecture1.mp4", "size": 50000000 }
            },
            {
                "type": "exercise",
                "title": "Practice Problems",
                "description": "Solving mechanics problems",
                "duration": 30,
                "file": { "filename": "mechanics_exercise1.mp4", "size": 30000000 }
            }
        ],
        "pdfFiles": [
            { "filename": "mechanics_notes.pdf", "originalName": "Mechanics Notes.pdf", "size": 2000000 }
        ],
        "tags": ["mechanics", "physics", "motion", "forces"],
        "difficulty": "intermediate",
        "status": "published",
        "isPublished": true,
        "isFeatured": false,
        "publishDate": "2024-01-15T10:00:00Z",
        "maxViews": null,
        "downloadable": true,
        "allowedStudentTypes": ["all"],
        "views": 245,
        "downloads": 12,
        "rating": 4.8,
        "ratingCount": 45,
        "createdAt": "2024-01-10T09:00:00Z",
        "updatedAt": "2024-01-15T10:00:00Z",
        "createdBy": "admin",
        "isActive": true
    },
    {
        "_id": "sess_002",
        "title": "Algebra Fundamentals",
        "subject": "mathematics",
        "grade": "senior1",
        "teacher": "shadyelsharqawy",
        "description": "Basic algebra concepts including equations, inequalities, and functions",
        "videos": [
            {
                "type": "lecture",
                "title": "Linear Equations",
                "description": "Solving linear equations and inequalities",
                "duration": 50,
                "file": { "filename": "algebra_lecture1.mp4", "size": 55000000 }
            }
        ],
        "pdfFiles": [],
        "tags": ["algebra", "mathematics", "equations", "functions"],
        "difficulty": "beginner",
        "status": "published",
        "isPublished": true,
        "isFeatured": true,
        "publishDate": "2024-01-14T09:00:00Z",
        "maxViews": null,
        "downloadable": true,
        "allowedStudentTypes": ["all"],
        "views": 189,
        "downloads": 8,
        "rating": 4.6,
        "ratingCount": 32,
        "createdAt": "2024-01-12T11:00:00Z",
        "updatedAt": "2024-01-14T09:00:00Z",
        "createdBy": "admin",
        "isActive": true
    },
    {
        "_id": "sess_003",
        "title": "Statistics Basics",
        "subject": "statistics",
        "grade": "senior2",
        "teacher": "shadyelsharqawy",
        "description": "Introduction to statistical concepts and data analysis",
        "videos": [],
        "pdfFiles": [],
        "tags": ["statistics", "data", "analysis"],
        "difficulty": "intermediate",
        "status": "draft",
        "isPublished": false,
        "isFeatured": false,
        "publishDate": null,
        "maxViews": null,
        "downloadable": true,
        "allowedStudentTypes": ["senior2"],
        "views": 0,
        "downloads": 0,
        "rating": 0,
        "ratingCount": 0,
        "createdAt": "2024-01-13T14:00:00Z",
        "updatedAt": "2024-01-13T14:00:00Z",
        "createdBy": "admin",
        "isActive": true
    }
];

class DemoDatabase {
    static async registerUser(name, phone, password, grade) {
        // Check if user already exists
        const existingUser = DEMO_STUDENTS.find(student => student.phone === phone);
        if (existingUser) {
            return { success: false, message: 'User already exists' };
        }

        // Create new user
        const newUser = {
            "_id": Date.now().toString(),
            "name": name,
            "phone": phone,
            "password": password,
            "grade": grade,
            "subjects": grade === 'senior1' ? ["physics", "mathematics", "mechanics"] : ["physics", "mathematics", "mechanics"],
            "joinDate": new Date().toISOString(),
            "lastLogin": new Date().toISOString(),
            "isActive": true,
            "totalSessionsViewed": 0,
            "totalWatchTime": 0
        };

        DEMO_STUDENTS.push(newUser);
        return { success: true, message: 'User registered successfully!' };
    }

    static async loginUser(phone, password) {
        const user = DEMO_STUDENTS.find(student =>
            student.phone === phone &&
            student.password === password &&
            student.isActive
        );

        if (user) {
            // Update last login
            user.lastLogin = new Date().toISOString();

            return {
                success: true,
                user: user,
                message: 'Login successful!'
            };
        } else {
            return { success: false, message: 'Invalid phone or password' };
        }
    }

    static async getStudentData(phone) {
        const user = DEMO_STUDENTS.find(student =>
            student.phone === phone && student.isActive
        );

        if (user) {
            // Calculate stats
            user.totalSessions = user.subjects ? user.subjects.length * 5 : 0;
            user.watchedSessions = user.totalSessionsViewed || 0;
            user.totalWatchTime = formatWatchTime(user.totalWatchTime || 0);

            return user;
        }
        return null;
    }

    static async getAllStudents() {
        return {
            documents: DEMO_STUDENTS.filter(student => student.isActive)
        };
    }

    static async updateStudent(studentId, updates) {
        const userIndex = DEMO_STUDENTS.findIndex(student => student._id === studentId);
        if (userIndex !== -1) {
            Object.assign(DEMO_STUDENTS[userIndex], updates);
            return { modifiedCount: 1 };
        }
        return { modifiedCount: 0 };
    }

    // Session Management Functions
    static async createSession(sessionData) {
        const newSession = {
            "_id": "sess_" + Date.now(),
            ...sessionData,
            "createdAt": new Date().toISOString(),
            "updatedAt": new Date().toISOString(),
            "isActive": true,
            "views": 0,
            "downloads": 0,
            "rating": 0,
            "ratingCount": 0
        };

        DEMO_SESSIONS.push(newSession);
        return { success: true, sessionId: newSession._id, message: 'Session created successfully!' };
    }

    static async getSession(sessionId) {
        const session = DEMO_SESSIONS.find(s => s._id === sessionId && s.isActive);
        if (session) {
            return { success: true, session: session };
        }
        return { success: false, message: 'Session not found' };
    }

    static async getAllSessions(filters = {}) {
        let sessions = DEMO_SESSIONS.filter(s => s.isActive);

        // Apply filters
        if (filters.subject) {
            sessions = sessions.filter(s => s.subject === filters.subject);
        }
        if (filters.grade) {
            sessions = sessions.filter(s => s.grade === filters.grade);
        }
        if (filters.status) {
            sessions = sessions.filter(s => s.status === filters.status);
        }
        if (filters.teacher) {
            sessions = sessions.filter(s => s.teacher === filters.teacher);
        }

        return { success: true, sessions: sessions, count: sessions.length };
    }

    static async updateSession(sessionId, updates) {
        const sessionIndex = DEMO_SESSIONS.findIndex(s => s._id === sessionId);
        if (sessionIndex !== -1) {
            Object.assign(DEMO_SESSIONS[sessionIndex], updates, { updatedAt: new Date().toISOString() });
            return { success: true, modifiedCount: 1, message: 'Session updated successfully' };
        }
        return { success: false, modifiedCount: 0, message: 'Session not found' };
    }

    static async deleteSession(sessionId) {
        const sessionIndex = DEMO_SESSIONS.findIndex(s => s._id === sessionId);
        if (sessionIndex !== -1) {
            DEMO_SESSIONS[sessionIndex].isActive = false;
            DEMO_SESSIONS[sessionIndex].updatedAt = new Date().toISOString();
            return { success: true, modifiedCount: 1, message: 'Session deleted successfully' };
        }
        return { success: false, modifiedCount: 0, message: 'Session not found' };
    }

    static async publishSession(sessionId) {
        return this.updateSession(sessionId, {
            status: 'published',
            isPublished: true
        });
    }

    static async unpublishSession(sessionId) {
        return this.updateSession(sessionId, {
            status: 'draft',
            isPublished: false
        });
    }

    static async getSessionStats() {
        const totalSessions = DEMO_SESSIONS.filter(s => s.isActive).length;
        const publishedSessions = DEMO_SESSIONS.filter(s => s.isActive && s.status === 'published').length;
        const totalViews = DEMO_SESSIONS.reduce((sum, s) => sum + (s.views || 0), 0);
        const totalDownloads = DEMO_SESSIONS.reduce((sum, s) => sum + (s.downloads || 0), 0);

        return {
            success: true,
            stats: {
                totalSessions,
                publishedSessions,
                draftSessions: totalSessions - publishedSessions,
                totalViews,
                totalDownloads
            }
        };
    }
}

function formatWatchTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}

// Export for global use
window.DemoDatabase = DemoDatabase;