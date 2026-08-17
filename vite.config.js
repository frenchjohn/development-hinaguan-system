import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Public pages
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/homepage.css',
                'resources/js/homepage.js',
                'resources/css/amenities.css',
                'resources/js/amenities.js',
                'resources/css/reservationpage.css',
                'resources/js/reservationpage.js',
                'resources/css/loginpage.css',
                'resources/js/loginpage.js',
                'resources/css/chatbot.css',
                'resources/js/guest_chatbot.js',
                'resources/js/staff_chatbot.js',
                'resources/js/admin_chatbot.js',

                // Shared dashboard components
                'resources/components/css_js/header.css',
                'resources/components/css_js/header.js',
                'resources/components/css_js/sidemenu.css',
                'resources/components/css_js/sidemenu.js',
                'resources/components/css_js/staff_sidemenu.css',

                // Admin pages
                'resources/css/admin_css/admin_shared.css',
                'resources/js/admin_js/admin_dashboard.js',
                'resources/js/admin_js/admin_amenitiesmanagement.js',
                'resources/js/admin_js/admin_reports.js',
                'resources/js/admin_js/admin_settings.js',
                'resources/js/admin_js/admin_usermanagement.js',

                // Staff pages
                'resources/css/staff_css/staff_shared.css',
                'resources/js/staff_js/staff_dashboard.js',
                'resources/js/staff_js/staff_check_ins.js',
                'resources/js/staff_js/staff_occupancy_monitor.js',
                'resources/js/staff_js/staff_records.js',
                'resources/js/staff_js/staff_reports.js',
                'resources/js/staff_js/staff_reservations.js',
                'resources/js/staff_js/staff_settings.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
