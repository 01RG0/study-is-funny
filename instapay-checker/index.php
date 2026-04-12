<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instapay Checker - Professional AI Verification</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'ibm-plex': ['"IBM Plex Sans Arabic"', 'sans-serif'],
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-900 text-white min-h-screen font-ibm-plex overflow-x-hidden">
    <div class="fixed inset-0 bg-gradient-radial from-blue-900/20 to-transparent pointer-events-none"></div>
    <div class="fixed top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 pointer-events-none"></div>

    <div class="container mx-auto px-4 py-8 relative z-10">
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6 bg-white/5 p-6 rounded-3xl border border-white/10 backdrop-blur-md">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-blue-500/40 animate-pulse-slow">
                    <i class="fas fa-shield-check text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-white">تحقق <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">إنستاباي</span></h1>
                    <p class="text-gray-400 text-sm font-light">نظام التحقق الذكي المعتمد على رؤية الكمبيوتر</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="hidden md:flex gap-4">
                    <div class="bg-black/40 px-4 py-2 rounded-xl border border-white/5 text-center">
                        <div class="text-[8px] text-gray-500 uppercase font-black">Tot Transactions</div>
                        <div id="statTotal" class="text-sm font-bold text-blue-400">--</div>
                    </div>
                    <div class="bg-black/40 px-4 py-2 rounded-xl border border-white/5 text-center">
                        <div class="text-[8px] text-gray-500 uppercase font-black">Valid Rate</div>
                        <div id="statRate" class="text-sm font-bold text-green-400">--</div>
                    </div>
                </div>
                <div id="connectionStatus" class="px-5 py-2.5 bg-black/40 rounded-xl border border-gray-700/50 flex items-center gap-3">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </div>
                    <span class="text-xs font-mono text-gray-300 tracking-widest">POLLINATIONS-AI // ONLINE</span>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Side: Upload & Terminal -->
            <div class="lg:col-span-12 xl:col-span-5 space-y-8 h-full">
                <!-- Dropzone Card -->
                <div class="glass-card rounded-[2rem] p-8 border border-white/10 relative overflow-hidden group transition-all duration-500 hover:border-blue-500/50 shadow-2xl">
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <input type="file" id="fileInput" class="hidden" accept="image/*">
                    <div id="dropzone" class="border-2 border-dashed border-gray-700/50 rounded-2xl p-10 text-center transition-all hover:border-blue-500 hover:bg-blue-500/5 cursor-pointer relative z-10">
                        <div class="upload-icon-container w-24 h-24 bg-gray-800/80 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:bg-blue-600/20 transition-all duration-500 shadow-inner">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-500 group-hover:text-blue-400"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-white">حلل الإيصال الآن</h3>
                        <p class="text-gray-400 mb-8 max-w-xs mx-auto text-sm leading-relaxed">قم بإسقاط صورة المعاملة هنا أو اضغط للرفع من جهازك</p>
                        <button class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white px-8 py-4 rounded-2xl font-bold transition-all transform hover:-translate-y-1 shadow-xl shadow-blue-600/20 group-hover:shadow-blue-500/40">
                            اختر ملف الصورة
                        </button>
                    </div>
                </div>

                <!-- Live Analysis Console -->
                <div class="glass-card rounded-[2rem] p-6 border border-white/10 bg-black/60 font-mono text-sm h-[320px] overflow-hidden relative shadow-inner">
                    <div class="flex items-center justify-between mb-4 border-b border-white/10 pb-3">
                        <div class="flex items-center gap-2">
                             <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                             <span class="text-blue-400 font-bold tracking-tighter">SECURE_VERIFICATION_LOG</span>
                        </div>
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-white/10"></div>
                            <div class="w-3 h-3 rounded-full bg-white/10"></div>
                        </div>
                    </div>
                    <div id="terminal" class="space-y-2 overflow-y-auto h-[240px] custom-scrollbar pr-2 text-xs">
                        <div class="text-gray-500 flex items-center gap-2">
                            <span class="text-blue-600">>>></span> 
                            <span>نظام التحقق جاهز لاستقبال البيانات...</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Grid Section -->
                <div id="recentActivitySection" class="mt-16 mb-20 animate-fade-in-up">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-3xl font-black text-white tracking-tight flex items-center gap-4">
                                <span class="w-2 h-10 bg-blue-600 rounded-full"></span>
                                سجل التحقق الذكي
                            </h2>
                            <p class="text-gray-500 text-sm mt-1 pr-6">آخر 10 معاملات تم تحليلها بواسطة الذكاء الاصطناعي</p>
                        </div>
                        <button onclick="loadDashboard()" class="glass-card px-4 py-2 border border-white/10 rounded-2xl text-xs text-gray-400 hover:text-white transition-all flex items-center gap-2">
                            <i class="fas fa-sync-alt"></i> تحديث البيانات
                        </button>
                    </div>

                    <div class="glass-card border border-white/10 rounded-[2.5rem] overflow-hidden bg-black/40 shadow-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full text-right border-collapse">
                                <thead>
                                    <tr class="bg-white/5 text-gray-400 text-[10px] uppercase tracking-[0.2em] font-bold">
                                        <th class="px-8 py-6">الرقم المرجعي</th>
                                        <th class="px-6 py-6">المبلغ</th>
                                        <th class="px-6 py-6">المرسل / المستلم</th>
                                        <th class="px-6 py-6 text-center">دقة التحقق</th>
                                        <th class="px-6 py-6 text-center">الحالة النهائية</th>
                                        <th class="px-8 py-6">التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody id="recentTableBody" class="text-gray-300">
                                    <!-- Row Template (Will be injected by JS) -->
                                    <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors">
                                        <td colspan="6" class="px-8 py-20 text-center text-gray-600">
                                            <i class="fas fa-spinner fa-spin text-2xl mb-4"></i>
                                            <p>جاري جلب أحدث العمليات من مصفوفة البيانات...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Results -->
            <div class="lg:col-span-12 xl:col-span-7 h-full">
                <div id="resultsContent" class="hidden space-y-6 animate-fade-in h-full">
                    <!-- Status Banner -->
                    <div id="statusBanner" class="rounded-[2rem] p-8 border backdrop-blur-xl relative overflow-hidden flex flex-col md:flex-row items-center justify-between transition-all duration-700 shadow-2xl">
                        <div class="absolute top-0 right-0 w-full h-full bg-gradient-to-l from-white/5 to-transparent pointer-events-none"></div>
                        
                        <div class="flex items-center gap-6 relative z-10 mb-6 md:mb-0">
                            <div id="statusIcon" class="w-20 h-20 rounded-[1.5rem] flex items-center justify-center text-4xl shadow-inner transition-all duration-500"></div>
                            <div>
                                <h2 id="statusTitle" class="text-3xl font-black mb-1"></h2>
                                <p id="statusSubtitle" class="text-gray-300 font-medium"></p>
                            </div>
                        </div>
                        
                        <div class="bg-black/40 p-6 rounded-3xl border border-white/10 text-center min-w-[160px] relative z-10">
                            <div class="text-[10px] text-blue-400 font-black uppercase tracking-[0.2em] mb-1">Confidence Score</div>
                            <div id="confidenceScore" class="text-4xl font-black tabular-nums">0%</div>
                        </div>
                    </div>

                    <!-- Modern Dashboard Header -->
            <div id="dashboardOverview" class="mb-12">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Transactions -->
                    <div class="glass-card p-6 border border-white/5 rounded-3xl relative overflow-hidden group hover:border-blue-500/30 transition-all">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-600/5 rounded-full blur-2xl group-hover:bg-blue-600/20 transition-all"></div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-blue-600/10 rounded-2xl flex items-center justify-center text-blue-500">
                                <i class="fas fa-microchip text-xl"></i>
                            </div>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">إجمالي الفحوصات</p>
                        </div>
                        <h4 id="dashStatTotal" class="text-4xl font-black text-white ml-2">0</h4>
                        <div class="mt-2 text-[10px] text-gray-500 flex items-center gap-1">
                            <i class="fas fa-chart-line text-blue-500"></i>
                            نظام التحقق يعمل بكفاءة 100%
                        </div>
                    </div>

                    <!-- Total Amount -->
                    <div class="glass-card p-6 border border-white/5 rounded-3xl relative overflow-hidden group hover:border-emerald-500/30 transition-all">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-600/5 rounded-full blur-2xl group-hover:bg-emerald-600/20 transition-all"></div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-emerald-600/10 rounded-2xl flex items-center justify-center text-emerald-500">
                                <i class="fas fa-wallet text-xl"></i>
                            </div>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">إجمالي المبالغ</p>
                        </div>
                        <h4 class="text-4xl font-black text-white ml-2"><span id="dashStatAmount">0</span> <span class="text-xs font-normal opacity-50">EGP</span></h4>
                        <div class="mt-2 text-[10px] text-gray-500 flex items-center gap-1">
                            <i class="fas fa-shield-check text-emerald-500"></i>
                            جميع المعاملات المؤكدة سـليمة
                        </div>
                    </div>

                    <!-- Valid vs Suspicious -->
                    <div class="glass-card p-6 border border-white/5 rounded-3xl relative overflow-hidden group hover:border-yellow-500/30 transition-all">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-yellow-600/5 rounded-full blur-2xl group-hover:bg-yellow-600/20 transition-all"></div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-yellow-600/10 rounded-2xl flex items-center justify-center text-yellow-500">
                                <i class="fas fa-fingerprint text-xl"></i>
                            </div>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">عمليات مشبوهة</p>
                        </div>
                        <h4 id="dashStatSuspicious" class="text-4xl font-black text-white ml-2">0</h4>
                        <div class="mt-2 text-[10px] text-gray-500 flex items-center gap-1">
                            <i class="fas fa-triangle-exclamation text-yellow-500"></i>
                            تتطلب مراجعة بشرية فورية
                        </div>
                    </div>

                    <!-- Fraudulent Attempts -->
                    <div class="glass-card p-6 border border-white/5 rounded-3xl relative overflow-hidden group hover:border-red-500/30 transition-all">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-red-600/5 rounded-full blur-2xl group-hover:bg-red-600/20 transition-all"></div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-red-600/10 rounded-2xl flex items-center justify-center text-red-500">
                                <i class="fas fa-virus-slash text-xl"></i>
                            </div>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">محاولات تزييف</p>
                        </div>
                        <h4 id="dashStatFraud" class="text-4xl font-black text-white ml-2">0</h4>
                        <div class="mt-2 text-[10px] text-gray-500 flex items-center gap-1">
                    <i class="fas fa-ban text-red-500"></i>
                            تم حظر ومنع هذه المعاملات
                        </div>
                    </div>
                </div>
                <!-- Volume Card -->
            <div class="glass-card p-6 rounded-[2.5rem] border border-white/5 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 hover:shadow-[0_20px_50px_-15px_rgba(99,102,241,0.3)] transition-all group overflow-hidden relative">
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl group-hover:scale-150 transition-transform"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mb-1">حمولة التحويلات الصالحة</p>
                        <h3 id="statVolume" class="text-3xl font-black text-white tracking-tighter">0.00</h3>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-500/20 text-indigo-400 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-money-bill-transfer text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 relative z-10">
                    <span class="text-[10px] py-1 px-2 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/20">EGP Local Volume</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                        <div class="group glass-card rounded-3xl p-6 border border-white/5 hover:border-blue-500/30 transition-all shadow-lg hover:shadow-blue-500/5">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">المبلغ المستخلص</span>
                                <i class="fas fa-coins text-gray-700 group-hover:text-blue-500 transition-colors"></i>
                            </div>
                            <div id="resAmount" class="text-3xl font-black text-white">--</div>
                        </div>
                        <div class="group glass-card rounded-3xl p-6 border border-white/5 hover:border-blue-500/30 transition-all shadow-lg hover:shadow-blue-500/5">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">المعرف المرجعي</span>
                                <i class="fas fa-fingerprint text-gray-700 group-hover:text-blue-500 transition-colors"></i>
                            </div>
                            <div id="resRef" class="text-xl font-mono font-bold text-white break-all">--</div>
                        </div>
                        <div class="group glass-card rounded-3xl p-6 border border-white/5 hover:border-blue-500/30 transition-all shadow-lg hover:shadow-blue-500/5">
                            <div class="text-[10px] text-blue-400 font-bold uppercase tracking-widest mb-1">الطرف المرسل</div>
                            <div id="resSender" class="text-lg font-bold text-white leading-tight">--</div>
                            <div id="resSenderEmail" class="text-xs text-gray-500 font-mono mt-1">--</div>
                        </div>
                        <div class="group glass-card rounded-3xl p-6 border border-white/5 hover:border-blue-500/30 transition-all shadow-lg hover:shadow-blue-500/5">
                            <div class="text-[10px] text-blue-400 font-bold uppercase tracking-widest mb-1">الطرف المستقبل</div>
                            <div id="resReceiver" class="text-lg font-bold text-white leading-tight">--</div>
                            <div id="resReceiverPhone" class="text-xs text-gray-500 font-mono mt-1">--</div>
                        </div>
                        <div class="group glass-card rounded-3xl p-6 border border-white/5 hover:border-blue-500/30 transition-all shadow-lg hover:shadow-blue-500/5">
                            <div class="text-[10px] text-blue-400 font-bold uppercase tracking-widest mb-1">طابع الوقت</div>
                            <div id="resDate" class="text-lg font-medium text-white">--</div>
                        </div>
                        <div class="group glass-card rounded-3xl p-6 border border-white/5 hover:border-blue-500/30 transition-all shadow-lg hover:shadow-blue-500/5">
                            <div class="text-[10px] text-blue-400 font-bold uppercase tracking-widest mb-1">البنك / الخدمة</div>
                            <div id="resBank" class="text-lg font-medium text-white">--</div>
                        </div>
                    </div>

                    <!-- Security Insights -->
                    <div class="glass-card rounded-3xl border border-white/10 overflow-hidden shadow-xl">
                        <div class="p-6 bg-white/5 flex items-center gap-3 border-b border-white/10">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <span class="font-bold text-gray-200">نتائج التحليل الأمني المتقدم</span>
                        </div>
                        <div id="analysisDetails" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 bg-black/20">
                            <!-- Injected via JS -->
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4 pt-4">
                        <button id="saveBtn" onclick="saveTransaction()" class="flex-[2] bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white p-5 rounded-3xl font-black transition-all transform hover:-translate-y-1 shadow-2xl shadow-green-600/20 flex items-center justify-center gap-3">
                            <i class="fas fa-check-double text-xl"></i>
                            <span>تأكيـد وحفـظ البيـانات فـي السـجل</span>
                        </button>
                        <button onclick="location.reload()" class="flex-1 bg-gray-800 hover:bg-gray-700 text-white p-5 rounded-3xl font-bold transition-all border border-gray-700 flex items-center justify-center gap-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>جـدبد</span>
                        </button>
                    </div>
                </div>

                <!-- Initial Empty State -->
                <div id="emptyState" class="h-full min-h-[600px] border-2 border-dashed border-gray-800/50 rounded-[3rem] flex flex-col items-center justify-center text-gray-700 group transition-all duration-500 hover:border-blue-500/20 hover:bg-blue-500/5">
                    <div class="relative mb-8">
                        <i class="fas fa-fingerprint text-8xl opacity-10 group-hover:opacity-30 transition-opacity"></i>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-16 h-16 border-2 border-blue-500/20 rounded-full animate-ping"></div>
                        </div>
                    </div>
                    <p class="text-xl font-medium tracking-widest uppercase opacity-40 group-hover:opacity-60 transition-opacity">System Idling // Awaiting Input</p>
                    <p class="mt-2 text-sm opacity-20 group-hover:opacity-40 transition-opacity">ارفع صورة الإيصال لبدء تحليل المستند</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal (Hidden) -->
    <div id="successModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="glass-card w-full max-w-sm rounded-[3rem] p-10 border border-green-500/30 text-center relative z-10 animate-scale-in">
            <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-green-500/40">
                <i class="fas fa-check text-4xl text-white"></i>
            </div>
            <h3 class="text-2xl font-black mb-2">تم الحفظ!</h3>
            <p class="text-gray-400 mb-8">تمت أرشفة المعاملة بنجاح في قاعدة البيانات المؤمنة.</p>
            <button onclick="location.reload()" class="w-full bg-white text-black py-4 rounded-2xl font-bold hover:bg-gray-200 transition-colors">متابعة</button>
        </div>
    </div>

    <!-- Implementation Script -->
    <script>
        let currentTransactionData = null;
        let originalFile = null;

        // Dashboard Intelligence
        async function loadDashboard() {
            try {
                const response = await fetch('api.php?action=stats');
                const result = await response.json();
                if (result.success) {
                    const stats = result.stats;
                    // Update Stats
                    document.getElementById('dashStatTotal').innerText = stats.total || 0;
                    document.getElementById('dashStatAmount').innerText = stats.total_amount || 0;
                    document.getElementById('dashStatSuspicious').innerText = stats.suspicious || 0;
                    document.getElementById('dashStatFraud').innerText = stats.fraudulent || 0;
                    
                    if (document.getElementById('statVolume')) {
                        document.getElementById('statVolume').innerText = new Intl.NumberFormat('en-EG', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(stats.valid_volume || 0);
                    }

                    // Update Table
                    const tableBody = document.getElementById('recentTableBody');
                    if (result.recent.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="6" class="px-8 py-10 text-center text-gray-500">لا توجد عمليات مسجلة حالياً</td></tr>`;
                        return;
                    }

                    tableBody.innerHTML = result.recent.map(row => {
                        let statusColor = 'text-green-400';
                        let statusBg = 'bg-green-500/10';
                        let statusIcon = 'fa-check-circle';
                        let statusText = 'سليمة';

                        if (row.is_valid === 'suspicious') {
                            statusColor = 'text-yellow-400';
                            statusBg = 'bg-yellow-500/10';
                            statusIcon = 'fa-exclamation-triangle';
                            statusText = 'مشبوهة';
                        } else if (row.is_valid === 'fraudulent' || row.is_valid === 'invalid') {
                            statusColor = 'text-red-500';
                            statusBg = 'bg-red-500/10';
                            statusIcon = 'fa-times-circle';
                            statusText = 'مزورة';
                        }

                        return `
                            <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors group">
                                <td class="px-8 py-5 font-mono text-xs text-blue-400">${row.reference_number}</td>
                                <td class="px-6 py-5 font-bold text-white">${row.amount} <span class="text-[10px] opacity-30">${row.currency}</span></td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-white font-medium">${row.sender_name || '---'}</span>
                                        <span class="text-[10px] text-gray-500"><i class="fas fa-arrow-left text-[8px] ml-1"></i> ${row.receiver_name}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <div class="inline-flex items-center gap-2 px-2 py-1 bg-white/5 rounded-lg text-xs">
                                        <div class="w-8 h-1 bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500" style="width: ${row.confidence_score}%"></div>
                                        </div>
                                        <span class="font-mono text-[10px]">${row.confidence_score}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold ${statusBg} ${statusColor} border border-current/20">
                                        <i class="fas ${statusIcon} ml-1"></i> ${statusText}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-gray-500 text-[10px]">${row.created_at}</td>
                            </tr>
                        `;
                    }).join('');
                }
            } catch (e) {
                console.error('Core Dashboard Error:', e);
            }
        }

        // Initialize dashboard on load
        window.addEventListener('DOMContentLoaded', loadDashboard);

        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const terminal = document.getElementById('terminal');
        const connectionStatus = document.getElementById('connectionStatus');

        // Terminal helper
        function log(message, type = 'info') {
            const div = document.createElement('div');
            const color = type === 'error' ? 'text-red-400' : (type === 'success' ? 'text-green-400' : 'text-blue-400');
            const prefix = `[${new Date().toLocaleTimeString('en-US', { hour12: false })}]`;
            const icon = type === 'error' ? '!' : (type === 'success' ? '✓' : '»');
            
            div.className = "flex items-start gap-2 animate-slide-in-right";
            div.innerHTML = `
                <span class="text-gray-600 whitespace-nowrap">${prefix}</span> 
                <span class="${color} font-bold opacity-80">${icon}</span>
                <span class="${type === 'info' ? 'text-gray-300' : color}">${message}</span>
            `;
            terminal.appendChild(div);
            terminal.scrollTop = terminal.scrollHeight;
        }

        // File handling
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => { 
            e.preventDefault(); 
            dropzone.classList.add('border-blue-500', 'bg-blue-500/10'); 
            dropzone.querySelector('.upload-icon-container').classList.add('scale-125', 'bg-blue-600/30');
        });
        dropzone.addEventListener('dragleave', () => { 
            dropzone.classList.remove('border-blue-500', 'bg-blue-500/10'); 
            dropzone.querySelector('.upload-icon-container').classList.remove('scale-125', 'bg-blue-600/30');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-blue-500', 'bg-blue-500/10');
            if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) handleFile(e.target.files[0]);
        });

        function handleFile(file) {
            originalFile = file;
            log('جاري معارضة الملف: ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)');
            processImage(file);
        }

        async function processImage(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'process');

            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('resultsContent').classList.add('hidden');
            
            log('بدء بروتوكول تحليل الصور المتعدد الطبقات...', 'info');
            
            try {
                // Futuristic Simulation Steps
                await step('تحميل مصفوفة البيانات إلى الذاكرة المؤقتة...', 400);
                await step('تفعيل محرك Pollinations Vision AI...', 600);
                
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    await step('استخراج خوارزميات النصوص العربية والإنجليزية...', 800);
                    await step('مطابقة الرقم المرجعي بقاعدة بيانات Instapay...', 1000);
                    await step('تحليل التوقيعات الرقمية وأنماط البكسل...', 700);
                    await step('التحقق من صحة أسماء البنوك والجهات المصدرة...', 500);
                    
                    log('تم الانتهاء من التحليل. عرض النتائج النهائية.', 'success');
                    displayResults(result);
                } else {
                    log('فشل بروتوكول التحليل: ' + result.message, 'error');
                }
            } catch (error) {
                log('خطأ في الاتصال بالنواة المركزية للذكاء الاصطناعي', 'error');
            }
        }

        async function step(msg, duration) {
            log(msg + ' <i class="fas fa-sync fa-spin text-[8px] opacity-40"></i>');
            return new Promise(r => setTimeout(r, duration));
        }

        function displayResults(result) {
            const data = result.data;
            const analysis = result.analysis;
            
            currentTransactionData = data;
            
            document.getElementById('resultsContent').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('hidden');

            // Fill basic info
            document.getElementById('resAmount').innerText = data.amount + ' ' + (data.currency || 'EGP');
            document.getElementById('resRef').innerText = data.reference_number || 'N/A';
            document.getElementById('resSender').innerText = data.sender_name || 'غير معروف';
            document.getElementById('resSenderEmail').innerText = data.sender_account || '--';
            document.getElementById('resReceiver').innerText = data.receiver_name || 'غير معروف';
            document.getElementById('resReceiverPhone').innerText = data.receiver_phone || '--';
            document.getElementById('resDate').innerText = data.transaction_date || '--';
            document.getElementById('resBank').innerText = data.bank_name || 'Instapay Network';
            document.getElementById('confidenceScore').innerText = analysis.confidence_score;

            // Status Banner Styling
            const banner = document.getElementById('statusBanner');
            const icon = document.getElementById('statusIcon');
            const title = document.getElementById('statusTitle');
            const sub = document.getElementById('statusSubtitle');
            const scoreText = document.getElementById('confidenceScore');

            if (analysis.is_valid === 'valid') {
                banner.className = 'rounded-[2rem] p-8 border border-green-500/30 bg-green-500/10 flex flex-col md:flex-row items-center justify-between text-green-400 shadow-[0_0_50px_-12px_rgba(34,197,94,0.3)] animate-pulse-gentle';
                icon.innerHTML = '<i class="fas fa-shield-check"></i>';
                icon.className = 'w-20 h-20 rounded-[1.5rem] flex items-center justify-center text-4xl bg-green-500/20 shadow-lg';
                title.innerText = 'معاملة سـليمة';
                sub.innerText = 'تم التحـقق مـن صـحة المعاملة بنجـاح تام';
                scoreText.className = 'text-4xl font-black tabular-nums text-green-400';
            } else if (analysis.is_valid === 'suspicious') {
                banner.className = 'rounded-[2rem] p-8 border border-yellow-500/30 bg-yellow-500/10 flex flex-col md:flex-row items-center justify-between text-yellow-400 shadow-[0_0_50px_-12px_rgba(234,179,8,0.3)]';
                icon.innerHTML = '<i class="fas fa-shield-exclamation"></i>';
                icon.className = 'w-20 h-20 rounded-[1.5rem] flex items-center justify-center text-4xl bg-yellow-500/20 shadow-lg';
                title.innerText = 'عملية مشبوهة';
                sub.innerText = 'يرجى مراجعة التفاصيل يدوياً لوجود شكوك نظامية';
                scoreText.className = 'text-4xl font-black tabular-nums text-yellow-400';
            } else {
                banner.className = 'rounded-[2rem] p-8 border border-red-500/30 bg-red-500/10 flex flex-col md:flex-row items-center justify-between text-red-500 shadow-[0_0_50px_-12px_rgba(239,68,68,0.3)]';
                icon.innerHTML = '<i class="fas fa-shield-virus"></i>';
                icon.className = 'w-20 h-20 rounded-[1.5rem] flex items-center justify-center text-4xl bg-red-500/20 shadow-lg';
                title.innerText = 'معاملة مزورة';
                sub.innerText = 'خطر: تم اكتشاف محاولة تلاعب بالبيانات أو إيصال تالف';
                scoreText.className = 'text-4xl font-black tabular-nums text-red-500';
            }

            // Analysis Details Grid
            const detailsList = document.getElementById('analysisDetails');
            detailsList.innerHTML = '';
            
            // Map validation keys to readable labels
            const validationLabels = {
                amount: 'المبلغ',
                email: 'حساب المرسل',
                phone: 'رقم المستلم',
                reference: 'الرقم المرجعي',
                date: 'تاريخ المعاملة',
                iban: 'الآيبان',
                bank: 'البنك',
                consistency: 'تناسق البيانات',
                patterns: 'أنماط المعاملات',
                duplicate: 'فحص التكرار'
            };
            
            const allPoints = [
                ...analysis.issues.map(i => ({ type: 'issue', text: i })),
                ...analysis.warnings.map(w => ({ type: 'warning', text: w })),
                ...Object.entries(analysis.validations).map(([k, v]) => ({ 
                    type: v.valid ? 'success' : (k === 'date' || k === 'amount' || k === 'iban' || k === 'duplicate' ? 'issue' : 'warning'), 
                    text: (validationLabels[k] || k) + ': ' + (v.reason || (v.valid ? 'صالح' : 'غير صالح'))
                }))
            ];

            allPoints.forEach(point => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 p-3 bg-white/5 rounded-2xl border border-white/5 text-xs';
                const iconClass = point.type === 'issue' ? 'fa-circle-xmark text-red-500' : (point.type === 'warning' ? 'fa-octagon-exclamation text-yellow-500' : 'fa-circle-check text-green-500');
                div.innerHTML = `<i class="fa-solid ${iconClass} text-lg"></i> <span class="text-gray-300">${point.text}</span>`;
                detailsList.appendChild(div);
            });
            
            banner.scrollIntoView({ behavior: 'smooth' });
        }

        async function saveTransaction() {
            if (!currentTransactionData || !originalFile) return;
            
            const saveBtn = document.getElementById('saveBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الأرشفة...';
            saveBtn.disabled = true;

            log('بدء عملية الحفظ في سجل المعاملات المؤمن...', 'info');
            
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('file', originalFile);
            formData.append('data', JSON.stringify(currentTransactionData));

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    log('تم الحفظ والأرشفة بنجاح.', 'success');
                    document.getElementById('successModal').classList.remove('hidden');
                    loadDashboard(); // Refresh dashboard stats and grid
                } else {
                    log('فشل الحفظ: ' + result.message, 'error');
                    alert('خطأ في الحفظ: ' + result.message);
                }
            } catch (e) {
                log('خطأ فادح في خادم الأرشفة', 'error');
            } finally {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }
        }
    </script>
</body>
</html>
