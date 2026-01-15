/**
 * MongoDB Database API Handler
 * Uses MongoDB Atlas Data API (REST) - No Node.js required!
 */

const MONGODB_API_URL = 'https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1';
const MONGODB_API_KEY = 'YOUR_API_KEY';
const DATABASE_NAME = 'attendance_system';
const COLLECTION_USERS = 'users';
const COLLECTION_CONTENT = 'content';
const COLLECTION_PROGRESS = 'progress';

class MongoDB {
    static async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'api-key': MONGODB_API_KEY
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${MONGODB_API_URL}${endpoint}`, options);
            return await response.json();
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
