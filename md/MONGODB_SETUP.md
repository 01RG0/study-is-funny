# MongoDB Atlas Data API Setup Guide

## 🚀 Quick Setup for Study is Funny

### Step 1: Enable Data API in MongoDB Atlas
1. Go to [MongoDB Atlas](https://cloud.mongodb.com/)
2. Login to your account
3. Select your project: `attendance_system`
4. Go to **Data API** in the left sidebar
5. Click **Enable the Data API**
6. Create a new API Key:
   - Click **Create API Key**
   - Name: `studyisfunny-api-key`
   - Copy the API Key (save it securely!)
7. Copy your **App ID** from the URL (looks like: `your-app-id`)

### Step 2: Configure Your Database.js File
Update `js/database.js` with your credentials:

```javascript
const MONGODB_API_URL = 'https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1';
const MONGODB_API_KEY = 'YOUR_API_KEY_HERE';
```

### Step 3: Your Connection Details
- **Connection String**: `mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/attendance_system?appName=Cluster0`
- **Data Source**: `Cluster0`
- **Database**: `attendance_system`

### Step 4: Test the Connection
1. Open `student/index.html` in your browser
2. Try registering a new student
3. Check browser console for any errors

## 📋 Required Collections

Your database should have these collections:
- `users` - Student information
- `content` - Session content data
- `progress` - Student progress tracking

## 🔧 API Endpoints Used

- `POST /action/insertOne` - Register new users
- `POST /action/findOne` - Login and get user data
- `POST /action/updateOne` - Update user progress
- `POST /action/find` - Get lists of data

## 🛠️ Troubleshooting

### CORS Issues
Make sure your Data API allows requests from your domain.

### Authentication Errors
- Check your API Key is correct
- Verify your App ID
- Ensure Data API is enabled

### Data Not Saving
- Check collection names match
- Verify database permissions
- Check network connectivity

## 🔒 Security Notes

- Never commit API keys to version control
- Use HTTPS in production
- Consider IP whitelisting for additional security
- Rotate API keys regularly

## 📞 Support

If you need help:
1. Check [MongoDB Data API Documentation](https://docs.mongodb.com/data-api/)
2. Verify your Atlas cluster is running
3. Test API key permissions