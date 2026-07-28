import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin'; // <-- This is the missing import
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx', // Make sure this matches your entry file (app.jsx or app.tsx)
            refresh: true,
        }),
        react(),
    ],
});