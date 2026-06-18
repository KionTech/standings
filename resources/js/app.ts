import { initializeTheme } from '@/composables/useAppearance';
import { createInertiaApp } from '@inertiajs/vue3';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Standings';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
