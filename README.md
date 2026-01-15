# Study is Funny - Minimal MongoDB System

A minimal educational platform using HTML, CSS, JavaScript with MongoDB Atlas Data API.

## 📁 File Structure (Minimal)

```
study-is-funny/
├── index.html          # Single page application
├── js/
│   ├── database.js    # MongoDB API handler
│   └── app.js         # Main application logic
├── css/
│   └── main.css       # All styles
└── images/            # Assets
```

## 🗄️ MongoDB Setup

1. Go to MongoDB Atlas: https://www.mongodb.com/cloud/atlas
2. Create a Data API endpoint
3. Get your API Key
4. Update `js/database.js`:
   - Set `MONGODB_API_URL` to your Data API endpoint
   - Set `MONGODB_API_KEY` to your API key

## 🚀 Usage

1. Open `index.html` in a web browser
2. The system will use MongoDB Atlas Data API
3. If API is unavailable, it falls back to localStorage

## 📝 MongoDB Collections

- `users` - User accounts
- `content` - Educational content
- `progress` - User progress tracking

## ⚙️ Configuration

Edit `js/database.js` to configure MongoDB connection:
```javascript
const MONGODB_API_URL = 'YOUR_DATA_API_ENDPOINT';
const MONGODB_API_KEY = 'YOUR_API_KEY';
```
