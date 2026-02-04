📋 **Testing Instructions:**

1. **Stop and restart your local PHP server** to load the new code:
   - Press Ctrl+C in the terminal running the server
   - Run: `php -S localhost:8000 router.php`

2. **Open the test page:**
   - Go to: `http://localhost:8000/test-lama-production.html`
   - Click "Test Direct API Call"

3. **Check the server logs** in your terminal. You should see:
   ```
   === checkStudentSessionAccess ===
   Phone: 01274856549
   Session Number: 1
   ✓ Student found in collection: [collection_name]
   Current online_attendance: false
   🔄 Updating online_attendance to true for session 1
   ✅ Updated 1 record(s) in [collection_name]
   ✅ Updated 1 record(s) in all_students_view
   ```

4. **Check the database** to verify the update:
   ```javascript
   db.all_students_view.findOne(
     { phone: "+201274856549" },
     { "session_1.online_attendance": 1, "session_1.online_attendance_completed_at": 1 }
   )
   ```

5. **Upload to production** if local test succeeds:
   - Upload `api/sessions.php` to Hostinger
   - Test on production using the same test page

**Expected Result:**
`online_attendance` should change from `false` to `true` ✅
