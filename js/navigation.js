/**
 * Navigation utilities for Study is Funny platform
 * Provides consistent path generation and navigation helpers
 */

class NavigationHelper {
    constructor() {
        this.basePath = this.getBasePath();
        // Wait for router to be available
        this.router = window.router || null;
    }
    constructor() {
        this.basePath = this.getBasePath();
    }

    /**
     * Get the base path relative to current location
     */
    getBasePath() {
        const path = window.location.pathname;
        const segments = path.split('/').filter(s => s);

        // Calculate how many levels deep we are
        let depth = segments.length - 1; // -1 because we want relative to root

        // For session player templates, we need to go up more levels
        if (path.includes('/templates/')) {
            depth += 2; // templates are 2 levels deeper
        } else if (path.includes('/sessions/')) {
            depth += 1; // sessions are 1 level deeper
        }

        return '../'.repeat(Math.max(0, depth));
    }

    /**
     * Generate correct path to any page from current location
     */
    getPath(target) {
        const paths = {
            'home': `${this.basePath}index.html`,
            'login': `${this.basePath}login/index.html`,
            'register': `${this.basePath}register/index.html`,
            'grade': `${this.basePath}grade/index.html`,
            'data': `${this.basePath}data/content.json`,
            'images': `${this.basePath}images/`,
            'css': `${this.basePath}css/`,
            'js': `${this.basePath}js/`
        };

        return paths[target] || target;
    }

    /**
     * Generate breadcrumb navigation HTML
     */
    createBreadcrumb(params = {}) {
        const breadcrumbs = [
            { text: 'الرئيسية', href: this.getPath('home') }
        ];

        // Since Senior 3 content was removed, breadcrumbs are simplified
        if (params.page) {
            breadcrumbs.push({
                text: params.pageTitle || params.page,
                href: '#'
            });
        }

        return breadcrumbs;
    }


    /**
     * Navigate to session part - disabled since Senior 3 content was removed
     */
    navigateToSession(subject, teacher, session, part) {
        // Senior 3 content has been removed, redirect to home
        window.location.href = this.getPath('home');
    }

    /**
     * Navigate to session list - disabled since Senior 3 content was removed
     */
    navigateToSessions(subject, teacher) {
        // Senior 3 content has been removed, redirect to home
        window.location.href = this.getPath('home');
    }

    /**
     * Navigate to buy session - disabled since Senior 3 content was removed
     */
    navigateToBuySession(subject, teacher, session) {
        // Senior 3 content has been removed, redirect to home
        window.location.href = this.getPath('home');
    }

    /**
     * Get current URL parameters
     */
    getUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            subject: urlParams.get('subject'),
            teacher: urlParams.get('teacher'),
            session: urlParams.get('session'),
            part: urlParams.get('part')
        };
    }

    /**
     * Generate session progress indicator
     */
    createProgressIndicator(currentPart, allParts) {
        if (!allParts || !Array.isArray(allParts)) return '';

        const currentIndex = allParts.findIndex(part => part.id === currentPart);
        const progress = ((currentIndex + 1) / allParts.length) * 100;

        return `
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${progress}%"></div>
                </div>
                <div class="progress-text">
                    ${currentIndex + 1} من ${allParts.length}
                </div>
            </div>
        `;
    }

    /**
     * Initialize navigation for a page
     */
    initialize(params = {}) {
        this.updateBreadcrumb(params);
        this.addKeyboardNavigation();
    }

    /**
     * Update breadcrumb display
     */
    updateBreadcrumb(params = {}) {
        const breadcrumbContainer = document.querySelector('.breadcrumb');
        if (!breadcrumbContainer) return;

        const breadcrumbs = this.createBreadcrumb(params);

        const breadcrumbHtml = breadcrumbs.map((crumb, index) => {
            if (index === breadcrumbs.length - 1) {
                return `<span class="current">${crumb.text}</span>`;
            } else {
                return `<a href="${crumb.href}">${crumb.text}</a>`;
            }
        }).join(' <span class="separator">></span> ');

        breadcrumbContainer.innerHTML = breadcrumbHtml;
    }

    /**
     * Add keyboard navigation shortcuts
     */
    addKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Don't trigger if user is typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            switch(e.key) {
                case 'ArrowLeft':
                    // Previous part/session
                    e.preventDefault();
                    this.navigatePrevious();
                    break;
                case 'ArrowRight':
                    // Next part/session
                    e.preventDefault();
                    this.navigateNext();
                    break;
                case 'h':
                case 'H':
                    // Go home
                    e.preventDefault();
                    window.location.href = this.getPath('home');
                    break;
                case 'Escape':
                    // Go back
                    e.preventDefault();
                    window.history.back();
                    break;
            }
        });
    }

    /**
     * Navigate to previous item (to be overridden by specific pages)
     */
    navigatePrevious() {
        console.log('Previous navigation not implemented for this page');
    }

    /**
     * Navigate to next item (to be overridden by specific pages)
     */
    navigateNext() {
        console.log('Next navigation not implemented for this page');
    }
}

// Global navigation instance
const navigationHelper = new NavigationHelper();

// Add progress bar styles if not already present
if (!document.querySelector('#progress-styles')) {
    const style = document.createElement('style');
    style.id = 'progress-styles';
    style.textContent = `
        .progress-container {
            margin: 20px auto;
            max-width: 400px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #008080, #FF8C69);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-text {
            text-align: center;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        body.dark-mode .progress-bar {
            background-color: #444;
        }

        body.dark-mode .progress-text {
            color: #ccc;
        }
    `;
    document.head.appendChild(style);
}

// Export for use in other scripts
window.NavigationHelper = NavigationHelper;
window.navigationHelper = navigationHelper;