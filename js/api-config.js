/**
 * API Configuration - Handles correct API path for both local and hosted environments
 * Works with:
 * - Local: http://localhost:8000/
 * - Production: https://studyisfunny.online/study-is-funny/
 */

// Detect the correct API base URL based on current location
function getApiBaseUrl() {
    const protocol = window.location.protocol; // http: or https:
    const host = window.location.host; // localhost:8000 or studyisfunny.online
    
    // Production domain (deployed under /study-is-funny/)
    if (host.includes('studyisfunny.online')) {
        return `${protocol}//${host}/study-is-funny/api/`;
    }
    
    // Local development
    return `${protocol}//${host}/api/`;
}

// Global API base URL
window.API_BASE_URL = getApiBaseUrl();

// Set additional variables for compatibility
window.BASE_URL = window.API_BASE_URL.replace('/api/', '/');
window.APP_BASE_URL = window.BASE_URL;

// Also expose them as global variables (without window prefix) for legacy compatibility
var BASE_URL = window.BASE_URL;
var API_BASE_URL = window.API_BASE_URL;
var APP_BASE_URL = window.APP_BASE_URL;

// Log for debugging
console.log('✓ API Base URL:', window.API_BASE_URL);
console.log('✓ Base URL:', window.BASE_URL);
console.log('✓ Current Location:', window.location.href);
