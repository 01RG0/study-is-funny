/**
 * Firebase Database Alternative
 * Uses Firebase instead of MongoDB Atlas Data API
 */

// Firebase Configuration
const firebaseConfig = {
    apiKey: "AIzaSyC7GD79H7F-DT8H-sl75uBtes5N6AxlMOQ",
    authDomain: "studyisfunny-a12a7.firebaseapp.com",
    databaseURL: "https://studyisfunny-a12a7-default-rtdb.firebaseio.com",
    projectId: "studyisfunny-a12a7",
    storageBucket: "studyisfunny-a12a7.appspot.com",
    messagingSenderId: "325862354665",
    appId: "1:325862354665:web:fe17ba82c8f226944a8a78",
    measurementId: "G-NPF83VEYXK"
};

// Initialize Firebase (only if not already initialized)
if (!firebase.apps.length) {
    firebase.initializeApp(firebaseConfig);
}

const database = firebase.database();

class FirebaseDB {
    static async registerUser(name, phone, password, grade) {
        try {
            // Check if user exists
            const userRef = database.ref('users/' + phone);
            const snapshot = await userRef.once('value');

            if (snapshot.exists()) {
                return { success: false, message: 'User already exists' };
            }

            // Create new user
            const userData = {
                name: name,
                phone: phone,
                password: password,
                grade: grade,
                subjects: grade === 'senior1' ? ["physics", "mathematics", "statistics"] : ["physics", "mathematics", "statistics"],
                joinDate: new Date().toISOString(),
                lastLogin: new Date().toISOString(),
                isActive: true,
                totalSessionsViewed: 0,
                totalWatchTime: 0
            };

            await userRef.set(userData);
            return { success: true, message: 'User registered successfully!' };

        } catch (error) {
            console.error('Firebase register error:', error);
            return { success: false, message: error.message };
        }
    }

    static async loginUser(phone, password) {
        try {
            const userRef = database.ref('users/' + phone);
            const snapshot = await userRef.once('value');

            if (snapshot.exists()) {
                const userData = snapshot.val();

                if (userData.password === password && userData.isActive) {
                    // Update last login
                    await userRef.update({ lastLogin: new Date().toISOString() });

                    return {
                        success: true,
                        user: { ...userData, _id: phone },
                        message: 'Login successful!'
                    };
                }
            }

            return { success: false, message: 'Invalid phone or password' };

        } catch (error) {
            console.error('Firebase login error:', error);
            return { success: false, message: error.message };
        }
    }

    static async getStudentData(phone) {
        try {
            const userRef = database.ref('users/' + phone);
            const snapshot = await userRef.once('value');

            if (snapshot.exists()) {
                const user = snapshot.val();

                // Calculate stats
                user._id = phone;
                user.totalSessions = user.subjects ? user.subjects.length * 5 : 0;
                user.watchedSessions = user.totalSessionsViewed || 0;
                user.totalWatchTime = formatWatchTime(user.totalWatchTime || 0);

                return user;
            }
            return null;

        } catch (error) {
            console.error('Firebase get student error:', error);
            return null;
        }
    }

    static async getAllStudents() {
        try {
            const usersRef = database.ref('users');
            const snapshot = await usersRef.once('value');

            const students = [];
            snapshot.forEach((childSnapshot) => {
                const user = childSnapshot.val();
                if (user.isActive) {
                    students.push({ ...user, _id: childSnapshot.key });
                }
            });

            return { documents: students };

        } catch (error) {
            console.error('Firebase get all students error:', error);
            return { documents: [] };
        }
    }

    static async updateStudent(phone, updates) {
        try {
            const userRef = database.ref('users/' + phone);
            await userRef.update(updates);
            return { modifiedCount: 1 };

        } catch (error) {
            console.error('Firebase update student error:', error);
            return { modifiedCount: 0 };
        }
    }
}

function formatWatchTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}

// Export for global use
window.FirebaseDB = FirebaseDB;