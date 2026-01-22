/**
 * API Configuration - Handles correct API path for both local and hosted environments
 * Works with:
 * - Local: http://localhost:8000/
 * - Hostinger: https://studyisfunny.online/study-is-funny/
 */

// Detect the correct API base URL based on current location
function getApiBaseUrl() {
    const protocol = window.location.protocol; // http: or https:
    const host = window.location.host; // localhost:8000 or studyisfunny.online
    const pathname = window.location.pathname; // /study-is-funny/senior2/... or /senior2/...
    
    // Check if we're in a hosted subdirectory (contains 'study-is-funny')
    if (pathname.includes('study-is-funny')) {
        // Extract the base path up to and including 'study-is-funny'
        const basePathMatch = pathname.match(/^(.+?\/study-is-funny)\//);
        const basePath = basePathMatch ? basePathMatch[1] : '/study-is-funny';
        return `${protocol}//${host}${basePath}/api/`;
    }
    
    // Local development or root path
    return `${protocol}//${host}/api/`;
}

// Global API base URL
window.API_BASE_URL = getApiBaseUrl();

// Log for debugging
console.log('✓ API Base URL:', window.API_BASE_URL);
console.log('✓ Current Location:', window.location.href);
