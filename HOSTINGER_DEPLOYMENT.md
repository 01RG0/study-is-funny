# 🚀 Hostinger Deployment Guide - Study is Funny

## Overview
This guide will help you deploy the Study is Funny project on a **Hostinger Mini Plan**.

---

## ✅ Pre-Deployment Checklist

### System Requirements (Hostinger Mini Plan Includes)
- ✅ PHP 8.0+ (usually 8.1+ on Hostinger)
- ✅ MySQL/MariaDB Database
- ✅ File Manager / FTP Access
- ✅ .htaccess support
- ✅ 50+ GB storage (usually)

### Current Project Status
- ✅ HTML/CSS/JavaScript (Frontend) - Ready
- ✅ PHP Backend - Ready
- ✅ MongoDB support configured (if using cloud MongoDB)
- ⚠️ Python server NOT needed on Hostinger
- ✅ No npm dependencies
- ✅ No Python dependencies (for production)

---

## 📋 Step-by-Step Deployment

### 1. Upload Files to Hostinger

**Via File Manager:**
1. Login to Hostinger control panel
2. Go to **File Manager**
3. Navigate to **public_html** folder
4. Upload all project files EXCEPT:
   - `run.ps1` (local development only)
   - `.git/` folder (optional)
   - `/plan/` folder (documentation only)
   - `debug_sessions*.php` files
   - `dev.config.json`

**Project structure on server:**
```
public_html/
├── admin/
├── api/
├── classes/
├── config/
├── css/
├── includes/
├── js/
├── login/
├── grade/
├── senior1/
├── senior2/
├── senior3/
├── student/
├── uploads/ (create this folder)
├── index.html
├── .htaccess
└── [other HTML files]
```

### 2. Create Required Folders

In File Manager, create these folders (give them 755 permissions):
```
uploads/
uploads/sessions/
uploads/videos/
uploads/homework/
uploads/resources/
uploads/thumbnails/
logs/
```

### 3. Configure Database Connection

**If using Hostinger MySQL:**

Edit `/config/config.php`:
```php
// Replace MongoDB with MySQL:
define('DB_HOST', 'your_hostinger_db_host');
define('DB_USER', 'your_db_username');
define('DB_PASSWORD', 'your_db_password');
define('DB_NAME', 'your_db_name');
```

**If using MongoDB Atlas (Cloud):**
- Update MONGO_URI in `/config/config.php`
- MongoDB Atlas free tier should work fine
- Current URI is already set: `mongodb+srv://root:root@cluster0.vkskqhg.mongodb.net/...`

### 4. Update Configuration Files

**Edit `/config/config.php`:**
```php
// Change localhost to your domain
define('APP_URL', 'https://yourdomain.com');

// Set for production
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
```

### 5. Set Folder Permissions

Via File Manager (right-click → Permissions):
- `uploads/` → **755**
- `logs/` → **755**
- `config/` → **755**
- `includes/` → **755**

### 6. Rename .htaccess File

The file is currently named `hide.htaccess`. 

**Rename to `.htaccess`:**
1. In File Manager, right-click `hide.htaccess`
2. Select "Rename"
3. Change to `.htaccess`
4. Make sure mod_rewrite is enabled (usually is on Hostinger)

**Alternatively, create new `.htaccess`:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.html -f
RewriteRule ^(.*)$ $1.html [L]
```

### 7. Set File Permissions

**Key files should have these permissions:**
- PHP files → **644**
- HTML files → **644**
- Folders → **755**
- `config/config.php` → **644** (readable by server)

### 8. Create .gitignore (if using Git)

For security, ensure these are NOT uploaded:
```
config/config.php
.env
database.yml
node_modules/
logs/
uploads/*
!uploads/.gitkeep
```

---

## 🔒 Security Checklist

### Essential Security Steps

1. **Change Database Credentials**
   - Update `config/config.php` with strong passwords
   - Don't use default credentials like `root:root`

2. **Protect Sensitive Files**
   - Add to `.htaccess`:
   ```apache
   <FilesMatch "^config\.php$|^\.env$|^database\.yml$">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

3. **Enable HTTPS**
   - Hostinger provides free SSL certificate
   - Enable it in control panel
   - Update `APP_URL` to use `https://`

4. **Set Correct Headers**
   - Add to `.htaccess`:
   ```apache
   <IfModule mod_headers.c>
       Header set X-Content-Type-Options "nosniff"
       Header set X-Frame-Options "SAMEORIGIN"
       Header set X-XSS-Protection "1; mode=block"
   </IfModule>
   ```

5. **Create Unique Session Keys**
   - Keep the current session handling in `config/config.php`
   - Make sure `session.cookie_secure` is set for HTTPS

---

## 🧪 Testing Your Deployment

### 1. Test Main Pages
```
https://yourdomain.com
https://yourdomain.com/login
https://yourdomain.com/grade
https://yourdomain.com/admin
```

### 2. Test API Endpoints
```
https://yourdomain.com/api/students.php
https://yourdomain.com/api/sessions.php
https://yourdomain.com/api/videos.php
```

### 3. Test File Uploads
- Try uploading a video to `/uploads/sessions/`
- Check if files are accessible

### 4. Check PHP Version
Create a `phpinfo.php` file:
```php
<?php phpinfo(); ?>
```
Visit `https://yourdomain.com/phpinfo.php` (delete after checking)

---

## 🐛 Troubleshooting

### Problem: 404 Errors on All Pages
**Solution:** 
- Rename `hide.htaccess` to `.htaccess`
- Make sure `AllowOverride` is enabled on Hostinger
- Contact Hostinger support if still not working

### Problem: "Permission Denied" on Upload
**Solution:**
- Change `/uploads/` folder permission to **755**
- Make sure folder is writable

### Problem: Database Connection Error
**Solution:**
- Verify credentials in `config/config.php`
- Check if MongoDB Atlas is accessible from Hostinger IP
- Use Hostinger's MySQL if available

### Problem: Blank Pages
**Solution:**
- Check `/logs/error.log` for errors
- Enable temporary display_errors in `config/config.php`
- Check if PHP extensions are installed (mysqli for MySQL)

---

## 📊 Performance Tips for Hostinger Mini

1. **Enable Caching**
   ```apache
   <IfModule mod_expires.c>
       ExpiresActive On
       ExpiresByType image/jpeg "access 1 year"
       ExpiresByType image/gif "access 1 year"
       ExpiresByType image/png "access 1 year"
       ExpiresByType text/css "access 1 month"
       ExpiresByType text/javascript "access 1 month"
   </IfModule>
   ```

2. **Compress Static Files**
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/css application/javascript
   </IfModule>
   ```

3. **Limit Upload Size**
   Update `config/config.php`:
   ```php
   define('MAX_VIDEO_SIZE', 104857600); // 100MB instead of 500MB
   ```

---

## 🆘 Support Resources

- **Hostinger Control Panel:** https://hpanel.hostinger.com
- **Hostinger Knowledge Base:** https://support.hostinger.com
- **PHP Documentation:** https://www.php.net/docs.php
- **MongoDB Atlas Docs:** https://docs.atlas.mongodb.com

---

## ✨ Final Checklist

- ✅ All files uploaded to public_html
- ✅ Folders created with correct permissions
- ✅ Database configured and working
- ✅ .htaccess renamed from hide.htaccess
- ✅ config/config.php updated with domain
- ✅ SSL/HTTPS enabled
- ✅ Database credentials changed from defaults
- ✅ Test all pages and API endpoints
- ✅ Remove debug files (phpinfo.php, debug_sessions.php)

---

## 🎉 You're Ready!

Your Study is Funny project is now live on Hostinger!

**Domain:** https://yourdomain.com

