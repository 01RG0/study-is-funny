/**
 * Demo Database - Local JSON fallback for testing
 * Replace with actual MongoDB Atlas Data API when configured
 */

// Demo student data - Your phone number included!
const DEMO_STUDENTS = [
    {
        "_id": "507f1f77bcf86cd799439011",
        "name": "أحمد محمد",
        "phone": "01280912031", // Your phone number!
        "password": "123456",
        "grade": "senior1",
        "subjects": ["physics", "mathematics", "statistics"],
        "joinDate": "2024-01-15T10:00:00Z",
        "lastLogin": "2024-01-16T14:30:00Z",
        "isActive": true,
        "totalSessionsViewed": 5,
        "totalWatchTime": 180
    },
    {
        "_id": "507f1f77bcf86cd799439012",
        "name": "فاطمة أحمد",
        "phone": "01234567890",
        "password": "123456",
        "grade": "senior2",
        "subjects": ["physics", "mathematics"],
        "joinDate": "2024-01-10T09:00:00Z",
        "lastLogin": "2024-01-15T16:20:00Z",
        "isActive": true,
        "totalSessionsViewed": 8,
        "totalWatchTime": 240
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
            "subjects": grade === 'senior1' ? ["physics", "mathematics", "statistics"] : ["physics", "mathematics", "statistics"],
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
}

function formatWatchTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}

// Export for global use
window.DemoDatabase = DemoDatabase;