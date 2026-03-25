<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Session - Study is Funny</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" class="circular-icon" type="image/png" href="../images/logo.png">
    <script>
        // API Configuration for Hostinger subdirectory support
        function getApiBaseUrl() {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;
            
            if (pathname.includes('study-is-funny')) {
                const basePathMatch = pathname.match(/^(.+?\/study-is-funny)\//);
                const basePath = basePathMatch ? basePathMatch[1] : '/study-is-funny';
                return `${protocol}//${host}${basePath}/api/`;
            }
            
            return `${protocol}//${host}/api/`;
        }
        
        window.API_BASE_URL = getApiBaseUrl();
        console.log('✓ Upload API Base URL:', window.API_BASE_URL);
        console.log('✓ Upload Session Location:', window.location.href);
    </script>
</head>

<body>
    <?php
    session_start();
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
    ?>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/logo.webp" alt="Logo" class="sidebar-logo">
            <h2>Admin Panel</h2>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.html" class="nav-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="upload-session.php" class="nav-item active">
                <i class="fas fa-upload"></i> Upload Session
            </a>
            <a href="manage-sessions.html" class="nav-item">
                <i class="fas fa-list"></i> Manage Sessions
            </a>
            <a href="manage-students.html" class="nav-item">
                <i class="fas fa-user-cog"></i> Manage Students
            </a>
            <a href="analytics.html" class="nav-item">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
            <a href="settings.html" class="nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Upload Session Section -->
        <div class="content-section active">
            <div class="section-header">
                <h1><i class="fas fa-upload"></i> Upload New Session</h1>
            </div>

            <form id="sessionUploadForm" class="upload-form" method="POST" action="../api/sessions.php?action=upload" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                
                <!-- Basic Information -->
                <div class="form-section">
                    <h3>Basic Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="sessionTitle">Session Title *</label>
                            <input type="text" id="sessionTitle" name="sessionTitle" required
                                placeholder="e.g., Introduction to Mechanics">
                        </div>

                        <div class="form-group">
                            <label for="gradeSubject">Grade & Subject *</label>
                            <select id="gradeSubject" name="gradeSubject" required>
                                <option value="">Select Grade & Subject</option>
                                <optgroup label="Senior 1">
                                    <option value="senior1-mathematics">Senior 1 - Mathematics</option>
                                </optgroup>
                                <optgroup label="Senior 2">
                                    <option value="senior2-physics">Senior 2 - Physics</option>
                                    <option value="senior2-mathematics">Senior 2 - Mathematics</option>
                                    <option value="senior2-mechanics">Senior 2 - Mechanics</option>
                                </optgroup>
                                <optgroup label="Senior 3">
                                    <option value="senior3-physics">Senior 3 - Physics</option>
                                    <option value="senior3-mathematics">Senior 3 - Mathematics</option>
                                    <option value="senior3-statistics">Senior 3 - Statistics</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- Teacher auto-set to only available teacher -->
                    <input type="hidden" id="teacher" name="teacher" value="shadyelsharqawy">

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                            placeholder="Brief description of the session content..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="type">Session Type *</label>
                        <select id="type" name="type" required onchange="toggleSessionType(this.value)">
                            <option value="normal" selected>Normal Session (Subscription Based)</option>
                            <option value="revision">Revision Video (Google Sheet Based)</option>
                        </select>
                    </div>
                </div>

                <!-- Lecture or Homework Selection -->
                <div class="form-section">
                    <h3>Content Type</h3>
                    <div class="form-group">
                        <label>Is this a Lecture or Homework? *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="contentType" value="lecture" checked> 
                                📚 Lecture (Study Session)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="contentType" value="homework"> 
                                📝 Homework (Assignment)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Access Control -->
                <div class="form-section" id="accessControlSection">
                    <h3>Access Control</h3>

                    <div class="form-group" id="sessionNumberGroup">
                        <label for="sessionNumber">Session Number *</label>
                        <input type="number" id="sessionNumber" name="sessionNumber" required min="1" max="999"
                            placeholder="e.g., 1">
                        <small class="file-info" id="sessionNumberHelp">This number is used for subscription restrictions (e.g., online_session = true for session_13)</small>
                    </div>

                    <div id="googleSheetGroup" style="display: none;">
                        <div class="form-group">
                            <label for="googleSheetLink">Google Sheet CSV Link *</label>
                            <input type="url" id="googleSheetLink" name="googleSheetLink" 
                                placeholder="e.g., https://docs.google.com/spreadsheets/d/.../export?format=csv">
                            <small class="file-info">Make sure the sheet is public or 'Anyone with link can view' and use the <b>CSV export URL</b>.</small>
                            <div style="margin-top: 5px;">
                                <small style="color: #666;">How to get the CSV link: File > Share > Publish to web > Select Sheet and 'Comma-separated values (.csv)' > Copy link.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Access Control *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="accessControl" value="free" checked> 
                                Free for All (Available to everyone)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="accessControl" value="restricted"> 
                                Restricted (Only students with session_{number} = online_session: true)
                            </label>
                        </div>
                        <small class="file-info" id="accessControlHelp">For restricted: Use the Session Number above (e.g., if Session Number = 13, only students with session_13.online_session = true can access)</small>
                    </div>

                    <div class="form-group">
                        <label for="maxViews">Maximum Views per Student</label>
                        <input type="number" id="maxViews" name="maxViews" min="1" placeholder="Unlimited">
                        <small class="file-info">Leave empty for unlimited views</small>
                    </div>
                </div>

                <!-- Video Upload Section -->
                <div class="form-section">
                    <h3>Video Content</h3>
                    <div id="videosContainer">
                        <div class="video-upload-item">
                            <h4>Video 1</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Video Title *</label>
                                    <input type="text" name="videoTitle[]" required
                                        placeholder="e.g., Part 1 - Introduction">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Video Source *</label>
                                    <div class="radio-group">
                                        <label class="radio-label">
                                            <input type="radio" name="videoSource[0]" value="upload"
                                                onchange="toggleVideoInput(this)"> Upload File
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="videoSource[0]" value="link" checked
                                                onchange="toggleVideoInput(this)"> Video Link
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group video-upload-group" style="display: none;">
                                    <label>Video File *</label>
                                    <input type="file" name="videoFile[]" accept="video/*" required disabled>
                                    <small class="file-info">Supported formats: MP4, AVI, MOV (No app-level limit, server/PHP size limits still apply)</small>
                                </div>

                                <div class="form-group video-link-group" style="display: block;">
                                    <label>Video URL *</label>
                                    <input type="url" name="videoLink[]" required
                                        placeholder="e.g., https://youtube.com/watch?v=... or direct video URL">
                                    <small class="file-info">YouTube, Vimeo, or direct video links supported</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Video Description</label>
                                <textarea name="videoDescription[]" rows="2"
                                    placeholder="Brief description of this video..."></textarea>
                            </div>

                            <button type="button" class="remove-video-btn" style="display: none;">
                                <i class="fas fa-trash"></i> Remove Video
                            </button>
                        </div>
                    </div>

                    <button type="button" id="addVideoBtn" class="add-video-btn">
                        <i class="fas fa-plus"></i> Add Another Video
                    </button>
                </div>

                <!-- Materials Section -->
                <div class="form-section">
                    <h3>Materials & Files</h3>
                    <div class="form-group">
                        <label for="pdfFile">Upload PDFs / Materials</label>
                        <input type="file" id="pdfFile" name="pdfFile[]" accept="application/pdf" multiple>
                        <small class="file-info">You can select multiple PDF files. These will be available for students to download.</small>
                    </div>
                </div>

                <!-- Schedule & Settings -->
                <div class="form-section">
                    <h3>Schedule & Settings</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="publishDate">Publish Date</label>
                            <input type="datetime-local" id="publishDate" name="publishDate">
                        </div>

                        <div class="form-group">
                            <label for="expiryDate">Expiry Date (Optional)</label>
                            <input type="datetime-local" id="expiryDate" name="expiryDate">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="isPublished">Publish Status</label>
                            <select id="isPublished" name="isPublished">
                                <option value="draft">Save as Draft</option>
                                <option value="published" selected>Publish Now</option>
                                <option value="scheduled">Schedule for Later</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="resetForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-upload"></i> Upload Session
                    </button>
                </div>

                <!-- Upload Progress -->
                <div id="uploadProgressContainer" style="display:none; margin-top: 1rem;">
                    <div style="width:100%; background:#e0e0e0; border-radius: 8px; overflow:hidden; height:24px;">
                        <div id="uploadProgressBar" style="width:0%; height:100%; background: #28a745; color:#fff; text-align:center; line-height:24px; font-weight:600;">0%</div>
                    </div>
                    <small id="uploadProgressText" style="display:block; margin-top:4px; color:#333;">0 MB / 0 MB (0%)</small>
                </div>
            </form>
        </div>
    </div>

    <script>
        // API Configuration for Hostinger subdirectory support
        function getApiBaseUrl() {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const pathname = window.location.pathname;
            
            if (pathname.includes('study-is-funny')) {
                const basePathMatch = pathname.match(/^(.+?\/study-is-funny)\//);
                const basePath = basePathMatch ? basePathMatch[1] : '/study-is-funny';
                return `${protocol}//${host}${basePath}/api/`;
            }
            
            return `${protocol}//${host}/api/`;
        }
        
        window.API_BASE_URL = getApiBaseUrl();
        console.log('✓ Upload Session API Base URL:', window.API_BASE_URL);
        console.log('✓ Upload Session Location:', window.location.href);
    </script>
    <script src="js/admin.js"></script>
    <script>
        // Update help text based on session number input
        document.getElementById('sessionNumber').addEventListener('input', function() {
            const sessionNum = this.value || '13';
            const sessionNumberHelp = document.getElementById('sessionNumberHelp');
            const accessControlHelp = document.getElementById('accessControlHelp');
            
            if (sessionNumberHelp) {
                sessionNumberHelp.textContent = `This number is used for subscription restrictions (e.g., online_session = true for session_${sessionNum})`;
            }
            if (accessControlHelp) {
                accessControlHelp.textContent = `For restricted: Students with session_${sessionNum}.online_session = true can access this content`;
            }
        });

        // Toggle Session Type
        function toggleSessionType(type) {
            const sessionNumberGroup = document.getElementById('sessionNumberGroup');
            const googleSheetGroup = document.getElementById('googleSheetGroup');
            const sessionNumberInput = document.getElementById('sessionNumber');
            const googleSheetInput = document.getElementById('googleSheetLink');
            const accessControlRadios = document.querySelectorAll('input[name="accessControl"]');
            
            if (type === 'revision') {
                sessionNumberGroup.style.display = 'none';
                googleSheetGroup.style.display = 'block';
                sessionNumberInput.required = false;
                googleSheetInput.required = true;
                
                // For revision, force restricted access (handled by sheet)
                document.querySelector('input[name="accessControl"][value="restricted"]').checked = true;
                document.getElementById('accessControlHelp').parentElement.style.display = 'none';
            } else {
                sessionNumberGroup.style.display = 'block';
                googleSheetGroup.style.display = 'none';
                sessionNumberInput.required = true;
                googleSheetInput.required = false;
                document.getElementById('accessControlHelp').parentElement.style.display = 'block';
            }
        }

        // Toggle between video upload and link input
        function toggleVideoInput(radio) {
            const container = radio.closest('.video-upload-item');
            const uploadGroup = container.querySelector('.video-upload-group');
            const linkGroup = container.querySelector('.video-link-group');
            const uploadInput = uploadGroup.querySelector('input[name="videoFile[]"]');
            const linkInput = linkGroup.querySelector('input[name="videoLink[]"]');

            if (radio.value === 'upload') {
                uploadGroup.style.display = 'block';
                linkGroup.style.display = 'none';
                uploadInput.required = true;
                uploadInput.disabled = false;
                linkInput.required = false;
                linkInput.disabled = true;
                linkInput.value = '';
            } else {
                uploadGroup.style.display = 'none';
                linkGroup.style.display = 'block';
                uploadInput.required = false;
                uploadInput.disabled = true;
                // Clear file input by creating new element
                const newUploadInput = uploadInput.cloneNode(true);
                uploadInput.parentNode.replaceChild(newUploadInput, uploadInput);
                linkInput.required = true;
                linkInput.disabled = false;
            }
        }

        function resetForm() {
            if (confirm('Are you sure you want to clear the form? All entered data will be lost.')) {
                document.getElementById('sessionUploadForm').reset();
            }
        }

        // Handle form submission with progress
        document.getElementById('sessionUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            console.log('=== SESSION UPLOAD STARTED ===');

            const form = this;
            const submitBtn = form.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            const progressContainer = document.getElementById('uploadProgressContainer');
            const progressBar = document.getElementById('uploadProgressBar');
            const progressText = document.getElementById('uploadProgressText');

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressText.textContent = '0 MB / 0 MB (0%)';

            const formData = new FormData(form);

            // Add contentType from selected radio button
            const contentTypeRadio = document.querySelector('input[name="contentType"]:checked');
            if (contentTypeRadio) {
                formData.append('contentType', contentTypeRadio.value);
            }

            // Log form data for debug
            console.log('Form Data:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(`  ${key}: ${value.name} (${value.size} bytes, type: ${value.type})`);
                } else {
                    console.log(`  ${key}: ${value}`);
                }
            }

            function formatBytes(bytes) {
                if (bytes === 0) return '0 MB';
                const mbs = bytes / (1024 * 1024);
                return mbs.toFixed(2) + ' MB';
            }

            function finalize(success, message) {
                if (success) {
                    if (typeof showMessage === 'function') {
                        showMessage(message || 'Session created successfully!', 'success');
                    } else {
                        alert('✓ ' + (message || 'Session created successfully!'));
                    }
                    form.reset();
                } else {
                    if (typeof showMessage === 'function') {
                        showMessage(message || 'Failed to create session', 'error');
                    } else {
                        alert('✗ ' + (message || 'Failed to create session'));
                    }
                }

                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                console.log('=== SESSION UPLOAD ENDED ===');
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.API_BASE_URL + 'sessions.php?action=upload');

            xhr.upload.addEventListener('progress', function(event) {
                if (!event.lengthComputable) return;
                const percent = Math.round((event.loaded / event.total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                progressText.textContent = `${formatBytes(event.loaded)} / ${formatBytes(event.total)} (${percent}%)`;
            });

            xhr.addEventListener('load', function() {
                let result;
                try {
                    result = JSON.parse(xhr.responseText);
                } catch (parseError) {
                    console.error('Failed to parse JSON:', parseError);
                    console.error('Response was:', xhr.responseText);
                    finalize(false, 'Server returned invalid JSON');
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300 && result.success) {
                    console.log('✅ Success! Session ID:', result.sessionId);
                    finalize(true, result.message || 'Session created successfully!');
                } else {
                    console.error('❌ Failed:', result && result.message ? result.message : 'Upload failed');
                    if (result && result.errors) {
                        console.error('Validation Errors:', result.errors);
                    }
                    finalize(false, result && result.message ? result.message : 'Upload failed');
                }
            });

            xhr.addEventListener('error', function() {
                console.error('Network error during upload');
                finalize(false, 'Network error occurred during upload');
            });

            xhr.addEventListener('abort', function() {
                console.warn('Upload aborted by user or network');
                finalize(false, 'Upload was interrupted. Please try again.');
            });

            xhr.addEventListener('timeout', function() {
                console.warn('Upload timed out');
                finalize(false, 'Upload timed out. Please try again.');
            });

            // Avoid premature server timeout; set high ceiling in milliseconds (15 minutes)
            xhr.timeout = 15 * 60 * 1000;

            xhr.send(formData);
        });

        function createVideoItem(number) {
            const div = document.createElement('div');
            div.className = 'video-upload-item';
            div.innerHTML = `
                <h4>Video ${number}</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Video Title *</label>
                        <input type="text" name="videoTitle[]" required placeholder="e.g., Part ${number} - Introduction">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Video Source *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="videoSource[${number-1}]" value="upload" onchange="toggleVideoInput(this)"> Upload File
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="videoSource[${number-1}]" value="link" checked onchange="toggleVideoInput(this)"> Video Link
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group video-upload-group" style="display: none;">
                        <label>Video File *</label>
                        <input type="file" name="videoFile[]" accept="video/*" required disabled>
                        <small class="file-info">Supported formats: MP4, AVI, MOV (No app-level limit, server/PHP size limits still apply)</small>
                    </div>

                    <div class="form-group video-link-group" style="display: block;">
                        <label>Video URL *</label>
                        <input type="url" name="videoLink[]" required placeholder="e.g., https://youtube.com/watch?v=... or direct video URL">
                        <small class="file-info">YouTube, Vimeo, or direct video links supported</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Video Description</label>
                    <textarea name="videoDescription[]" rows="2" placeholder="Brief description of this video..."></textarea>
                </div>

                <button type="button" class="remove-video-btn" onclick="removeVideo(this)">
                    <i class="fas fa-trash"></i> Remove Video
                </button>
            `;
            return div;
        }

        function removeVideo(button) {
            const item = button.closest('.video-upload-item');
            if (document.querySelectorAll('.video-upload-item').length > 1) {
                item.remove();
                // Update video numbers
                document.querySelectorAll('.video-upload-item h4').forEach((h4, index) => {
                    h4.textContent = `Video ${index + 1}`;
                });
            } else {
                alert('At least one video is required');
            }
        }
    </script>
</body>

</html>
