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

                // Shared dashboard components
                'resources/components/css_js/header.css',
                'resources/components/css_js/header.js',
                'resources/components/css_js/sidemenu.css',
                'resources/components/css_js/sidemenu.js',
                'resources/components/css_js/admin_sidemenu.css',
                'resources/components/css_js/staff_sidemenu.css',

                // Admin pages
                'resources/css/admin_css/admin_dashboard.css',
                'resources/js/admin_js/admin_dashboard.js',
                'resources/css/admin_css/admin_amenitiesmanagement.css',
                'resources/js/admin_js/admin_amenitiesmanagement.js',
                'resources/css/admin_css/admin_reports.css',
                'resources/js/admin_js/admin_reports.js',
                'resources/css/admin_css/admin_settings.css',
                'resources/js/admin_js/admin_settings.js',
                'resources/css/admin_css/admin_usermanagement.css',
                'resources/js/admin_js/admin_usermanagement.js',

                // Staff pages
                'resources/css/staff_css/staff_theme.css',
                'resources/css/staff_css/staff_dashboard.css',
                'resources/js/staff_js/staff_dashboard.js',
                'resources/css/staff_css/staff_check_ins.css',
                'resources/js/staff_js/staff_check_ins.js',
                'resources/css/staff_css/staff_occupancy_monitor.css',
                'resources/js/staff_js/staff_occupancy_monitor.js',
                'resources/css/staff_css/staff_records.css',
                'resources/js/staff_js/staff_records.js',
                'resources/css/staff_css/staff_reports.css',
                'resources/js/staff_js/staff_reports.js',
                'resources/css/staff_css/staff_reservations.css',
                'resources/js/staff_js/staff_reservations.js',
                'resources/css/staff_css/staff_settings.css',
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
