import { computed, ref } from 'vue';
import api, { ensureCsrfCookie } from '../api/client';

const user = ref(null);
const bootstrapped = ref(false);

export function useAuth() {
    const isAuthenticated = computed(() => !!user.value);

    async function fetchUser() {
        try {
            const { data } = await api.get('/user');
            user.value = data;
            return data;
        } catch {
            user.value = null;
            return null;
        } finally {
            bootstrapped.value = true;
        }
    }

    async function register(payload) {
        await ensureCsrfCookie();
        await api.post('/auth/register', payload);
    }

    async function login(payload) {
        await ensureCsrfCookie();
        await api.post('/auth/login', payload);
        await fetchUser();
    }

    async function logout() {
        try {
            await ensureCsrfCookie();
            await api.post('/auth/logout');
        } finally {
            user.value = null;
        }
    }

    async function verifyEmail(code) {
        await ensureCsrfCookie();
        await api.post('/auth/verify', { code });
    }

    return {
        user,
        bootstrapped,
        isAuthenticated,
        fetchUser,
        register,
        login,
        logout,
        verifyEmail,
    };
}
