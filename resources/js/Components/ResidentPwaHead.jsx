import { useEffect } from 'react';
import { Head } from '@inertiajs/react';

export default function ResidentPwaHead() {
    useEffect(() => {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
        }
    }, []);

    return (
        <Head>
            <link rel="manifest" href="/manifest.webmanifest" />
            <meta name="theme-color" content="#0a2342" />
            <link rel="apple-touch-icon" href="/icon.svg" />
            <meta name="apple-mobile-web-app-capable" content="yes" />
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
            <meta name="apple-mobile-web-app-title" content="Resident" />
        </Head>
    );
}
