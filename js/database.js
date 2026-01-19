// Helper to get the base path of the application
function getBasePath() {
    const scripts = document.getElementsByTagName('script');
    for (let script of scripts) {
        if (script.src.includes('js/database.js')) {
            return script.src.split('js/database.js')[0];
        }
    }
    return '';
}

const BASE_URL = getBasePath();
window.APP_BASE_URL = BASE_URL;
const API_BASE_URL = BASE_URL + 'api/';
const DATABASE_NAME = 'attendance_system';
const COLLECTION_USERS = 'users';
const COLLECTION_CONTENT = 'content';
const COLLECTION_PROGRESS = 'progress';

// Check if MongoDB Atlas API is configured
const USE_MONGODB_API = typeof MONGODB_API_URL !== 'undefined' &&
    typeof MONGODB_API_KEY !== 'undefined' &&
    typeof MONGODB_DATA_SOURCE !== 'undefined';

// Use demo mode if MongoDB API is not configured
const USE_DEMO_MODE = !USE_MONGODB_API;

// Initialization state
let dbInitialized = false;
let dbInitializationPromise = null;

async function initDatabase() {
    if (dbInitializationPromise) return dbInitializationPromise;

    dbInitializationPromise = new Promise(async (resolve) => {
        console.log('🔧 Initializing Database System...', { USE_DEMO_MODE, USE_FIREBASE: typeof firebase !== 'undefined' });

        if (USE_DEMO_MODE) {
            try {
                const response = await fetch(`${BASE_URL}js/demo-database.js`);
                const scriptText = await response.text();
                const scriptElement = document.createElement('script');
                scriptElement.textContent = scriptText;
                document.head.appendChild(scriptElement);

                // Wait for script to be processed
                let attempts = 0;
                const checkDemo = setInterval(() => {
                    if (typeof DemoDatabase !== 'undefined' || attempts > 20) {
                        clearInterval(checkDemo);
                        console.log('✅ Demo Database Loaded');
                        resolve();
                    }
                    attempts++;
                }, 50);
            } catch (e) {
                console.error('Failed to load demo database:', e);
                resolve();
            }
        } else {
            resolve();
        }
    });

    return dbInitializationPromise;
}

// Start initialization immediately
initDatabase();

class MongoDB {
    static async request(endpoint, method = 'POST', data = null) {
        await initDatabase();

        // If MongoDB API is not configured, skip to PHP API fallback
        if (!USE_MONGODB_API) {
            return await this.phpApiFallback(endpoint, method, data) ||
                this.localStorageFallback(endpoint, method, data);
        }

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
            return await this.phpApiFallback(endpoint, method, data) ||
                this.localStorageFallback(endpoint, method, data);
        }
    }

    static async phpApiFallback(endpoint, method, data) {
        try {
            if (endpoint.includes('findOne') && data.collection === 'users') {
                const phone = data.filter.phone;
                const response = await fetch(`${API_BASE_URL}students.php?action=get&phone=${encodeURIComponent(phone)}`);
                const text = await response.text();
                if (text.includes('<?php')) return null;

                const result = JSON.parse(text);
                if (result.success) return { document: result.student };
            }
            return null;
        } catch (error) {
            return null;
        }
    }

    static localStorageFallback(endpoint, method, data) {
        if (typeof DemoDatabase !== 'undefined') {
            // Check demo data first if in demo mode
            if (endpoint.includes('findOne') && data.collection === 'users') {
                const found = DemoDatabase.getStudentData(data.filter.phone);
                if (found) return { document: found };
            }
        }
        return { error: 'Not found' };
    }
}

// Global functions
window.getStudentData = async function (phone) {
    await initDatabase();
    phone = phone.trim(); // Ensure no spaces
    console.log(`📞 getStudentData called with phone: "${phone}"`);

    // 1. Try PHP API (Primary source)
    console.log(`🌐 Attempting PHP API fetch for: ${phone}`);
    console.log(`🔗 API URL: ${API_BASE_URL}students.php?action=get&phone=${encodeURIComponent(phone)}`);

    try {
        const response = await fetch(`${API_BASE_URL}students.php?action=get&phone=${encodeURIComponent(phone)}`);
        console.log(`📡 Response status: ${response.status}`);
        const text = await response.text();
        console.log(`📄 Response text (first 200 chars):`, text.substring(0, 200));

        // Detect if the server is returning PHP source code
        if (text.includes('<?php')) {
            console.warn('⚠️ Server returned raw PHP. Check your PHP server setup.');
        } else {
            try {
                const data = JSON.parse(text);
                console.log(`🔍 Parsed JSON:`, data);
                if (data.success && data.student) {
                    console.log('✅ Found student via PHP API:', data.student);
                    return data.student;
                } else {
                    console.warn('❌ API returned success=false:', data.message || 'Unknown error');
                }
            } catch (parseError) {
                console.error('❌ Failed to parse API response as JSON:', parseError);
                console.error('Raw response:', text.substring(0, 100));
            }
        }
    } catch (error) {
        console.error('❌ PHP API Fetch failed with error:', error);
    }

    // 2. Try Demo Database (Fallback only)
    console.log('⚠️ Falling back to Demo Database');
    if (typeof DemoDatabase !== 'undefined') {
        try {
            const demoData = await DemoDatabase.getStudentData(phone);
            if (demoData) {
                console.log('✅ Found student in Demo Database (Fallback):', demoData);
                return demoData;
            } else {
                console.log('❌ No student found in Demo Database');
            }
        } catch (e) {
            console.warn('Demo database error:', e);
        }
    } else {
        console.log('❌ DemoDatabase not available');
    }

    console.error('❌ Student not found in any source');
    return null;
};

// Session Management Functions
window.createSession = async function(sessionData) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=create`, {
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
};

window.getSession = async function(sessionId) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=get&id=${sessionId}`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error fetching session:', error);
        return { success: false, message: 'Network error occurred' };
    }
};

window.getAllSessions = async function(filters = {}) {
    try {
        const queryParams = new URLSearchParams(filters);
        const response = await fetch(`${API_BASE_URL}sessions.php?action=all&${queryParams}`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error fetching sessions:', error);
        return { success: false, message: 'Network error occurred' };
    }
};

window.updateSession = async function(sessionId, updates) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=update`, {
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
};

window.deleteSession = async function(sessionId) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=delete`, {
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
};

window.publishSession = async function(sessionId) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=publish`, {
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
};

window.unpublishSession = async function(sessionId) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=unpublish`, {
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
};

window.getSessionStats = async function() {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=stats`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error fetching session stats:', error);
        return { success: false, message: 'Network error occurred' };
    }
};

window.checkStudentSessionAccess = async function(studentId, sessionNumber, subject, grade) {
    try {
        const response = await fetch(`${API_BASE_URL}sessions.php?action=check-access&studentId=${studentId}&sessionNumber=${sessionNumber}&subject=${subject}&grade=${grade}`);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Error checking session access:', error);
        return { success: false, message: 'Network error' };
    }
};

window.MongoDB = MongoDB;


