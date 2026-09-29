import './bootstrap';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AdminLayout from './Layouts/AdminLayout';
import ResidentLayout from './Layouts/ResidentLayout';
import SecretaryLayout from './Layouts/SecretaryLayout';
import VAWCLayout from './Layouts/VAWCLayout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const moduleLayouts = {
    Admin: AdminLayout,
    Resident: ResidentLayout,
    Secretary: SecretaryLayout,
    VAWC: VAWCLayout,
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    layout: (name) => moduleLayouts[name.split('/')[0]] ?? null,
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});