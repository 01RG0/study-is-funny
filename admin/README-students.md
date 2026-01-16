# Admin Students View - Study is Funny

## Overview
The Admin Students View page allows administrators to:
- View all students from MongoDB database
- Search and filter students by name, phone, grade, and status
- View detailed student information and statistics
- Generate and display QR codes for each student's subjects
- Download individual QR codes or all QR codes for a student
- Manage student accounts (activate/deactivate, reset progress)

## Features

### 🔍 Student Search & Filtering
- **Search by name or phone number**
- **Filter by grade** (Senior 1, Senior 2)
- **Filter by status** (Active, Inactive)
- **Real-time filtering** as you type

### 👤 Student Details
- **Personal Information**: Name, phone, grade, join date, last login
- **Activity Statistics**: Sessions viewed, total watch time, downloads
- **Subject Access**: List of subjects the student can access
- **Account Status**: Active/Inactive status with toggle

### 📱 QR Code Generation
- **Automatic QR Generation**: Creates QR codes for each subject
- **Encrypted Access**: Each QR contains encrypted student and subject data
- **Time-limited Access**: QR codes expire after 24 hours
- **Download Options**: Individual or bulk download of QR codes

### ⚙️ Student Management
- **Account Activation/Deactivation**: Enable or disable student access
- **Progress Reset**: Clear all student progress and activity logs
- **Bulk Actions**: Perform actions on multiple students

## Database Integration

### MongoDB Collection: `users`
```json
{
  "_id": "ObjectId",
  "name": "Student Name",
  "phone": "+201234567890",
  "password": "hashed_password",
  "grade": "senior1|senior2",
  "joinDate": "2024-01-15T10:00:00Z",
  "lastLogin": "2024-01-16T14:30:00Z",
  "isActive": true,
  "subjects": ["physics", "mathematics", "statistics"],
  "totalSessionsViewed": 25,
  "totalWatchTime": 1800, // minutes
  "activityLog": [
    {
      "type": "watch",
      "subject": "physics",
      "sessionId": "session_123",
      "watchTime": 45,
      "timestamp": "2024-01-16T14:30:00Z"
    }
  ]
}
```

## QR Code Structure
Each QR code contains encrypted JSON data:
```json
{
  "studentPhone": "+201234567890",
  "subject": "physics",
  "grade": "senior1",
  "timestamp": 1705410000000,
  "accessToken": "encrypted_token"
}
```

## API Endpoints Used

### Fetch All Students
```javascript
POST /action/find
{
  "dataSource": "Cluster0",
  "database": "attendance_system",
  "collection": "users",
  "filter": {},
  "sort": { "createdAt": -1 }
}
```

### Update Student Status
```javascript
POST /action/updateOne
{
  "dataSource": "Cluster0",
  "database": "attendance_system",
  "collection": "users",
  "filter": { "_id": { "$oid": "student_id" } },
  "update": { "$set": { "isActive": false } }
}
```

### Reset Student Progress
```javascript
POST /action/updateOne
{
  "dataSource": "Cluster0",
  "database": "attendance_system",
  "collection": "users",
  "filter": { "_id": { "$oid": "student_id" } },
  "update": {
    "$set": {
      "totalSessionsViewed": 0,
      "totalWatchTime": 0,
      "activityLog": []
    }
  }
}
```

## Security Features

### Access Token Generation
- **Base64 Encoding**: Simple encoding for demo purposes
- **Timestamp Validation**: 24-hour expiration
- **Student Verification**: Phone number validation
- **Subject Authorization**: Check if student has access to subject

### Database Security
- **No Direct Queries**: All operations go through API
- **Input Validation**: Sanitize all user inputs
- **Error Handling**: Graceful error handling without exposing sensitive data

## Usage Instructions

### 1. Access Admin Panel
Navigate to `admin/students-view.html` in your browser

### 2. View Students
- All students are loaded automatically
- Use search box to find specific students
- Apply filters for grade and status

### 3. View Student Details
- Click on any student card to view detailed information
- See activity statistics and subject access
- Use action buttons to manage the student

### 4. Generate QR Codes
- Click "رموز QR" button on any student card
- View QR codes for all subjects the student can access
- Download individual or all QR codes

### 5. Manage Students
- Toggle active/inactive status
- Reset student progress if needed
- Monitor student activity and engagement

## Troubleshooting

### No Students Showing
- Check MongoDB connection
- Verify API key is correct
- Ensure Data API is enabled
- Check collection name and permissions

### QR Codes Not Generating
- Verify QRCode.js library is loaded
- Check browser console for JavaScript errors
- Ensure student has subjects assigned

### Database Update Errors
- Check API key permissions
- Verify collection exists
- Ensure correct data types in updates

## Future Enhancements

- **Bulk QR Code Generation**: Generate QR codes for multiple students
- **QR Code Analytics**: Track which QR codes are scanned
- **Student Communication**: Send messages to students directly
- **Advanced Filtering**: Filter by activity levels, join dates, etc.
- **Export Features**: Export student lists and QR codes as PDF/Excel

## Dependencies

- **QRCode.js**: For QR code generation
- **Font Awesome**: For icons
- **MongoDB Atlas Data API**: For database operations
- **Custom CSS**: For styling and responsive design