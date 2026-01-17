# Study is Funny - Educational Platform 🚀

A comprehensive educational platform for secondary students featuring interactive learning with MongoDB Atlas Data API integration.

## 📁 Project Structure

```
study-is-funny/
├── index.html                 # Main application entry point
├── package.json              # Project configuration and scripts
├── run.bat                   # Windows startup script
├── run.sh                    # Linux/Mac startup script
├── run.ps1                   # PowerShell startup script
├── README.md                 # Main project README
├── README_PROJECT.md         # This file
├── md/                       # Documentation folder
│   ├── MONGODB_SETUP_GUIDE.md
│   ├── MONGODB_SETUP.md
│   ├── QUICK_SETUP.md
│   └── SETUP.md
│
├── admin/                    # Admin dashboard
│   ├── dashboard.html
│   ├── analytics.html
│   ├── manage-students.html
│   └── css/, js/
│
├── student/                  # Student interface
│   ├── index.html
│   └── css/, js/
│
├── senior1/ & senior2/       # Grade-specific content
│   ├── mathematics/
│   ├── physics/
│   └── statistics/
│
├── api/                      # Backend API endpoints
│   ├── config.php
│   └── students.php
│
├── js/                       # JavaScript files
│   ├── database.js           # MongoDB Data API handler
│   ├── database-firebase.js  # Firebase backup
│   ├── main.js               # Main application logic
│   └── router.js             # Client-side routing
│
├── css/                      # Stylesheets
│   ├── main.css
│   └── all.min.css (FontAwesome)
│
├── login/ & register/        # Authentication pages
├── qr-scanner.html           # QR code functionality
├── tests/                    # Test files
│   ├── test-grade-qr.html    # QR testing
│   └── test-mongodb.html     # MongoDB testing
└── images/                   # Static assets
```

## 🚀 Quick Start

### Method 1: Run Scripts (Recommended)

#### Windows
```bash
# Double-click run.bat or run in command prompt
run.bat
```

#### PowerShell
```powershell
.\run.ps1
```

#### Linux/Mac
```bash
chmod +x run.sh
./run.sh
```

### Method 2: npm scripts (Recommended)
```bash
# Install dependencies (optional)
npm install

# Start server with automatic browser opening
npm start
# or
npm run dev
# or
npm run serve

# Start and open specific pages
npm run admin      # Opens admin dashboard
npm run student    # Opens student portal
npm run qr         # Opens QR scanner
npm run test-qr    # Opens QR test page
npm run test-mongo # Opens MongoDB test page
```

### Method 3: Using Custom Python Server
```bash
# Start server (automatically opens browser)
python server.py

# Start on different port
python server.py 8080

# Start and open specific page
python server.py admin
```

### Method 4: Manual Python Server (if needed)
```bash
# Python 3
python -m http.server 8000

# Python 2
python -m SimpleHTTPServer 8000
```

### Method 5: Any Static Server
```bash
# Using Node.js (if available)
npx serve . -p 8000

# Using PHP (if available)
php -S localhost:8000
```

## 🌐 Access URLs

Once running, access these URLs:

- **Main App**: http://localhost:8000
- **Admin Dashboard**: http://localhost:8000/admin/dashboard.html
- **Student Portal**: http://localhost:8000/student/index.html
- **QR Scanner**: http://localhost:8000/qr-scanner.html
- **Login**: http://localhost:8000/login/index.html
- **Senior 1**: http://localhost:8000/senior1/index.html
- **Senior 2**: http://localhost:8000/senior2/index.html
- **Test QR**: http://localhost:8000/tests/test-grade-qr.html
- **Test MongoDB**: http://localhost:8000/tests/test-mongodb.html

## ⚙️ MongoDB Configuration

### For Full Functionality
1. Go to [MongoDB Atlas](https://cloud.mongodb.com/)
2. Create Data API endpoint
3. Update `js/database.js`:
   ```javascript
   const MONGODB_API_URL = 'https://data.mongodb-api.com/app/YOUR_APP_ID/endpoint/data/v1';
   const MONGODB_API_KEY = 'YOUR_API_KEY';
   ```

### Demo Mode (Works Without MongoDB)
The application automatically falls back to localStorage when MongoDB is not configured, allowing full testing of the UI.

## 📚 Features

- **Interactive Learning**: Mathematics, Physics, Statistics
- **Multi-language**: Arabic and English support
- **QR Code Integration**: Attendance and content access
- **Admin Dashboard**: Student management and analytics
- **Responsive Design**: Mobile-friendly interface
- **Offline Support**: localStorage fallback

## 🛠️ Development

### Prerequisites
- Python 3.x (for built-in server)
- Web browser (Chrome, Firefox, Safari, Edge)

### Development Workflow
1. Run the server: `npm run dev`
2. Open http://localhost:8000 in browser
3. Make changes to HTML/CSS/JS files
4. Refresh browser to see changes

### File Organization Tips
- Keep HTML files in respective directories
- Place shared CSS in `css/` directory
- Place shared JS in `js/` directory
- Use descriptive names for new files

## 🔧 Troubleshooting

### Server Won't Start
```bash
# Check if port 8000 is available
netstat -an | find "8000"

# Use different port
python -m http.server 8080
```

### Files Not Loading
- Ensure you're accessing via `http://localhost:8000` (not file://)
- Check browser console for errors
- Verify file paths are correct

### MongoDB Issues
- Check API credentials in `js/database.js`
- Verify MongoDB Atlas Data API is enabled
- Check browser network tab for API calls

## 📝 Available npm Scripts

```json
{
  "start": "python server.py",
  "dev": "python server.py",
  "serve": "python server.py",
  "run": "python server.py",
  "server": "python server.py",
  "live": "python server.py",
  "open": "python server.py",
  "dev:open": "python server.py",
  "admin": "python server.py admin",
  "student": "python server.py student",
  "qr": "python server.py qr",
  "test-qr": "python server.py test-qr",
  "test-mongo": "python server.py test-mongo"
}
```

## 🎯 Next Steps

1. **Configure MongoDB** for full database functionality
2. **Customize Content** in the grade-specific directories
3. **Add New Features** following the existing structure
4. **Deploy** to web hosting service

## 📞 Support

For issues or questions:
1. Check this README
2. Review MongoDB setup guides in the `md/` folder:
   - `md/MONGODB_SETUP_GUIDE.md` - Comprehensive setup guide
   - `md/MONGODB_SETUP.md` - Basic setup instructions
   - `md/QUICK_SETUP.md` - Quick 2-minute setup
   - `md/SETUP.md` - Alternative setup method
3. Check browser developer tools for errors

---

**Happy Learning! 📚✨**