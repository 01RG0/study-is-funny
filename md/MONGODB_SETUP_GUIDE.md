# 🚀 MongoDB Atlas Data API Setup Guide - Study is Funny

## Step 1: Enable Data API in MongoDB Atlas

### 1.1 Go to MongoDB Atlas
- Open [MongoDB Atlas](https://cloud.mongodb.com/)
- Sign in to your account

### 1.2 Navigate to Data API
- Select your project: `attendance_system`
- Click **Data API** in the left sidebar
- Click **Enable the Data API**

### 1.3 Create API Key
- Click **Create API Key**
- Name: `studyisfunny-api-key`
- **Copy the API Key** (save it securely!)
- Note: You can only see the API Key once!

### 1.4 Get Your App ID
- Look at your browser URL, it should look like:
  `https://cloud.mongodb.com/v2/YOUR_PROJECT_ID/app/YOUR_APP_ID/dataAPI`
- Copy the `YOUR_APP_ID` part from the URL

## Step 2: Update Configuration

### 2.1 Edit js/database.js
Replace the placeholder values:

```javascript
// MongoDB Atlas Data API Configuration
const MONGODB_API_URL = 'https://data.mongodb-api.com/app/YOUR_ACTUAL_APP_ID/endpoint/data/v1';
const MONGODB_API_KEY = 'YOUR_ACTUAL_API_KEY';
const MONGODB_DATA_SOURCE = 'Cluster0';
```

**Example:**
```javascript
const MONGODB_API_URL = 'https://data.mongodb-api.com/app/63f1a2b3c4d5e6f7g8h9i0j/endpoint/data/v1';
const MONGODB_API_KEY = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
const MONGODB_DATA_SOURCE = 'Cluster0';
```

## Step 3: Test the Connection

### 3.1 Open Test Page
- Open `test-mongodb.html` in your browser
- Click **"اختبر الاتصال الأساسي"**

### 3.2 Check Results
- ✅ Success: Connection working
- ❌ Error: Check your credentials

## Step 4: Add Your Student Data

### 4.1 Create Student Document
Use MongoDB Atlas to add your student:

```json
{
  "name": "اسم الطالب",
  "phone": "01280912038",
  "password": "student_password",
  "grade": "senior1",
  "subjects": ["physics", "mathematics", "statistics"],
  "joinDate": "2024-01-15T10:00:00Z",
  "lastLogin": "2024-01-16T14:30:00Z",
  "isActive": true,
  "totalSessionsViewed": 0,
  "totalWatchTime": 0
}
```

### 4.2 Insert via Atlas
- Go to **Collections** in Atlas
- Select `attendance_system` database
- Select `users` collection
- Click **Insert Document**
- Paste the JSON above
- Click **Insert**

## Step 5: Test Student Login

### 5.1 Register/Login
- Open `register/index.html`
- Register with your phone: `01280912038`
- Or login if already registered

### 5.2 Test QR Codes
- Go to `grade/index.html`
- QR codes should load automatically

## Troubleshooting

### ❌ "API Key not valid"
- Check if Data API is enabled
- Verify API key is correct
- Ensure API key has proper permissions

### ❌ "App ID not found"
- Double-check the App ID in your Atlas URL
- Make sure you're in the correct project

### ❌ "No documents found"
- Verify collection name: `users`
- Check if student data exists
- Confirm phone number format

### ❌ "CORS error"
- Data API should handle CORS automatically
- Try a different browser
- Check network connectivity

## Your Current Setup

Based on your connection string:
```
mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0
```

- **Cluster**: `Cluster0`
- **Database**: `attendance_system`
- **Data Source**: `Cluster0`

## Quick Test Commands

### Test API Connection
```bash
curl -X POST "https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1/action/findOne" \
  -H "Content-Type: application/json" \
  -H "api-key: YOUR_API_KEY" \
  -d '{"dataSource": "Cluster0", "database": "attendance_system", "collection": "users"}'
```

### Find Your Student
```bash
curl -X POST "https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1/action/findOne" \
  -H "Content-Type: application/json" \
  -H "api-key: YOUR_API_KEY" \
  -d '{"dataSource": "Cluster0", "database": "attendance_system", "collection": "users", "filter": {"phone": "01280912038"}}'
```

## Security Notes

- 🔒 **Never commit API keys** to version control
- 🔒 **Use HTTPS** in production
- 🔒 **Restrict API key permissions** to read/write only
- 🔒 **Rotate API keys** regularly

## Support

If you need help:
1. Check [MongoDB Data API Docs](https://docs.mongodb.com/data-api/)
2. Test with the provided `test-mongodb.html`
3. Verify credentials in Atlas dashboard
4. Check browser console for detailed errors

---

**Your Phone Number**: `01280912038`
**Ready to test once credentials are configured!** 🎯