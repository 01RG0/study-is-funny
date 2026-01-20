<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Session - Study is Funny</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" class="circular-icon" type="image/png" href="../images/logo.png">
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
            <a href="manage-homework.php" class="nav-item">
                <i class="fas fa-tasks"></i> Manage Homework
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
                            <label for="subject">Subject *</label>
                            <select id="subject" name="subject" required>
                                <option value="">Select Subject</option>
                                <option value="physics">Physics</option>
                                <option value="mathematics">Mathematics</option>
                                <option value="statistics">Statistics</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="grade">Grade Level *</label>
                            <select id="grade" name="grade" required>
                                <option value="">Select Grade</option>
                                <option value="senior1">Senior 1</option>
                                <option value="senior2">Senior 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="teacher">Teacher *</label>
                            <select id="teacher" name="teacher" required>
                                <option value="">Select Teacher</option>
                                <option value="shadyelsharqawy">ENG. Shady Elsharqawy</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                            placeholder="Brief description of the session content..."></textarea>
                    </div>
                </div>

                <!-- Access Control -->
                <div class="form-section">
                    <h3>Access Control</h3>

                    <div class="form-group">
                        <label>Access Type *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="accessType" value="online_session" checked> 
                                Specific Session (Only for students with online_session = true, year auto-determined from grade)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="accessType" value="free_for_all"> 
                                Free for All (Available to everyone)
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="maxViews">Maximum Views per Student</label>
                        <input type="number" id="maxViews" name="maxViews" min="1" placeholder="Unlimited">
                        <small class="file-info">Leave empty for unlimited views</small>
                    </div>

                    <div class="form-group">
                        <label for="sessionAccess">Restrict to Specific Session</label>
                        <select id="sessionAccess" name="sessionAccess">
                            <option value="">No restriction - Available to all</option>
                            <option value="session-1">Session 1</option>
                            <option value="session-2">Session 2</option>
                            <option value="session-3">Session 3</option>
                            <option value="session-4">Session 4</option>
                            <option value="session-5">Session 5</option>
                            <option value="session-6">Session 6</option>
                            <option value="session-7">Session 7</option>
                            <option value="session-8">Session 8</option>
                            <option value="session-9">Session 9</option>
                            <option value="session-10">Session 10</option>
                        </select>
                        <small class="file-info">Optionally restrict this content to students who have accessed a
                            specific session</small>
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
                                    <label>Video Type *</label>
                                    <select name="videoType[]" required>
                                        <option value="lecture">Lecture</option>
                                        <option value="questions">Questions</option>
                                        <option value="summary">Summary</option>
                                        <option value="exercise">Exercise</option>
                                        <option value="homework">Homework</option>
                                    </select>
                                </div>

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
                                            <input type="radio" name="videoSource[]" value="upload" checked
                                                onchange="toggleVideoInput(this)"> Upload File
                                        </label>
                                        <label class="radio-label">
                                            <input type="radio" name="videoSource[]" value="link"
                                                onchange="toggleVideoInput(this)"> Video Link
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group video-upload-group">
                                    <label>Video File *</label>
                                    <input type="file" name="videoFile[]" accept="video/*" required>
                                    <small class="file-info">Supported formats: MP4, AVI, MOV (Max: 500MB)</small>
                                </div>

                                <div class="form-group video-link-group" style="display: none;">
                                    <label>Video URL *</label>
                                    <input type="url" name="videoLink[]"
                                        placeholder="e.g., https://youtube.com/watch?v=... or direct video URL">
                                    <small class="file-info">YouTube, Vimeo, or direct video links supported</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Video Description</label>
                                <textarea name="videoDescription[]" rows="2"
                                    placeholder="Brief description of this video..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Duration (minutes)</label>
                                <input type="number" name="duration[]" min="1" placeholder="Estimated duration">
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
                                <option value="published">Publish Now</option>
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
            </form>
        </div>
    </div>

    <script>
        // Configuration
        window.APP_BASE_URL = window.location.origin + '/';
    </script>
    <script src="js/admin.js"></script>
    <script>
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
                linkInput.required = false;
                linkInput.value = '';
            } else {
                uploadGroup.style.display = 'none';
                linkGroup.style.display = 'block';
                uploadInput.required = false;
                uploadInput.value = '';
                linkInput.required = true;
            }
        }

        function resetForm() {
            if (confirm('Are you sure you want to clear the form? All entered data will be lost.')) {
                document.getElementById('sessionUploadForm').reset();
            }
        }

        // Handle form submission
        document.getElementById('sessionUploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(this);
                
                const response = await fetch('../api/sessions.php?action=upload', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showMessage === 'function') {
                        showMessage('Session created successfully!', 'success');
                    } else {
                        alert('✓ Session created successfully!');
                    }
                    // Redirect to manage sessions page after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = 'manage-sessions.html';
                    }, 1500);
                } else {
                    if (typeof showMessage === 'function') {
                        showMessage(result.message || 'Failed to create session', 'error');
                    } else {
                        alert('✗ ' + (result.message || 'Failed to create session'));
                    }
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof showMessage === 'function') {
                    showMessage('An error occurred while uploading the session', 'error');
                } else {
                    alert('✗ An error occurred while uploading the session');
                }
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Add video functionality
        let videoCount = 1;
        document.getElementById('addVideoBtn').addEventListener('click', function() {
            videoCount++;
            const container = document.getElementById('videosContainer');
            const videoItem = createVideoItem(videoCount);
            container.appendChild(videoItem);
        });

        function createVideoItem(number) {
            const div = document.createElement('div');
            div.className = 'video-upload-item';
            div.innerHTML = `
                <h4>Video ${number}</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Video Type *</label>
                        <select name="videoType[]" required>
                            <option value="lecture">Lecture</option>
                            <option value="questions">Questions</option>
                            <option value="summary">Summary</option>
                            <option value="exercise">Exercise</option>
                            <option value="homework">Homework</option>
                        </select>
                    </div>

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
                                <input type="radio" name="videoSource[${number-1}]" value="upload" checked onchange="toggleVideoInput(this)"> Upload File
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="videoSource[${number-1}]" value="link" onchange="toggleVideoInput(this)"> Video Link
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group video-upload-group">
                        <label>Video File *</label>
                        <input type="file" name="videoFile[]" accept="video/*" required>
                        <small class="file-info">Supported formats: MP4, AVI, MOV (Max: 500MB)</small>
                    </div>

                    <div class="form-group video-link-group" style="display: none;">
                        <label>Video URL *</label>
                        <input type="url" name="videoLink[]" placeholder="e.g., https://youtube.com/watch?v=... or direct video URL">
                        <small class="file-info">YouTube, Vimeo, or direct video links supported</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Video Description</label>
                    <textarea name="videoDescription[]" rows="2" placeholder="Brief description of this video..."></textarea>
                </div>

                <div class="form-group">
                    <label>Duration (minutes)</label>
                    <input type="number" name="duration[]" min="1" placeholder="Estimated duration">
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
