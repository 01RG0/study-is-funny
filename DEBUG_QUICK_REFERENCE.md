# Quick Console Debug Checklist

## Before You Test on Hostinger:

✅ Upload these updated files:
```
js/api-config.js (NEW - MOST IMPORTANT!)
senior2/mathematics/sessions/index.html
senior2/mathematics/Homework/index.html
(and all other session/homework pages)
```

---

## Test Steps:

1. **Go to:** `https://studyisfunny.online/study-is-funny/senior2/mathematics/sessions/`
2. **Press:** F12 (Open Console)
3. **Look for this exact message:**
   ```
   ✓ API Base URL: https://studyisfunny.online/study-is-funny/api/
   ```

### ✅ If You See It:
- The fix is working!
- Sessions should load
- Share the rest of the console output

### ❌ If You DON'T See It:
- `api-config.js` was not uploaded
- Or incorrect relative path in HTML
- Check Network tab for 404 errors

---

## Copy-Paste This Into Console to Test:

```javascript
// Check if API_BASE_URL exists
console.log('API_BASE_URL:', window.API_BASE_URL);

// Test the API path manually
fetch(window.API_BASE_URL + 'sessions.php?action=list')
  .then(r => r.json())
  .then(d => console.log('API Response:', d))
  .catch(e => console.error('API Error:', e));
```

---

## Expected Console Output Structure:

```
[api-config.js]
✓ API Base URL: ...
✓ Current Location: ...

[sessions/index.html]
========== SESSION PAGE DEBUG ==========
Page URL: ...
API_BASE_URL: ...
Final API URL: ...
Subject: ...
Fetching sessions...
Response status: ...
Response headers content-type: ...
Response data: ...
```

---

## Common Issues & Fixes:

| Issue | Console Shows | Fix |
|-------|---------------|-----|
| api-config.js not uploaded | No API Base URL message | Upload `js/api-config.js` |
| Wrong path in HTML | API_BASE_URL shows `/api/` not `/study-is-funny/api/` | Check relative paths: `../../../js/api-config.js` |
| API endpoint 404 | Response status: 404 | API file missing or wrong path |
| API error | Response status: 500 | Check Hostinger PHP error logs |
| JSON parse error | `SyntaxError: Unexpected token '<'` | Server returning HTML, not JSON |

---

## Network Tab Check:

1. **Open DevTools**
2. **Go to Network tab**
3. **Reload page (Ctrl+R)**
4. **Look for:**
   - `api-config.js` → Status 200 ✓
   - `sessions.php` → Status 200, Type: fetch ✓
   - `sessions.php` response → Should be JSON, not HTML

---

## What to Share With Me:

Copy from console and tell me:

```
1. API Base URL: [what does it show?]
2. Response status: [what number?]
3. Any errors?: [copy exact error]
4. Sessions loaded?: [yes/no]
```

This will help me identify the exact issue instantly!
