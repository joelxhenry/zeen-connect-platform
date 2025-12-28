import axios from 'axios';

// Configure Axios defaults for cross-subdomain requests
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Set the XSRF token header name to match Laravel's expectation
// Axios will automatically read the XSRF-TOKEN cookie and send it as X-XSRF-TOKEN
// This is the preferred method as it stays fresh across Inertia navigations
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

export default axios;
