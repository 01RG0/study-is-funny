// DOM Elements
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const previewSection = document.getElementById('previewSection');
const previewImage = document.getElementById('previewImage');
const resultSection = document.getElementById('resultSection');
const loadingSection = document.getElementById('loadingSection');
const errorSection = document.getElementById('errorSection');
const successMessage = document.getElementById('successMessage');
const clearBtn = document.getElementById('clearBtn');
const saveBtn = document.getElementById('saveBtn');
const newCheckBtn = document.getElementById('newCheckBtn');

let currentFile = null;
let currentData = null;

// Upload Area Events
uploadArea.addEventListener('click', () => fileInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFileSelect(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

// Handle File Selection
function handleFileSelect(file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showError('يرجى اختيار ملف صورة صحيح');
        return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showError('حجم الصورة كبير جداً (الحد الأقصى 5MB)');
        return;
    }

    currentFile = file;
    displayPreview(file);
    hideAllSections();
    previewSection.style.display = 'block';
}

// Display Image Preview
function displayPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImage.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// Clear Preview
clearBtn.addEventListener('click', () => {
    currentFile = null;
    fileInput.value = '';
    hideAllSections();
    uploadArea.click();
});

// New Check Button
newCheckBtn.addEventListener('click', () => {
    currentFile = null;
    currentData = null;
    fileInput.value = '';
    hideAllSections();
    previewSection.style.display = 'none';
});

// Save Transaction
saveBtn.addEventListener('click', async () => {
    if (!currentData) return;

    saveBtn.disabled = true;
    saveBtn.textContent = 'جاري الحفظ...';

    try {
        const formData = new FormData();
        formData.append('action', 'save');
        formData.append('data', JSON.stringify(currentData));
        formData.append('file', currentFile);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('تم حفظ المعاملة بنجاح');
            loadStats();
            setTimeout(() => {
                newCheckBtn.click();
            }, 2000);
        } else {
            showError(result.message || 'حدث خطأ أثناء الحفظ');
        }
    } catch (error) {
        showError('خطأ في الاتصال بالخادم: ' + error.message);
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'حفظ المعاملة';
    }
});

// Process Image
previewImage.addEventListener('load', async () => {
    // Automatically process after preview loads
    await processImage();
});

async function processImage() {
    if (!currentFile) return;

    hideAllSections();
    loadingSection.style.display = 'block';

    try {
        const formData = new FormData();
        formData.append('action', 'process');
        formData.append('file', currentFile);

        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            currentData = result.data;
            displayResults(result);
        } else {
            showError(result.message || 'فشل في معالجة الصورة');
        }
    } catch (error) {
        showError('خطأ في الاتصال: ' + error.message);
    }
}

// Display Results
function displayResults(result) {
    hideAllSections();
    resultSection.style.display = 'block';

    const data = result.data;
    const analysis = result.analysis;

    // Update Validity Badge
    const badge = document.getElementById('validityBadge');
    const statusIndicator = document.getElementById('statusIndicator');
    const statusMessage = document.getElementById('statusMessage');
    const duplicateIndicator = document.getElementById('duplicateIndicator');
    const duplicateMessage = document.getElementById('duplicateMessage');

    // Validity status
    badge.className = 'badge';
    if (analysis.is_valid === 'valid') {
        badge.classList.add('valid');
        badge.textContent = '✓ معاملة صحيحة';
        statusIndicator.className = 'status-indicator valid';
        statusMessage.textContent = 'المعاملة تبدو حقيقية وتحتوي على جميع البيانات المطلوبة';
    } else if (analysis.is_valid === 'suspicious') {
        badge.classList.add('suspicious');
        badge.textContent = '⚠ معاملة مريبة';
        statusIndicator.className = 'status-indicator suspicious';
        statusMessage.textContent = 'المعاملة قد تحتوي على تحريفات أو بيانات ناقصة';
    } else {
        badge.classList.add('invalid');
        badge.textContent = '✕ معاملة مزيفة';
        statusIndicator.className = 'status-indicator invalid';
        statusMessage.textContent = 'المعاملة تحتوي على علامات مزيفة أو بيانات غير صحيحة';
    }

    // Duplicate status
    if (analysis.is_duplicate) {
        duplicateIndicator.className = 'duplicate-indicator duplicate';
        duplicateMessage.innerHTML = `<strong style="color: var(--danger-color);">تم العثور على معاملة مطابقة!</strong><br>رقم المرجع: ${analysis.duplicate_ref}<br>التاريخ: ${analysis.duplicate_date}`;
    } else {
        duplicateIndicator.className = 'duplicate-indicator no-duplicate';
        duplicateMessage.textContent = 'لم يتم العثور على معاملات مطابقة في قاعدة البيانات';
    }

    // Display extracted data
    const dataGrid = document.getElementById('dataGrid');
    dataGrid.innerHTML = '';

    const dataFields = [
        { key: 'amount', label: 'المبلغ' },
        { key: 'currency', label: 'العملة' },
        { key: 'sender_account', label: 'حساب المرسل' },
        { key: 'sender_name', label: 'اسم المرسل' },
        { key: 'receiver_name', label: 'اسم المستقبل' },
        { key: 'receiver_phone', label: 'هاتف المستقبل' },
        { key: 'reference_number', label: 'رقم المرجع' },
        { key: 'transaction_date', label: 'التاريخ والوقت' },
        { key: 'bank_name', label: 'اسم البنك' },
        { key: 'transaction_type', label: 'نوع المعاملة' },
        { key: 'confidence_score', label: 'درجة الثقة' }
    ];

    dataFields.forEach(field => {
        const value = data[field.key] || 'غير متوفر';
        const item = document.createElement('div');
        item.className = 'data-item';
        item.innerHTML = `
            <label>${field.label}</label>
            <value>${escapeHtml(value)}</value>
        `;
        dataGrid.appendChild(item);
    });

    loadStats();
}

// Show/Hide Sections
function hideAllSections() {
    previewSection.style.display = 'none';
    resultSection.style.display = 'none';
    loadingSection.style.display = 'none';
    errorSection.style.display = 'none';
}

// Show Error
function showError(message) {
    hideAllSections();
    errorSection.style.display = 'block';
    document.getElementById('errorMessage').textContent = message;
}

// Show Success
function showSuccess(message) {
    successMessage.style.display = 'flex';
    document.getElementById('successText').textContent = message;
    setTimeout(() => {
        successMessage.style.display = 'none';
    }, 3000);
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load Statistics
async function loadStats() {
    try {
        const response = await fetch('api.php?action=stats');
        const stats = await response.json();

        if (stats.success) {
            document.getElementById('totalTransactions').textContent = stats.total;
            document.getElementById('validTransactions').textContent = stats.valid;
            document.getElementById('suspiciousTransactions').textContent = stats.suspicious;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Load stats on page load
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
});

// Keyboard shortcut: Press 'U' to upload
document.addEventListener('keydown', (e) => {
    if (e.key.toLowerCase() === 'u' && !e.ctrlKey && !e.metaKey) {
        uploadArea.click();
    }
});
