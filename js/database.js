/**
 * MongoDB Database API Handler
 * Uses MongoDB Atlas Data API (REST) - No Node.js required!
 */

// PHP Backend API Configuration
// Now using PHP backend that connects directly to MongoDB using your connection string:
// mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0

const API_BASE_URL = 'api'; // Relative path to PHP API
const DATABASE_NAME = 'attendance_system';
const COLLECTION_USERS = 'users';
const COLLECTION_CONTENT = 'content';
const COLLECTION_PROGRESS = 'progress';

class MongoDB {
    static async request(endpoint, method = 'POST', data = null) {
        const payload = {
            dataSource: MONGODB_DATA_SOURCE,
            database: DATABASE_NAME,
            ...data
        };

        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'api-key': MONGODB_API_KEY
            },
            body: JSON.stringify(payload)
        };

        try {
            const response = await fetch(`${MONGODB_API_URL}${endpoint}`, options);
            const result = await response.json();

            if (!response.ok) {
                console.error('MongoDB API Error:', result);
                return { error: result, success: false };
            }

            return result;
        } catch (error) {
            console.error('MongoDB API Error:', error);
            // Fallback to localStorage if API fails
            return this.localStorageFallback(endpoint, method, data);
        }
    }

    static localStorageFallback(endpoint, method, data) {
        // Fallback to localStorage for offline/development
        const key = endpoint.replace('/action/', '');
        
        if (method === 'POST' && endpoint.includes('insertOne')) {
            const collection = data.collection;
            const doc = data.document;
            const storageKey = `mongodb_${collection}`;
            const existing = JSON.parse(localStorage.getItem(storageKey) || '[]');
            doc._id = Date.now().toString();
            existing.push(doc);
            localStorage.setItem(storageKey, JSON.stringify(existing));
            return { insertedId: doc._id };
        }
        
        if (method === 'POST' && endpoint.includes('findOne')) {
            const collection = data.collection;
            const filter = data.filter;
            const storageKey = `mongodb_${collection}`;
            const existing = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const found = existing.find(item => {
                return Object.keys(filter).every(key => item[key] === filter[key]);
            });
            return { document: found || null };
        }
        
        if (method === 'POST' && endpoint.includes('updateOne')) {
            const collection = data.collection;
            const filter = data.filter;
            const update = data.update;
            const storageKey = `mongodb_${collection}`;
            const existing = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const index = existing.findIndex(item => {
                return Object.keys(filter).every(key => item[key] === filter[key]);
            });
            if (index !== -1) {
                existing[index] = { ...existing[index], ...update.$set };
                localStorage.setItem(storageKey, JSON.stringify(existing));
                return { modifiedCount: 1 };
            }
            return { modifiedCount: 0 };
        }

        if (method === 'POST' && endpoint.includes('find')) {
            const collection = data.collection;
            const filter = data.filter || {};
            const storageKey = `mongodb_${collection}`;
            const existing = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const found = existing.filter(item => {
                return Object.keys(filter).every(key => item[key] === filter[key]);
            });
            return { documents: found };
        }

        return { error: 'Method not supported in fallback' };
    }

    // User Operations
    static async registerUser(name, phone, password, grade) {
        return await this.request('/action/insertOne', 'POST', {
            collection: COLLECTION_USERS,
            database: DATABASE_NAME,
            document: {
                name,
                phone,
                password,
                grade,
                createdAt: new Date().toISOString()
            }
        });
    }

    static async loginUser(phone, password) {
        return await this.request('/action/findOne', 'POST', {
            collection: COLLECTION_USERS,
            database: DATABASE_NAME,
            filter: { phone, password }
        });
    }

    // Content Operations
    static async getContent(grade, subject) {
        return await this.request('/action/findOne', 'POST', {
            collection: COLLECTION_CONTENT,
            database: DATABASE_NAME,
            filter: { grade, subject }
        });
    }

    static async saveContent(grade, subject, teacher, sessions) {
        return await this.request('/action/updateOne', 'POST', {
            collection: COLLECTION_CONTENT,
            database: DATABASE_NAME,
            filter: { grade, subject, teacher },
            update: {
                $set: {
                    grade,
                    subject,
                    teacher,
                    sessions,
                    updatedAt: new Date().toISOString()
                }
            },
            upsert: true
        });
    }

    // Progress Operations
    static async getProgress(userId, sessionId) {
        return await this.request('/action/findOne', 'POST', {
            collection: COLLECTION_PROGRESS,
            database: DATABASE_NAME,
            filter: { userId, sessionId }
        });
    }

    static async saveProgress(userId, sessionId, progress, completed) {
        return await this.request('/action/updateOne', 'POST', {
            collection: COLLECTION_PROGRESS,
            database: DATABASE_NAME,
            filter: { userId, sessionId },
            update: {
                $set: {
                    userId,
                    sessionId,
                    progress,
                    completed,
                    lastAccessed: new Date().toISOString()
                }
            },
            upsert: true
        });
    }

    static async getUserProgress(userId) {
        return await this.request('/action/find', 'POST', {
            collection: COLLECTION_PROGRESS,
            database: DATABASE_NAME,
            filter: { userId }
        });
    }
}

// Database selection logic
const USE_DEMO_MODE = MONGODB_API_KEY === 'YOUR_API_KEY_HERE' || MONGODB_API_URL.includes('YOUR_APP_ID');
const USE_FIREBASE = typeof firebase !== 'undefined' && firebase.apps.length > 0;

if (USE_FIREBASE) {
    console.log('🔥 Using Firebase Database');

    // Load Firebase database functions
    fetch('js/database-firebase.js')
        .then(response => response.text())
        .then(script => {
            const scriptElement = document.createElement('script');
            scriptElement.textContent = script;
            document.head.appendChild(scriptElement);

            // Override functions after Firebase database loads
            setTimeout(() => {
                window.getStudentData = FirebaseDB.getStudentData;
                window.MongoDB = {
                    registerUser: FirebaseDB.registerUser,
                    loginUser: FirebaseDB.loginUser,
                    request: async function(endpoint, data) {
                        if (endpoint.includes('find')) {
                            return FirebaseDB.getAllStudents();
                        }
                        return { documents: [] };
                    }
                };
            }, 100);
        });

} else if (USE_DEMO_MODE) {
    console.log('🔧 Using Demo Database - Configure Firebase for production');

    // Load demo database
    fetch('js/demo-database.js')
        .then(response => response.text())
        .then(script => {
            const scriptElement = document.createElement('script');
            scriptElement.textContent = script;
            document.head.appendChild(scriptElement);

            // Override functions after demo database loads
            setTimeout(() => {
                window.getStudentData = async function(phone) {
                    await new Promise(resolve => setTimeout(resolve, 500));
                    return DemoDatabase.getStudentData(phone);
                };

                window.MongoDB = {
                    registerUser: DemoDatabase.registerUser,
                    loginUser: DemoDatabase.loginUser,
                    request: async function() { return { documents: [] }; }
                };
            }, 100);
        });
} else {
    console.log('🚀 Using MongoDB Atlas Data API');
}

// Export for global use
window.MongoDB = window.MongoDB || MongoDB;
window.StudentManager = StudentManager;
