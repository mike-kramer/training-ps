import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export const api = axios.create({
    baseURL: '/api',
});

export async function ensureCsrfCookie() {
    await axios.get('/sanctum/csrf-cookie');
}

export function validationErrors(error) {
    return error?.response?.data?.errors ?? null;
}

export function errorMessage(error, fallback = 'Something went wrong') {
    return error?.response?.data?.message
        || error?.response?.data?.errors?.email?.[0]
        || fallback;
}

export default api;
