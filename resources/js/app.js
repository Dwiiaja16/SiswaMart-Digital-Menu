import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'SiswaMart';

// Tangkap secara global jika terjadi sesi habis / response tidak valid dari server
router.on('invalid', (event) => {
    event.preventDefault();

    const status = event.detail.response.status;
    if (status === 401 || status === 405 || status === 419) {
        alert('Sesi login kamu telah berakhir atau sudah logout. Halaman akan dimuat ulang.');
        window.location.href = '/';
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#f97316', // Menggunakan warna tema SiswaMart (Orange)
    },
});