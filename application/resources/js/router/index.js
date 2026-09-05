import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const routes = [
    {
        path: '/',
        redirect: '/cashboxes',
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../pages/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/verify',
        name: 'verify',
        component: () => import('../pages/VerifyEmail.vue'),
        meta: { auth: true },
    },
    {
        path: '/cashboxes',
        name: 'cashboxes',
        component: () => import('../pages/Cashboxes.vue'),
        meta: { auth: true },
    },
    {
        path: '/process-payment/:id',
        name: 'process-payment',
        component: () => import('../pages/ProcessPayment.vue'),
        meta: { public: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const { bootstrapped, fetchUser, isAuthenticated } = useAuth();

    if (!bootstrapped.value) {
        await fetchUser();
    }

    if (to.meta.auth && !isAuthenticated.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && isAuthenticated.value) {
        return { name: 'cashboxes' };
    }

    return true;
});

export default router;
