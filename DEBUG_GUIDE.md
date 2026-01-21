# Debug Guide: Sessions Not Appearing in Manage Sessions Page

## Issue
Sessions are uploaded successfully but don't appear on the manage-sessions.html page.

## What Was Changed

### 1. API Enhancements (api/sessions.php)
- `getAllSessions()` now supports `includeInactive=true` parameter
  - By default only returns sessions with `isActive=true`
  - With parameter, returns ALL sessions for testing
- Added server-side error logging (written to logs/error.log)

### 2. Frontend Updates (admin/manage-sessions.html)
- API call now includes `&includeInactive=true` to see all sessions
- Added comprehensive console.log statements at these points:
  - When API fetch starts
  - Response status code
  - Full API result object  
  - Session count
  - Sessions array content
  - When filterAndDisplay() is called
  - Filtered result count and data
  - When displaySessions() renders HTML

## How to Debug

### Step 1: Open Developer Tools
1. Open manage-sessions.html in your browser
2. Press **F12** to open Developer Tools
3. Go to **Console** tab
4. Look for messages starting with: 📡 📥 📦 📊 📋 🔍 👥 ⚙️ 📊 📋 🎬 ⚠️ ✅

### Step 2: Check API Response
Look for these console messages:

```
📡 Fetching sessions from: ../api/sessions.php?action=getAllSessions&includeInactive=true
📥 Response status: 200
📦 Full API Result: { success: true/false, sessions: [...], count: X }
📊 Session Count: X
📋 All Sessions Array: [...]
```

### Step 3: Interpret the Results

#### If you see `success: true` and `count: 0` or empty array
- Sessions are NOT being saved to MongoDB
- Check: 
  - Form submission (check upload-session.php console logs)
  - Video upload process (check Video class)
  - CreateSession validation errors

#### If you see `success: true` and `count: > 0`
- Sessions ARE in the database
- Check: 
  - filterAndDisplay() logs to see if filtering is hiding them
  - displaySessions() logs to see if rendering fails
  - Check browser console for JavaScript errors

#### If you see `success: false`
- API call itself is failing
- Check: 
  - API error message in the response
  - PHP errors in logs/error.log
  - Database connection issues

### Step 4: Check Filter Results
Look for:

```
🔍 filterAndDisplay called
👥 Total allSessions: X
⚙️ Active Filters: { search: '', grade: '', access: '' }
📊 Filtered sessions count: X
📋 Filtered data: [...]
🎬 displaySessions called with: X sessions
```

## Session Data Structure
Sessions should appear like this in console:

```javascript
{
  _id: "507f1f77bcf86cd799439011",
  id: "507f1f77bcf86cd799439011",
  title: "Session Title",
  grade: "senior2",
  subject: "mathematics",
  teacher: "shadyelsharqawy",
  sessionNumber: 13,
  accessControl: "restricted",
  videos: [...],
  isActive: true,
  createdAt: "2026-01-21T..."
}
```

## Common Issues & Solutions

### Issue: No sessions appear even though count is 0
- Upload didn't succeed - check upload form console logs
- Video upload failed - check logs/error.log
- createSession validation failed - check API response for validation errors

### Issue: Sessions appear in API response but not on page
- Filter is hiding them - check filter values in console
- JavaScript rendering error - check browser console for errors
- displaySessions() not called - check if filterAndDisplay() was reached

### Issue: Sessions appear briefly then disappear
- Check for JavaScript errors in console
- Verify filter values aren't being reset unexpectedly
- Check if page is refreshing automatically

## Files Modified
1. `api/sessions.php` - Added logging and includeInactive parameter
2. `admin/manage-sessions.html` - Updated API call and added console logging

## Next Steps After Debugging
Once sessions appear correctly:
1. Test access control with student accounts
2. Verify video playback on session-detail.php
3. Test subscription model (session_N.online_session check)
