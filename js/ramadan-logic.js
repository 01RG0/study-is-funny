document.addEventListener('DOMContentLoaded', () => {
    // 1. حقن العناصر في الصفحة
    const container = document.createElement('div');
    container.className = 'ramadan-container';
    
    // إضافة الزينة والنجوم
    container.innerHTML = `
        <div class="ramadan-zeenah"></div>
        <div class="ramadan-countdown">
            <span class="countdown-label">المتبقي على الإفطار</span>
            <span class="countdown-time" id="iftar-timer">--:--:--</span>
        </div>
        <div class="ramadan-greeting">🌙 رمضان كريم - Ramadan Kareem</div>
        <div id="ramadan-start-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:20000; cursor:pointer; display:none;"></div>
    `;
    // إضافة النجوم عشوائياً
    for (let i = 0; i < 20; i++) {
        const star = document.createElement('div');
        star.className = 'ramadan-star';
        star.style.width = star.style.height = `${Math.random() * 3 + 1}px`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        star.style.animationDelay = `${Math.random() * 5}s`;
        container.appendChild(star);
    }
    // وظيفة إنشاء الفانوس
    const createLantern = (pos) => {
        const lantern = document.createElement('div');
        lantern.className = 'ramadan-lantern';
        lantern.style[pos.side] = pos.val;
        lantern.innerHTML = `<svg viewBox="0 0 50 100"><path d="M25 0 L15 15 L35 15 Z" fill="#ffd700"/><path d="M12 20 L38 20 L42 60 L8 60 Z" fill="rgba(255, 215, 0, 0.3)" stroke="#ffd700" stroke-width="2"/><circle cx="25" cy="40" r="8" fill="#fff" fill-opacity="0.5"><animate attributeName="opacity" values="0.2;0.8;0.2" dur="3s" repeatCount="indefinite" /></circle><path d="M8 60 L42 60 L38 80 L12 80 Z" fill="#f0c27b"/></svg>`;
        return lantern;
    };
    container.appendChild(createLantern({side: 'left', val: '5%'}));
    container.appendChild(createLantern({side: 'right', val: '5%'}));
    document.body.appendChild(container);
    // 2. منطق العداد التنازلي
    let iftarTime = null;
    const fetchIftar = async () => {
        try {
            const res = await fetch('https://api.aladhan.com/v1/timingsByCity?city=Alexandria&country=Egypt&method=5');
            const data = await res.json();
            if(data.code === 200) {
                const [h, m] = data.data.timings.Maghrib.split(':');
                iftarTime = new Date();
                iftarTime.setHours(h, m, 0);
            }
        } catch(e) { console.error("API Error", e); }
    };
    const updateTimer = () => {
        if(!iftarTime) return;
        let diff = iftarTime - new Date();
        if(diff < 0) {
            if(diff > -7200000) { document.getElementById('iftar-timer').innerText = "صوماً مقبولاً!"; return; }
            iftarTime.setDate(iftarTime.getDate() + 1);
            diff = iftarTime - new Date();
        }
        const h = Math.floor(diff/3600000).toString().padStart(2,'0');
        const m = Math.floor((diff%3600000)/60000).toString().padStart(2,'0');
        const s = Math.floor((diff%60000)/1000).toString().padStart(2,'0');
        document.getElementById('iftar-timer').innerText = `${h}:${m}:${s}`;
    };
    fetchIftar().then(() => setInterval(updateTimer, 1000));
    // 3. منطق الموسيقى الرمضانية
    const getBaseDir = () => {
        const path = window.location.pathname;
        if (path.includes('/student/') || path.includes('/senior1/') || path.includes('/senior2/') || path.includes('/senior3/') || path.includes('/login/')) {
            return '../';
        }
        if (path.includes('/sessions/')) {
            return '../../../';
        }
        return '';
    };

    const baseDir = getBaseDir();
    const audio = new Audio(`${baseDir}images/ramadan_gana.mp3`);
    audio.loop = true;

    const musicBtn = document.createElement('div');
    musicBtn.className = 'ramadan-music-control';
    musicBtn.innerHTML = '<i class="fas fa-music"></i>';
    musicBtn.title = 'تشغيل/إيقاف الموسيقى';
    document.body.appendChild(musicBtn);

    let isPlaying = false;

    // وظيفة تشغيل الموسيقى مع تحديث الواجهة
    const playMusic = () => {
        audio.play().then(() => {
            if (!isPlaying) {
                isPlaying = true;
                musicBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                musicBtn.classList.add('playing');
                document.getElementById('ramadan-start-overlay').style.display = 'none';
                console.log("Ramadan music started");
            }
        }).catch(e => {
            console.log("Autoplay blocked, showing interaction overlay");
            document.getElementById('ramadan-start-overlay').style.display = 'block';
        });
    };

    // محاولة التشغيل فوراً
    playMusic();

    musicBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (isPlaying) {
            audio.pause();
            musicBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
            musicBtn.classList.remove('playing');
            isPlaying = false;
        } else {
            playMusic();
        }
    });

    // تشغيل عند النقر على أي مكان في الصفحة (الغشاء الشفاف)
    document.getElementById('ramadan-start-overlay').addEventListener('click', () => {
        playMusic();
    });

    // تشغيل عند أول تفاعل في الصفحة كاحتياط
    const startOnInteraction = () => {
        if (!isPlaying) {
            playMusic();
            document.removeEventListener('scroll', startOnInteraction);
            document.removeEventListener('keydown', startOnInteraction);
        }
    };

    document.addEventListener('scroll', startOnInteraction);
    document.addEventListener('keydown', startOnInteraction);
});
