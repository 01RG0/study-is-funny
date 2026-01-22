# Console Logging Guide - Debug API Issues

I've added detailed console logging to help you diagnose the API path issue. Here's what to look for.

---

## How to Access Console Logs

1. **Open your browser** at: `https://studyisfunny.online/study-is-funny/senior2/mathematics/sessions/`
2. **Press F12** to open Developer Tools
3. **Click "Console"** tab
4. **Look for the debug messages below**

---

## What Console Will Show

### ✅ Expected Output (If Working Correctly)

```
========== SESSION PAGE DEBUG ==========
Page URL: https://studyisfunny.online/study-is-funny/senior2/mathematics/sessions/
✓ API Base URL: https://studyisfunny.online/study-is-funny/api/
✓ Current Location: https://studyisfunny.online/study-is-funny/senior2/mathematics/sessions/
Final API URL: https://studyisfunny.online/study-is-funny/api/sessions.php?action=list&subject=mathematics
Subject: mathematics
Fetching sessions...
Response status: 200
Response headers content-type: application/json
Response data: {success: true, sessions: Array(5), count: 5}
Successfully loaded 5 sessions.
```

---

### ❌ Common Error Scenarios

#### **Error 1: API Base URL is Wrong**
```
API Base URL: https://studyisfunny.online/api/
❌ WRONG! Should be: https://studyisfunny.online/study-is-funny/api/
```
**Fix:** Make sure `js/api-config.js` was uploaded

---

#### **Error 2: Getting HTML Instead of JSON**
```
Response status: 404
Response headers content-type: text/html
❌ ERROR FETCHING SESSIONS: SyntaxError: Unexpected token '<', "<!DOCTYPE "
```
**Fix:** The path is still wrong or API file doesn't exist
**Check:** 
- Is API at `/study-is-funny/api/sessions.php`?
- File permissions correct on Hostinger?

---

#### **Error 3: No Response At All**
```
❌ ERROR FETCHING SESSIONS: TypeError: Failed to fetch
```
**Possible Causes:**
- Network issue
- CORS problem
- Server is down
- API endpoint unreachable

---

## Debug Messages for Sessions Page

| Message | Meaning | Status |
|---------|---------|--------|
| `API Base URL:` | Shows detected API path | ✓ Should include `/study-is-funny/` |
| `Final API URL:` | Complete URL being fetched | ✓ Should be `/study-is-funny/api/sessions.php` |
| `Response status: 200` | Server returned successfully | ✓ Good |
| `Response status: 404` | File not found | ❌ Wrong path |
| `Response status: 500` | Server error | ❌ Check PHP error log |
| `Response data: {success: true...}` | Got valid JSON | ✓ Good |
| `SyntaxError: Unexpected token '<'` | Got HTML, not JSON | ❌ Wrong path or API error |

---

## Debug Messages for Homework Page

Same as Sessions page, just look for:
```
========== HOMEWORK PAGE DEBUG ==========
...
Final API URL: https://studyisfunny.online/study-is-funny/api/homework.php?action=list&status=active
```

---

## Step-by-Step Debugging

### Step 1: Check if api-config.js loaded
**Look for:**
```
✓ API Base URL: https://studyisfunny.online/study-is-funny/api/
✓ Current Location: https://studyisfunny.online/study-is-funny/...
```

**If you DON'T see this:**
- The script didn't load
- Check Network tab → filter by `api-config.js`
- Should show Status 200
- If 404, file wasn't uploaded

---

### Step 2: Check the API URL being called
**Look for:**
```
Final API URL: https://studyisfunny.online/study-is-funny/api/sessions.php?action=list...
```

**If it says `/api/` instead of `/study-is-funny/api/`:**
- `window.API_BASE_URL` is not set correctly
- Go back to Step 1

---

### Step 3: Check the Response
**Look for:**
```
Response status: 200
Response headers content-type: application/json
Response data: {success: true, sessions: [...]
```

**If status is 404:**
- API file doesn't exist at that path on Hostinger
- Or HTML file has wrong relative paths

**If you see HTML error:**
```
SyntaxError: Unexpected token '<', "<!DOCTYPE "
```
- Server is returning an error page instead of JSON
- Check Hostinger error logs

---

## Console Filter Tips

In DevTools Console, you can **filter** to see only relevant messages:

```
Filter for "API Base URL" → shows initialization
Filter for "ERROR" → shows only errors  
Filter for "Response status" → shows HTTP status codes
```

---

## What to Tell Me

Once you've uploaded the files and checked the console, tell me:

1. **What does `API Base URL` show?**
   - `https://studyisfunny.online/study-is-funny/api/` ✓
   - `https://studyisfunny.online/api/` ❌

2. **What's the response status?**
   - `200` ✓
   - `404` ❌
   - `500` ❌

3. **What's the response type?**
   - `application/json` ✓
   - `text/html` ❌

4. **Is there an error? If so, what is it?**
   - Copy the exact error from console

---

## Example Console Log Session

Here's what a **successful session page load** should look like:

```javascript
// From api-config.js
✓ API Base URL: https://studyisfunny.online/study-is-funny/api/
✓ Current Location: https://studyisfunny.online/study-is-funny/senior2/mathematics/sessions/

// From sessions/index.html
========== SESSION PAGE DEBUG ==========
Page URL: https://studyisfunny.online/study-is-funny/senior2/mathematics/sessions/
API_BASE_URL: https://studyisfunny.online/study-is-funny/api/
Final API URL: https://studyisfunny.online/study-is-funny/api/sessions.php?action=list&subject=mathematics
Subject: mathematics
Fetching sessions...
Response status: 200
Response headers content-type: application/json
Response data: {success: true, sessions: Array(3), count: 3}
Successfully loaded 3 sessions.
```

---

## Still Not Working?

1. **Open browser DevTools (F12)**
2. **Go to Console tab**
3. **Share the console output with me**
4. **Also check Network tab:**
   - Look for `api-config.js` - should be Status 200
   - Look for `sessions.php` - what's the status and response?

This will help me identify the exact issue!
