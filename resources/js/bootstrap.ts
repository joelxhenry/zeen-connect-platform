import axios from 'axios';

// Configure Axios defaults for cross-subdomain requests
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Set the XSRF token header name to match Laravel's expectation
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Add CSRF token from meta tag as fallback
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

export default axios;
