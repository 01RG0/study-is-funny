# MongoDB Setup Instructions

## Step 1: MongoDB Atlas Setup

1. Go to https://www.mongodb.com/cloud/atlas
2. Sign up/Login to your account
3. Create a new cluster (Free tier is fine)
4. Create a database named: `attendance_system`

## Step 2: Create Collections

Create these collections in your database:
- `users` - For user accounts
- `content` - For educational content
- `progress` - For user progress tracking

## Step 3: Enable Data API

1. In MongoDB Atlas, go to **App Services** (or **Realm**)
2. Create a new App
3. Enable **Data API**
4. Get your:
   - **App ID**
   - **API Key**

## Step 4: Configure the Application

Edit `js/database.js` and update:

```javascript
const MONGODB_API_URL = 'https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1';
const MONGODB_API_KEY = 'YOUR_API_KEY';
```

Replace:
- `YOUR_APP_ID` with your App ID from Step 3
- `YOUR_API_KEY` with your API Key from Step 3

## Step 5: Test

1. Open `index.html` in your browser
2. Try registering a new user
3. Check MongoDB Atlas to see if data was saved

## Fallback Mode

If MongoDB API is not configured, the system automatically falls back to localStorage for development/testing.

## Connection String (For Reference)

Your MongoDB connection string:
```
mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0
```

This is used for direct MongoDB connections (if you add a backend later), but for frontend-only, use the Data API method above.
