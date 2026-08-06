<?php
/**
 * SPA refactor: strip the identical "page transition" boilerplate (which does a full
 * window.location.href reload) from every staff/admin page JS file, and wrap the
 * remaining page logic in a reusable init function registered on window.AppPage.
 *
 * The router (in sidemenu.js) can then invoke the correct init after swapping content.
 */

$boilerplateSource = 'resources/js/staff_js/staff_dashboard.js';
$src = file_get_contents($boilerplateSource);
if ($src === false) { fwrite(STDERR, "Cannot read $boilerplateSource\n"); exit(1); }

// Extract the identical boilerplate block: everything between the opener line and
// the final "});" line (which closes the DOMContentLoaded callback).
$block = preg_replace('/^[^\n]*\n/', '', $src);      // drop first line (opener)
$block = rtrim($block);
$block = preg_replace('/\}\);\s*$/', '', $block);     // drop final "});" closer
$block = rtrim($block);
$blockLf = str_replace("\r\n", "\n", $block);

$files = [
    'resources/js/staff_js/staff_check_ins.js'              => 'staff_check_ins',
    'resources/js/staff_js/staff_dashboard.js'              => 'staff_dashboard',
    'resources/js/staff_js/staff_occupancy_monitor.js'      => 'staff_occupancy_monitor',
    'resources/js/staff_js/staff_records.js'                => 'staff_records',
    'resources/js/staff_js/staff_reports.js'                => 'staff_reports',
    'resources/js/staff_js/staff_reservations.js'           => 'staff_reservations',
    'resources/js/staff_js/staff_settings.js'               => 'staff_settings',
    'resources/js/admin_js/admin_amenitiesmanagement.js'    => 'admin_amenitiesmanagement',
    'resources/js/admin_js/admin_dashboard.js'              => 'admin_dashboard',
    'resources/js/admin_js/admin_reports.js'                => 'admin_reports',
    'resources/js/admin_js/admin_settings.js'               => 'admin_settings',
    'resources/js/admin_js/admin_usermanagement.js'         => 'admin_usermanagement',
];

$openerPattern = "/document\\.addEventListener\\(\\s*'DOMContentLoaded'\\s*,\\s*(?:function\\s*\\(\\s*\\)|\\(\\s*\\)\\s*=>)\\s*\\{/";

foreach ($files as $file => $key) {
    $content = file_get_contents($file);
    if ($content === false) { fwrite(STDERR, "Cannot read $file\n"); continue; }

    $original = $content;

    // 1) Remove the boilerplate block (byte-identical; fall back to LF-normalized).
    $count = 0;
    $content = str_replace($block, '', $content, $count);
    if ($count === 0) {
        $content = str_replace($blockLf, '', $content, $count);
        if ($count === 0) {
            fwrite(STDERR, "WARN: boilerplate not found in $file — skipping\n");
            continue;
        }
    }

    // 2) Replace the DOMContentLoaded opener with a registration + init function.
    $replacement = "window.AppPage = window.AppPage || {};\nwindow.AppPage['$key'] = function () {";
    $content = preg_replace($openerPattern, $replacement, $content, 1);
    if ($content === null || strpos($content, "AppPage['$key']") === false) {
        fwrite(STDERR, "WARN: opener pattern not matched in $file\n");
        continue;
    }

    // 3) Replace the final "});" closer with the function close + DOMContentLoaded dispatch.
    $content = rtrim($content);
    $closer = "};\n\ndocument.addEventListener('DOMContentLoaded', () => window.AppPage['$key']());";
    $content = preg_replace('/\}\);\s*$/', $closer, $content, 1);
    if ($content === null) { fwrite(STDERR, "WARN: closer pattern failed in $file\n"); continue; }

    if ($content === $original) {
        fwrite(STDERR, "WARN: no change produced for $file\n");
        continue;
    }

    file_put_contents($file, $content);
    echo "OK   $file -> init '$key'\n";
}

echo "Done.\n";
