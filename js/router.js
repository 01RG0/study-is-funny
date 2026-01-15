/**
 * Simple URL Router for Study is Funny platform
 * Provides clean URL routing and redirects to appropriate pages
 */

class Router {
    constructor() {
        this.routes = {
            // Main routes
            'home': '/',
            'login': '/login/',
            'register': '/register/',
            'grade': '/grade/',

            // Session routes - dynamic (for future use)
            'session': '/session/'
        };

        this.init();
    }

    init() {
        // Handle initial load
        this.handleRoute();

        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            this.handleRoute();
        });
    }

    /**
     * Navigate to a route
     */
    navigate(path, replace = false) {
        const fullPath = this.resolvePath(path);

        if (replace) {
            window.history.replaceState(null, '', fullPath);
        } else {
            window.history.pushState(null, '', fullPath);
        }

        this.handleRoute();
    }

    /**
     * Resolve a short path to full path
     */
    resolvePath(path) {
        // If it's already a full path, return it
        if (path.startsWith('/')) return path;

        // Handle special cases
        if (path === 'home') return '/';
        if (path.startsWith('session/')) {
            return this.buildSessionPath(path);
        }

        return this.routes[path] || path;
    }

    /**
     * Build session path from short notation - disabled since Senior 3 was removed
     */
    buildSessionPath(path) {
        // Senior 3 content has been removed, redirect to home
        return '/';
    }

    /**
     * Handle current route
     */
    handleRoute() {
        const path = window.location.pathname;
        const search = window.location.search;

        // Handle clean URLs
        if (path.match(/^\/session\/[^\/]+\/[^\/]+\/[^\/]+$/)) {
            // Clean session URL like /session/physics/session1/part1
            this.handleSessionRoute(path);
        } else {
            // Handle normal routes
            this.handleNormalRoute(path + search);
        }
    }

    /**
     * Handle session routes with clean URLs - disabled since Senior 3 was removed
     */
    handleSessionRoute(path) {
        // Senior 3 content has been removed, redirect to home
        window.location.replace('/');
    }

    /**
     * Handle subject routes - currently disabled since Senior 3 was removed
     */
    handleSubjectRoute(pathWithSearch) {
        // Senior 3 content has been removed, redirect to home
        window.location.replace('/');
    }

    /**
     * Handle normal routes
     */
    handleNormalRoute(pathWithSearch) {
        // For now, just let the normal page load
        // This could be enhanced to handle more routing logic
    }

    /**
     * Generate clean URL for session
     */
    getSessionUrl(subject, session, part) {
        return `/session/${subject}/${session}/${part}`;
    }
}

// Global router instance
const router = new Router();

// Export for use in other scripts
window.Router = Router;
window.router = router;