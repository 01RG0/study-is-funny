// DOM Elements
let form, fileInput, fileLabel, fileName, alertBox, phoneInput, subjectGroup, subjectSelect, changeAmountBtn, confirmYes, confirmNo, submitBtn;

// State
let detectedAmount = null;
let studentSubjects = [];
let isProcessing = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [INIT] Page loaded, initializing add-money system...');
    
    // Initialize DOM elements after DOM is loaded
    console.log('🔍 [DOM] Selecting elements...');
    form = document.getElementById('paymentForm');
    console.log('  ✓ form:', form ? 'found' : 'NOT FOUND');
    
    fileInput = document.getElementById('screenshot');
    console.log('  ✓ fileInput:', fileInput ? 'found' : 'NOT FOUND');
    
    fileLabel = document.querySelector('.upload-label');
    console.log('  ✓ fileLabel:', fileLabel ? 'found' : 'NOT FOUND');
    
    fileName = document.getElementById('fileName');
    console.log('  ✓ fileName:', fileName ? 'found' : 'NOT FOUND');
    
    alertBox = document.getElementById('alert');
    console.log('  ✓ alertBox:', alertBox ? 'found' : 'NOT FOUND');
    
    phoneInput = document.getElementById('phone');
    console.log('  ✓ phoneInput:', phoneInput ? 'found' : 'NOT FOUND');
    
    subjectGroup = document.getElementById('subjectGroup');
    console.log('  ✓ subjectGroup:', subjectGroup ? 'found' : 'NOT FOUND');
    
    subjectSelect = document.getElementById('subject');
    console.log('  ✓ subjectSelect:', subjectSelect ? 'found' : 'NOT FOUND');
    
    changeAmountBtn = document.getElementById('changeAmountBtn');
    console.log('  ✓ changeAmountBtn:', changeAmountBtn ? 'found' : 'NOT FOUND');
    
    confirmYes = document.getElementById('confirmYes');
    console.log('  ✓ confirmYes:', confirmYes ? 'found' : 'NOT FOUND');
    
    confirmNo = document.getElementById('confirmNo');
    console.log('  ✓ confirmNo:', confirmNo ? 'found' : 'NOT FOUND');
    
    submitBtn = document.querySelector('.btn-submit');
    console.log('  ✓ submitBtn:', submitBtn ? 'found' : 'NOT FOUND');
    
    initializePage();
    setupEventListeners();
});

function initializePage() {
    console.log('📋 [INIT] Initializing page...');
    const savedPhone = localStorage.getItem('userPhone');
    console.log('  🔐 Checking localStorage for userPhone:', savedPhone ? '✓ Found: ' + savedPhone : '✗ Not found');
    
    if (savedPhone) {
        phoneInput.value = savedPhone;
        console.log('  ✓ Phone set to:', savedPhone);
        loadStudentSubjects(savedPhone);
    } else {
        console.log('  ✗ ERROR: No userPhone in localStorage');
        showAlert('لم يتم العثور على رقم الهاتف. يرجى تسجيل الدخول من صفحة grade أولاً', 'warning');
        disableForm();
    }
}

function setupEventListeners() {
    console.log('🎯 [EVENTS] Setting up event listeners...');
    
    // Form submission (prevent default)
    if (form) {
        form.addEventListener('submit', (e) => e.preventDefault());
        console.log('  ✓ Form submit listener added');
    } else {
        console.log('  ✗ ERROR: Form element not found');
    }

    // File upload
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
        console.log('  ✓ File input change listener added');
    } else {
        console.log('  ✗ ERROR: File input not found');
    }

    // Drag and drop
    if (fileLabel) {
        fileLabel.addEventListener('dragover', handleDragOver);
        fileLabel.addEventListener('dragleave', handleDragLeave);
        fileLabel.addEventListener('drop', handleDrop);
        console.log('  ✓ Drag and drop listeners added');
    } else {
        console.log('  ✗ ERROR: File label not found');
    }

    // Change amount
    if (changeAmountBtn) {
        changeAmountBtn.addEventListener('click', showManualAmount);
        console.log('  ✓ Change amount listener added');
    } else {
        console.log('  ✗ ERROR: Change amount button not found');
    }

    // Confirmation buttons
    if (confirmYes) {
        confirmYes.addEventListener('click', () => submitPayment());
        console.log('  ✓ Confirm yes listener added');
    } else {
        console.log('  ✗ ERROR: Confirm yes button not found');
    }
    
    if (confirmNo) {
        confirmNo.addEventListener('click', showManualAmount);
        console.log('  ✓ Confirm no listener added');
    } else {
        console.log('  ✗ ERROR: Confirm no button not found');
    }
}

function disableForm() {
    console.log('🔒 [DISABLE] Disabling form due to authentication error');
    if (submitBtn) {
        submitBtn.disabled = true;
        console.log('  ✓ Submit button disabled');
    }
    if (fileInput) {
        fileInput.disabled = true;
        console.log('  ✓ File input disabled');
    }
}

async function loadStudentSubjects(phone) {
    console.log('📚 [SUBJECTS] Loading subjects for phone:', phone);
    try {
        const apiUrl = `../api/students.php?action=get&phone=${encodeURIComponent(phone)}`;
        console.log('  🌐 Fetching from:', apiUrl);
        
        const response = await fetch(apiUrl);
        console.log('  📡 Response status:', response.status);
        
        const result = await response.json();
        console.log('  📦 Response data:', result);
        
        if (result.success && result.student && result.student.subjects) {
            studentSubjects = result.student.subjects;
            console.log('  ✓ Subjects loaded successfully:', studentSubjects);
            populateSubjectSelect();
            
            // Show subject selector only if more than one subject
            if (studentSubjects.length > 1) {
                subjectGroup.style.display = 'block';
                console.log('  ✓ Subject selector shown (multiple subjects)');
            } else {
                console.log('  ✓ Subject selector hidden (single subject)');
            }
        } else {
            console.log('  ✗ ERROR: Invalid response or no subjects found');
        }
    } catch (error) {
        console.error('  ✗ ERROR loading subjects:', error);
    }
}

function populateSubjectSelect() {
    console.log('🎨 [POPULATE] Populating subject dropdown with:', studentSubjects);
    
    subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
    console.log('  ✓ Dropdown cleared');
    
    const subjectNames = {
        'physics': 'Physics',
        'mathematics': 'Mathematics',
        'mechanics': 'Mechanics',
        'engineering': 'Engineering',
        'chemistry': 'Chemistry',
        'biology': 'Biology'
    };
    
    studentSubjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject;
        option.text = subjectNames[subject] || subject;
        subjectSelect.appendChild(option);
        console.log('  ✓ Added option:', subject, '→', option.text);
    });
    
    console.log('  ✓ Dropdown populated with', studentSubjects.length, 'subjects');
}

function handleFileSelect(e) {
    console.log('📁 [FILE] File selection triggered');
    console.log('  📊 Files selected:', e.target.files.length);
    console.log('  ⏳ isProcessing:', isProcessing);
    
    if (e.target.files.length > 0 && !isProcessing) {
        const file = e.target.files[0];
        console.log('  ✓ File selected:', file.name);
        console.log('  📏 File size:', (file.size / 1024).toFixed(2) + ' KB');
        console.log('  🔍 File type:', file.type);
        
        // Validate file type
        console.log('  🔎 CHECK: File type validation...');
        if (!file.type.startsWith('image/')) {
            console.log('  ✗ FAIL: Invalid file type');
            showAlert('يرجى اختيار ملف صورة صالح', 'error');
            return;
        }
        console.log('  ✓ PASS: Valid image file');
        
        // Validate file size (5MB max)
        console.log('  🔎 CHECK: File size validation (max 5MB)...');
        if (file.size > 5 * 1024 * 1024) {
            console.log('  ✗ FAIL: File too large');
            showAlert('حجم الملف كبير جداً. الحد الأقصى 5 ميجابايت', 'error');
            return;
        }
        console.log('  ✓ PASS: File size acceptable');

        updateStep(2);
        if (fileName) {
            fileName.textContent = '⏳ جاري تحليل الصورة واكتشاف المبلغ...';
            fileName.classList.add('show');
            console.log('  ✓ UI updated: Showing loading message');
        }
        if (fileLabel) {
            fileLabel.classList.add('has-file');
            console.log('  ✓ UI updated: File label marked as has-file');
        }
        
        console.log('  🚀 Starting amount extraction...');
        extractAmountFromScreenshot(file);
    } else {
        if (isProcessing) {
            console.log('  ⏸️  SKIP: Already processing another file');
        }
        if (e.target.files.length === 0) {
            console.log('  ⏸️  SKIP: No files selected');
        }
    }
}

function handleDragOver(e) {
    console.log('🎯 [DRAG] Drag over event');
    e.preventDefault();
    if (fileLabel) {
        fileLabel.classList.add('dragover');
        console.log('  ✓ UI updated: Dragover style applied');
    }
}

function handleDragLeave(e) {
    console.log('🎯 [DRAG] Drag leave event');
    e.preventDefault();
    if (fileLabel) {
        fileLabel.classList.remove('dragover');
        console.log('  ✓ UI updated: Dragover style removed');
    }
}

function handleDrop(e) {
    console.log('🎯 [DRAG] Drop event');
    e.preventDefault();
    if (fileLabel) {
        fileLabel.classList.remove('dragover');
        console.log('  ✓ UI updated: Dragover style removed');
    }
    
    const files = e.dataTransfer.files;
    console.log('  📊 Dropped files:', files.length);
    
    if (files.length > 0) {
        console.log('  ✓ File dropped:', files[0].name);
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
        console.log('  ✓ File input updated and change event triggered');
    } else {
        console.log('  ⏸️  SKIP: No files dropped');
    }
}

async function extractAmountFromScreenshot(file) {
    console.log('🔍 [EXTRACT] Starting amount extraction from screenshot');
    console.log('  📁 File:', file.name);
    isProcessing = true;
    console.log('  ⏸️  Set isProcessing = true');
    
    try {
        const reader = new FileReader();
        console.log('  📖 Starting file reader...');
        
        reader.onload = async (e) => {
            console.log('  ✓ File loaded successfully');
            const base64Image = e.target.result.split(',')[1];
            console.log('  📦 Base64 image length:', base64Image.length, 'characters');
            
            try {
                console.log('📤 [API] Sending image to API for amount extraction...');
                console.log('  🌐 Endpoint: ../api/payment.php');
                console.log('  🎯 Action: extract_amount');
                
                // Add timeout to prevent hanging
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
                console.log('  ⏱️  Timeout set: 10 seconds');
                
                const response = await fetch('../api/payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'extract_amount',
                        image: base64Image
                    }),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                console.log('  ⏱️  Timeout cleared');
                console.log('  📡 Response status:', response.status, response.statusText);

                if (!response.ok) {
                    const text = await response.text();
                    console.error('  ✗ HTTP Error ' + response.status + ':', text);
                    throw new Error('HTTP ' + response.status);
                }
                console.log('  ✓ HTTP response OK');

                const responseText = await response.text();
                console.log('  📥 Raw API Response length:', responseText.length, 'characters');
                
                let result;
                try {
                    result = JSON.parse(responseText);
                    console.log('  ✓ JSON parsed successfully');
                    console.log('  📦 Parsed result:', result);
                } catch (parseError) {
                    console.error('  ✗ Failed to parse JSON:', parseError);
                    console.error('  Response was:', responseText.substring(0, 200) + '...');
                    throw new Error('Invalid JSON response from server');
                }
                
                if (result.success === true && result.data && result.data.amount) {
                    console.log('  ✅ SUCCESS: Amount detected:', result.data.amount);
                    detectedAmount = parseFloat(result.data.amount);
                    console.log('  💰 Detected amount set to:', detectedAmount);
                    
                    document.getElementById('amount').value = detectedAmount;
                    document.getElementById('amountDisplay').style.display = 'block';
                    document.getElementById('manualAmountGroup').style.display = 'none';
                    document.getElementById('detectedAmountDisplay').textContent = detectedAmount + ' EGP';
                    document.getElementById('confirmAmount').textContent = detectedAmount;
                    fileName.textContent = '✓ ' + file.name + ' - Amount Detected: ' + detectedAmount + ' EGP';
                    fileName.style.color = '#4caf50';
                    console.log('  ✓ UI updated: Showing detected amount');
                    
                    showAlert('تم اكتشاف المبلغ بنجاح', 'success');
                } else {
                    console.log('  ⚠️  WARNING: Could not auto-detect amount');
                    console.log('  📊 Result:', result);
                    detectedAmount = null;
                    console.log('  💰 Detected amount set to: null');
                    
                    document.getElementById('amount').value = '';
                    document.getElementById('amountDisplay').style.display = 'none';
                    document.getElementById('manualAmountGroup').style.display = 'block';
                    fileName.textContent = '⚠️ ' + file.name + ' - Please enter amount manually';
                    fileName.style.color = '#ff9800';
                    console.log('  ✓ UI updated: Showing manual amount input');
                    
                    showAlert('Could not auto-detect amount. Please enter it manually.', 'warning');
                }
            } catch (error) {
                console.error('  ✗ ERROR extracting amount:', error);
                detectedAmount = null;
                console.log('  💰 Detected amount set to: null');
                
                document.getElementById('amount').value = '';
                document.getElementById('amountDisplay').style.display = 'none';
                document.getElementById('manualAmountGroup').style.display = 'block';
                fileName.textContent = '⚠️ ' + file.name + ' - Please enter amount manually';
                fileName.style.color = '#ff9800';
                console.log('  ✓ UI updated: Showing manual amount input');
                
                if (error.name === 'AbortError') {
                    console.error('  ⏱️  Request timed out');
                    showAlert('Request timed out. Please enter amount manually.', 'warning');
                } else {
                    console.error('  🌐 Network error:', error.message);
                    showAlert('Network error: ' + error.message + '. Please enter amount manually.', 'warning');
                }
            }
            
            isProcessing = false;
            console.log('  ⏸️  Set isProcessing = false');
            console.log('🏁 [EXTRACT] Extraction completed');
        };
        
        reader.onerror = () => {
            console.error('  ✗ ERROR: File reader error');
            showAlert('Error reading image file. Please try another image.', 'error');
            isProcessing = false;
            console.log('  ⏸️  Set isProcessing = false');
        };
        
        reader.readAsDataURL(file);
        console.log('  📖 Started reading file as DataURL');
    } catch (error) {
        console.error('  ✗ ERROR: Exception in file reading:', error);
        showAlert('Error reading image file. Please try another image.', 'error');
        isProcessing = false;
        console.log('  ⏸️  Set isProcessing = false');
    }
}

function handlePaymentSubmit() {
    console.log('💳 [SUBMIT] Payment submit button clicked');
    console.log('  ⏳ isProcessing:', isProcessing);
    
    if (isProcessing) {
        console.log('  ⏸️  SKIP: Already processing');
        return;
    }
    
    const amount = detectedAmount || parseFloat(document.getElementById('manualAmount').value);
    const phone = phoneInput.value;
    const screenshot = fileInput.files[0];
    const subject = subjectSelect.value || (studentSubjects.length === 1 ? studentSubjects[0] : '');
    
    console.log('  📊 Form data:');
    console.log('    💰 Amount:', amount, '(detected:', detectedAmount, ', manual:', document.getElementById('manualAmount').value + ')');
    console.log('    📱 Phone:', phone);
    console.log('    📁 Screenshot:', screenshot ? screenshot.name : 'NOT UPLOADED');
    console.log('    📚 Subject:', subject);
    console.log('    📚 Available subjects:', studentSubjects);

    // Validation
    console.log('  🔎 CHECK: Amount validation (50-1000)...');
    if (!amount || isNaN(amount)) {
        console.log('  ✗ FAIL: Invalid amount');
        showAlert('يرجى إدخال المبلغ', 'error');
        showManualAmount();
        return;
    }
    
    if (amount < 50 || amount > 1000) {
        console.log('  ✗ FAIL: Amount out of range');
        showAlert('المبلغ يجب أن يكون بين 50 و 1000 جنيه', 'error');
        showManualAmount();
        return;
    }
    console.log('  ✓ PASS: Amount valid');

    console.log('  🔎 CHECK: Phone validation...');
    if (!phone) {
        console.log('  ✗ FAIL: Phone missing');
        showAlert('رقم الهاتف مطلوب', 'error');
        return;
    }

    if (!/^01[0-9]{9}$/.test(phone)) {
        console.log('  ✗ FAIL: Invalid phone format');
        showAlert('رقم الهاتف غير صحيح', 'error');
        return;
    }
    console.log('  ✓ PASS: Phone valid');

    console.log('  🔎 CHECK: Screenshot validation...');
    if (!screenshot) {
        console.log('  ✗ FAIL: No screenshot uploaded');
        showAlert('يرجى رفع صورة التحويل', 'error');
        return;
    }
    console.log('  ✓ PASS: Screenshot present');

    console.log('  🔎 CHECK: Subject validation...');
    if (studentSubjects.length > 1 && !subject) {
        console.log('  ✗ FAIL: Subject required but not selected');
        showAlert('يرجى اختيار المادة', 'error');
        return;
    }
    console.log('  ✓ PASS: Subject valid');

    // Show confirmation dialog
    console.log('  ✓ All validations passed, showing confirmation...');
    document.getElementById('manualAmountGroup').style.display = 'none';
    document.getElementById('confirmationBox').style.display = 'block';
    document.getElementById('confirmAmount').textContent = amount;
    document.getElementById('amount').value = amount;
    console.log('  ✓ UI updated: Confirmation box shown with amount:', amount);

    // Scroll to confirmation
    document.getElementById('confirmationBox').scrollIntoView({ behavior: 'smooth' });
    console.log('  ✓ Scrolled to confirmation box');
}

function showManualAmount() {
    console.log('✏️  [MANUAL] Showing manual amount input');
    console.log('  💰 Detected amount:', detectedAmount);
    document.getElementById('manualAmountGroup').style.display = 'block';
    document.getElementById('confirmationBox').style.display = 'none';
    if (detectedAmount) {
        document.getElementById('manualAmount').value = detectedAmount;
        console.log('  ✓ Pre-filled manual amount with detected value:', detectedAmount);
    }
    console.log('  ✓ UI updated: Manual amount input shown');
}

async function submitPayment() {
    console.log('🚀 [PAYMENT] Submitting payment to server');
    isProcessing = true;
    console.log('  ⏸️  Set isProcessing = true');
    
    const amount = parseFloat(document.getElementById('amount').value);
    const phone = phoneInput.value;
    const screenshot = fileInput.files[0];
    const subject = subjectSelect.value || (studentSubjects.length === 1 ? studentSubjects[0] : '');

    console.log('  📊 Payment data:');
    console.log('    💰 Amount:', amount);
    console.log('    📱 Phone:', phone);
    console.log('    📁 Screenshot:', screenshot ? screenshot.name : 'NOT UPLOADED');
    console.log('    📚 Subject:', subject);

    // Final validation
    console.log('  🔎 CHECK: Final amount validation...');
    if (!amount || isNaN(amount)) {
        console.log('  ✗ FAIL: Invalid amount');
        showAlert('يرجى إدخال المبلغ', 'error');
        showManualAmount();
        isProcessing = false;
        console.log('  ⏸️  Set isProcessing = false');
        return;
    }
    console.log('  ✓ PASS: Amount valid');

    console.log('  🔎 CHECK: Final amount range validation...');
    if (amount < 50 || amount > 1000) {
        console.log('  ✗ FAIL: Amount out of range');
        showAlert('المبلغ يجب أن يكون بين 50 و 1000 جنيه', 'error');
        showManualAmount();
        isProcessing = false;
        console.log('  ⏸️  Set isProcessing = false');
        return;
    }
    console.log('  ✓ PASS: Amount in range');

    console.log('  🔎 CHECK: Final phone validation...');
    if (!phone || !/^01[0-9]{9}$/.test(phone)) {
        console.log('  ✗ FAIL: Invalid phone');
        showAlert('رقم الهاتف غير صحيح', 'error');
        isProcessing = false;
        console.log('  ⏸️  Set isProcessing = false');
        return;
    }
    console.log('  ✓ PASS: Phone valid');

    console.log('  🔎 CHECK: Final screenshot validation...');
    if (!screenshot) {
        console.log('  ✗ FAIL: No screenshot');
        showAlert('يرجى رفع صورة التحويل', 'error');
        isProcessing = false;
        console.log('  ⏸️  Set isProcessing = false');
        return;
    }
    console.log('  ✓ PASS: Screenshot present');

    console.log('  🔎 CHECK: Final subject validation...');
    if (studentSubjects.length > 1 && !subject) {
        console.log('  ✗ FAIL: Subject required');
        showAlert('يرجى اختيار المادة', 'error');
        isProcessing = false;
        console.log('  ⏸️  Set isProcessing = false');
        return;
    }
    console.log('  ✓ PASS: Subject valid');

    updateStep(3);
    console.log('  ✓ Step updated to 3 (Verification)');

    document.getElementById('formStage').classList.remove('active');
    document.getElementById('confirmationBox').style.display = 'none';
    document.getElementById('loadingStage').classList.add('active');
    console.log('  ✓ UI updated: Showing loading stage');

    const formData = new FormData();
    formData.append('action', 'upload_screenshot');
    formData.append('amount', amount);
    formData.append('phone', phone);
    formData.append('subject', subject);
    formData.append('screenshot', screenshot);
    console.log('  📦 FormData prepared with action: upload_screenshot');

    try {
        console.log('📤 [API] Sending payment to server...');
        console.log('  🌐 Endpoint: ../api/payment.php');
        console.log('  🎯 Action: upload_screenshot');
        
        const response = await fetch('../api/payment.php', {
            method: 'POST',
            body: formData
        });

        console.log('  📡 Response status:', response.status, response.statusText);

        const result = await response.json();
        console.log('  📦 Response data:', result);
        
        document.getElementById('loadingStage').classList.remove('active');
        console.log('  ✓ UI updated: Hiding loading stage');

        if (result.success) {
            console.log('  ✅ SUCCESS: Payment submitted successfully');
            console.log('  📊 Result data:', result.data);
            
            // Log detailed server-side validation results
            console.log('🔍 [SERVER-VALIDATION] Detailed validation results:');
            console.log('  📊 Status:', result.data.status);
            console.log('  📊 Confidence Score:', result.data.confidence_score);
            console.log('  📊 Confidence Level:', result.data.confidence_level);
            
            if (result.data.validations) {
                console.log('  🔎 Validations:', result.data.validations);
                for (const [key, validation] of Object.entries(result.data.validations)) {
                    if (validation.valid) {
                        console.log('    ✓ PASS:', key, '-', validation.reason || 'Valid');
                    } else {
                        console.log('    ✗ FAIL:', key, '-', validation.reason || 'Invalid');
                    }
                }
            }
            
            if (result.data.issues && result.data.issues.length > 0) {
                console.log('  ❌ Issues:', result.data.issues);
                result.data.issues.forEach((issue, i) => {
                    console.log('    Issue ' + (i + 1) + ':', issue);
                });
            }
            
            if (result.data.warnings && result.data.warnings.length > 0) {
                console.log('  ⚠️  Warnings:', result.data.warnings);
                result.data.warnings.forEach((warning, i) => {
                    console.log('    Warning ' + (i + 1) + ':', warning);
                });
            }
            
            if (result.data.fraud_flags && result.data.fraud_flags.length > 0) {
                console.log('  🚨 Fraud Flags:', result.data.fraud_flags);
                result.data.fraud_flags.forEach((flag, i) => {
                    console.log('    Fraud Flag ' + (i + 1) + ':', flag.type, '-', flag.reason, '(severity:', flag.severity + ')');
                });
            }
            
            if (result.data.extracted_data) {
                console.log('  📤 Extracted Data:', result.data.extracted_data);
            }
            
            showResult(result.data);
        } else {
            console.log('  ✗ FAIL: Payment submission failed');
            console.log('  ❌ Error:', result.error);
            showError(result.error || 'حدث خطأ أثناء معالجة التحويل');
            document.getElementById('formStage').classList.add('active');
            console.log('  ✓ UI updated: Showing form again');
        }
    } catch (error) {
        console.error('  ✗ ERROR: Network error during payment submission:', error);
        document.getElementById('loadingStage').classList.remove('active');
        showError('خطأ في الاتصال: ' + error.message);
        document.getElementById('formStage').classList.add('active');
        console.log('  ✓ UI updated: Showing form again');
    }
    
    isProcessing = false;
    console.log('  ⏸️  Set isProcessing = false');
    console.log('🏁 [PAYMENT] Payment submission completed');
}

function showResult(data) {
    console.log('📊 [RESULT] Showing result...');
    console.log('  📊 Status:', data.status);
    console.log('  📊 Transaction ID:', data.transaction_id);
    console.log('  📊 Confidence score:', data.confidence_score);
    console.log('  📊 Confidence level:', data.confidence_level);
    
    const resultDiv = document.getElementById('resultStage');
    resultDiv.classList.add('active');
    let html = '';

    switch(data.status) {
        case 'pending':
            console.log('  ✅ Status: PENDING');
            html = `
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="success-title">تم استلام طلبك</h2>
                    <p class="success-text">
                        تم استلام طلب إضافة رصيد بنجاح. سيتم مراجعته من قبل الإدارة.
                    </p>
                    <div class="transaction-id">
                        رقم المعاملة: <strong>${data.transaction_id}</strong>
                    </div>
                    <div class="confidence-badge ${getConfidenceLabel(data.confidence_level)}">
                        مستوى الثقة: ${data.confidence_level.toUpperCase()}
                    </div>
                </div>
            `;
            break;
        case 'rejected':
            console.log('  ❌ Status: REJECTED');
            let rejectionReasons = '';
            
            // Add issues
            if (data.issues && data.issues.length > 0) {
                rejectionReasons += '<div style="margin: 15px 0; text-align: right;"><strong>أسباب الرفض:</strong><ul style="color: #dc2626; margin-right: 20px;">';
                data.issues.forEach(issue => {
                    rejectionReasons += `<li>${issue}</li>`;
                });
                rejectionReasons += '</ul></div>';
            }
            
            // Add warnings
            if (data.warnings && data.warnings.length > 0) {
                rejectionReasons += '<div style="margin: 15px 0; text-align: right;"><strong>تحذيرات:</strong><ul style="color: #d97706; margin-right: 20px;">';
                data.warnings.forEach(warning => {
                    rejectionReasons += `<li>${warning}</li>`;
                });
                rejectionReasons += '</ul></div>';
            }
            
            html = `
                <div class="success-message">
                    <div class="success-icon" style="color: var(--error-color);">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 class="success-title">تم رفض الطلب</h2>
                    <p class="success-text">
                        عذراً، لم يتم قبول طلبك.
                    </p>
                    ${rejectionReasons}
                    <div class="transaction-id">
                        رقم المعاملة: <strong>${data.transaction_id}</strong>
                    </div>
                </div>
            `;
            break;

        case 'failed':
            console.log('  ⚠️  Status: FAILED');
            console.log('  ⚠️  Issues:', data.issues);
            let failureReasons = '';
            
            // Add issues
            if (data.issues && data.issues.length > 0) {
                failureReasons += '<div style="margin: 15px 0; text-align: right;"><strong>أسباب الفشل:</strong><ul style="color: #d97706; margin-right: 20px;">';
                data.issues.forEach(issue => {
                    failureReasons += `<li>${issue}</li>`;
                });
                failureReasons += '</ul></div>';
            }
            
            // Add warnings
            if (data.warnings && data.warnings.length > 0) {
                failureReasons += '<div style="margin: 15px 0; text-align: right;"><strong>تحذيرات:</strong><ul style="color: #d97706; margin-right: 20px;">';
                data.warnings.forEach(warning => {
                    failureReasons += `<li>${warning}</li>`;
                });
                failureReasons += '</ul></div>';
            }
            
            html = `
                <div class="success-message">
                    <div class="success-icon"><i class="fas fa-exclamation-circle" style="color: #ff9800;"></i></div>
                    <h2 class="success-title" style="color: #ff9800;">فشل التحقق</h2>
                    <p class="success-text">
                        لم يتم التحقق من التحويل.
                    </p>
                    ${failureReasons}
                </div>
            `;
            break;
            
        default:
            console.log('  ❓ Status: UNKNOWN -', data.status);
    }

    resultDiv.innerHTML = html;
    console.log('🏁 [RESULT] Result display completed');
}

function showError(message) {
    console.log('❌ [ERROR] Showing error message');
    console.log('  💬 Message:', message);
    document.getElementById('formStage').classList.add('active');
    document.getElementById('confirmationBox').style.display = 'none';
    console.log('  ✓ UI updated: Form shown, confirmation hidden');
    showAlert(message, 'error');
}

function showAlert(message, type) {
    console.log('🔔 [ALERT] Showing alert');
    console.log('  💬 Message:', message);
    console.log('  🎨 Type:', type);
    if (alertBox) {
        alertBox.textContent = message;
        alertBox.className = 'alert-box show alert-' + type;
        alertBox.scrollIntoView({ behavior: 'smooth' });
        console.log('  ✓ Alert displayed and scrolled into view');
    } else {
        console.error('  ✗ ERROR: alertBox element not found');
    }
}

function getConfidenceLabel(level) {
    console.log('🏷️  [LABEL] Getting confidence label for level:', level);
    const labels = {
        'high': 'ثقة عالية',
        'medium': 'ثقة متوسطة',
        'low': 'ثقة منخفضة',
        'reject': 'رفض'
    };
    const label = labels[level] || 'غير معروف';
    console.log('  ✓ Label:', label);
    return label;
}

function getConfidenceClass(level) {
    console.log('🎨 [CLASS] Getting confidence class for level:', level);
    const classes = {
        'high': 'confidence-high',
        'medium': 'confidence-medium',
        'low': 'confidence-low',
        'reject': 'confidence-low'
    };
    const className = classes[level] || 'confidence-low';
    console.log('  ✓ Class:', className);
    return className;
}

function updateStep(stepNumber) {
    console.log('� [STEP] Updating step to:', stepNumber);
    const steps = document.querySelectorAll('.progress-step');
    console.log('  ✓ Found', steps.length, 'step elements');
    
    steps.forEach((step, i) => {
        const stepIndex = i + 1;
        console.log('  📍 Processing step', stepIndex);
        
        if (i < stepNumber) {
            step.classList.add('completed');
            step.classList.remove('active');
            console.log('  ✓ Step', i, 'set to completed');
        } else if (i === stepNumber) {
            step.classList.add('active');
            step.classList.remove('completed');
            console.log('  ✓ Step', i, 'set to active');
        } else {
            step.classList.remove('active', 'completed');
            console.log('  ✓ Step', i, 'cleared');
        }
    });
}

function checkStatus(transactionId) {
    console.log('🔍 [STATUS] Checking transaction status');
    console.log('  🆔 Transaction ID:', transactionId);
    const url = `./payment-status.php?id=${transactionId}`;
    console.log('  🌐 Navigating to:', url);
    location.href = url;
}
