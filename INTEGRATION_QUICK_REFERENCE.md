# 🚀 Backend Integration - Quick Reference

## ✅ What Was Done

### Problem:
- Backend (API + Classes) existed but wasn't connected to admin pages
- Forms didn't know which endpoint to call
- API couldn't handle file uploads properly

### Solution:
- ✅ Fixed `api/sessions.php` to handle multipart/form-data
- ✅ Connected `upload-session.php` form to the API
- ✅ Integrated Video and SessionManager classes
- ✅ Updated access control system

---

## 📋 File Changes Summary

| File | What Changed |
|------|-------------|
| `api/sessions.php` | • Fixed `handlePost()` to detect file uploads<br>• Updated `uploadSession()` to match form format<br>• Added class imports (DatabaseMongo, Video, SessionManager) |
| `admin/upload-session.php` | • Form action → `../api/sessions.php?action=upload`<br>• Added APP_BASE_URL config<br>• Added success/error handling<br>• Auto-redirects after upload |
| `admin/upload-session.html` | • Renamed to `.php`<br>• Removed "Session Number" field<br>• Updated access control to 2 options |
| All admin pages | • Updated nav links to `upload-session.php` |
| `upload-session-handler.php` | • Deleted (no longer needed, using API directly) |

---

## 🎯 How It Works Now

### Upload Flow:
```
Form Submit → api/sessions.php?action=upload → uploadSession() → Video->upload() → createSession() → MongoDB
```

### The Chain:
1. **User fills form** in `admin/upload-session.php`
2. **Form posts** to `api/sessions.php?action=upload` (with files)
3. **API receives** multipart/form-data
4. **handlePost()** detects it's an upload, calls `uploadSession()`
5. **uploadSession()** processes files using `Video` class
6. **Videos saved** to `uploads/videos/` folder
7. **Session created** in MongoDB with video references
8. **Success** → redirect to manage-sessions

---

## 🔌 API Quick Reference

### Upload Session (with files):
```javascript
// HTML Form Method:
<form action="../api/sessions.php?action=upload" method="POST" enctype="multipart/form-data">
  <input name="sessionTitle" required>
  <input name="subject" required>
  <input name="grade" required>
  <input name="teacher" required>
  <input name="accessType" value="online_session">
  <input type="file" name="videoFile[]" multiple>
  <button type="submit">Upload</button>
</form>

// JavaScript Method:
const formData = new FormData(form);
const response = await fetch('../api/sessions.php?action=upload', {
  method: 'POST',
  body: formData
});
const result = await response.json();
```

### Create Session (JSON only, no files):
```javascript
const sessionData = {
  title: "Introduction to Physics",
  subject: "physics",
  grade: "senior1",
  teacher: "shadyelsharqawy",
  accessType: "online_session",
  videos: []  // Pre-uploaded video IDs or links
};

const response = await fetch('../api/sessions.php?action=create', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(sessionData)
});
```

### Get All Sessions:
```javascript
const filters = {
  subject: 'physics',
  grade: 'senior1',
  status: 'published'
};
const queryParams = new URLSearchParams(filters);
const response = await fetch(`../api/sessions.php?action=all&${queryParams}`);
const result = await response.json();
```

---

## 📊 Database Structure

### Sessions Collection:
```json
{
  "_id": ObjectId("..."),
  "title": "Introduction to Mechanics",
  "subject": "physics",
  "grade": "senior1",
  "teacher": "shadyelsharqawy",
  "description": "...",
  "accessType": "online_session",
  "year": 1,
  "videos": [
    {
      "video_id": "67890...",
      "title": "Part 1 - Theory",
      "type": "lecture",
      "source": "upload"
    },
    {
      "video_id": null,
      "title": "Part 2 - Examples",
      "type": "questions",
      "source": "link",
      "url": "https://youtube.com/watch?v=..."
    }
  ],
  "status": "published",
  "createdAt": UTCDateTime("..."),
  "createdBy": "admin"
}
```

### Videos Collection:
```json
{
  "_id": ObjectId("67890..."),
  "video_title": "Part 1 - Theory",
  "video_file_path": "subject_physics/lesson_1/video_12345.mp4",
  "file_size_mb": 45.2,
  "uploaded_by": ObjectId("..."),
  "status": "completed",
  "view_count": 0,
  "createdAt": UTCDateTime("...")
}
```

---

## 🔐 Access Control

### New System (2 options):

1. **Specific Session** (`accessType: "online_session"`)
   - Only for students where `online_session = true`
   - Year automatically determined from grade:
     - `senior1` → `year: 1`
     - `senior2` → `year: 2`
     - `senior3` → `year: 3`

2. **Free for All** (`accessType: "free_for_all"`)
   - Available to all students
   - No restrictions

### Old System (Removed):
- ❌ "Session Number" field
- ❌ Multiple student type checkboxes
- ❌ session_X field mapping

---

## 🛠️ Classes Used

### DatabaseMongo (`classes/DatabaseMongo.php`)
```php
$db = new DatabaseMongo();
$db->insert('sessions', $data);
$db->find('sessions', $filter);
$db->update('sessions', $filter, $data);
```

### Video (`classes/Video.php`)
```php
$video = new Video($db);
$result = $video->upload($_FILES['video'], $metadata);
// Returns: ['success' => true, 'video_id' => '...', 'message' => '...']
```

### SessionManager (`classes/SessionManager.php`)
```php
$sessionMgr = new SessionManager($db);
$sessionMgr->create($sessionData);
$sessionMgr->getAll($filters);
$sessionMgr->update($id, $data);
```

---

## ✨ Admin.js Functions

Located in `admin/js/admin.js`:

```javascript
// Session Operations
await createSession(sessionData)
await getAllSessions(filters)
await updateSession(sessionId, updates)
await deleteSession(sessionId)
await publishSession(sessionId)
await unpublishSession(sessionId)
await getSessionStats()

// Utility
showMessage(message, type)  // type: 'success' or 'error'
```

---

## 🎨 Frontend Pages

### Admin Panel:
- `dashboard.html` - Overview with stats
- `upload-session.php` - **NEW! Connected to API**
- `upload-video.php` - Simple video upload (separate tool)
- `manage-homework.php` - Homework management
- `manage-sessions.html` - Sessions table & filters
- `manage-students.html` - Student management
- `analytics.html` - Charts & analytics
- `settings.html` - System settings

---

## 🚨 Important Notes

### File Uploads:
- **Max file size**: 500MB per video (configurable in php.ini)
- **Allowed formats**: MP4, WebM, AVI, MOV
- **Upload directory**: `uploads/videos/`
- **Permissions**: 755 for folders, 644 for files

### Security:
- All forms have CSRF protection
- Teacher authentication required
- Input sanitization enabled
- File validation active

### Configuration:
- **MongoDB**: `api/config.php`
- **Session timeout**: `includes/session_check.php`
- **Upload limits**: `php.ini` (`upload_max_filesize`, `post_max_size`)

---

## 📞 Testing the Integration

### 1. Test Upload:
```bash
# Navigate to admin panel
http://localhost/admin/upload-session.php

# Fill form:
- Title: Test Session
- Subject: Physics
- Grade: Senior 1
- Teacher: ENG. Shady Elsharqawy
- Access: Specific Session
- Upload 1 video file

# Submit → Should redirect to manage-sessions
# Check MongoDB: sessions collection should have new entry
# Check folder: uploads/videos/ should have video file
```

### 2. Test API Directly:
```bash
# Get all sessions:
curl http://localhost/api/sessions.php?action=all

# Get session stats:
curl http://localhost/api/sessions.php?action=stats
```

### 3. Check Logs:
- PHP errors: Check Apache/Nginx error log
- MongoDB: Check connection in api/config.php
- Browser: Check console for JavaScript errors

---

## 🎉 Success Indicators

✅ Form submits without errors
✅ "Session created successfully!" message appears
✅ Redirects to manage-sessions page
✅ New session appears in MongoDB
✅ Video file exists in uploads/videos/
✅ Session shows in manage-sessions table

---

## 💡 Quick Tips

1. **Enable error display during development:**
   ```php
   // Add to top of api/sessions.php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. **Check upload limits:**
   ```php
   echo ini_get('upload_max_filesize');  // Should be >= 500M
   echo ini_get('post_max_size');        // Should be >= 500M
   ```

3. **Test MongoDB connection:**
   ```bash
   http://localhost/tests/test_mongo_connection.php
   ```

4. **Clear cache if changes don't show:**
   - Ctrl + Shift + Delete (browser)
   - Or use Incognito mode

---

## 📚 Related Files

- Full documentation: `admin/BACKEND_INTEGRATION.md`
- Test files: `tests/` folder
- Database schema: `plan/DATABASE_SCHEMA.md`
- API docs: `plan/implementation_reference.md`

---

**Everything is connected and ready to use! 🚀**
