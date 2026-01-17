# 🚀 Quick MongoDB Setup for QR Codes

## Your Current Status
- ✅ You have MongoDB connection string: `mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0`
- ❌ Missing: MongoDB Atlas Data API credentials

## What You Need (2 minutes)

### Step 1: Enable Data API
1. Go to [MongoDB Atlas](https://cloud.mongodb.com/)
2. Login to your account
3. Select `attendance_system` project
4. Click **"Data API"** in left menu
5. Click **"Enable the Data API"**

### Step 2: Get API Key
1. Click **"Create API Key"**
2. Name: `studyisfunny-key`
3. **COPY THE API KEY** (you can only see it once!)

### Step 3: Get App ID
Look at your browser URL, it should be:
```
https://cloud.mongodb.com/v2/.../app/YOUR_APP_ID_HERE/dataAPI
```
Copy the `YOUR_APP_ID_HERE` part.

### Step 4: Update js/database.js
Replace these lines:
```javascript
const MONGODB_API_URL = 'https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1';
const MONGODB_API_KEY = 'YOUR_API_KEY_HERE';
```

With your actual values:
```javascript
const MONGODB_API_URL = 'https://data.mongodb-api.com/app/63f1a2b3c4d5e6f7g8h9i0j/endpoint/data/v1';
const MONGODB_API_KEY = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
```

## Test It Works
1. Open `grade/index.html`
2. Login with phone: `01280912038`
3. QR codes should appear!

## Why This is Needed

**Your connection string** = Server access (like Node.js)
**Data API credentials** = Client access (HTML/JS only)

Both are needed for full functionality! 🔑

## Demo Mode (Works Now!)
Currently using demo data so QR codes work immediately.
Replace with real MongoDB when ready.

---
**Time needed: 2 minutes** ⏱️