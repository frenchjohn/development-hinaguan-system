<?php

/**
 * Removes the `<div class="page-transition-overlay" id="pageTransitionOverlay">`
 * skeleton block from every staff/admin Blade view, since instant navigation
 * no longer uses it.
 */

$views = array_merge(
    glob(__DIR__ . '/../resources/views/staff/*.blade.php'),
    glob(__DIR__ . '/../resources/views/admin/*.blade.php')
);

foreach ($views as $file) {
    $lines = file($file);
    $out = [];
    $removing = false;
    $depth = 0;
    $removed = false;

    foreach ($lines as $line) {
        if (!$removing && strpos($line, 'id="pageTransitionOverlay"') !== false) {
            $removing = true;
            $removed = true;
            $depth = substr_count($line, '<div') - substr_count($line, '</div>');
            continue;
        }

        if ($removing) {
            $depth += substr_count($line, '<div') - substr_count($line, '</div>');
            if ($depth <= 0) {
                $removing = false;
            }
            continue;
        }

        // Drop blank lines that immediately preceded the removed overlay.
        if ($removed && trim($line) === '' && isset($out[count($out) - 1]) && trim(end($out)) === '') {
            continue;
        }

        $out[] = $line;
    }

    if ($removed) {
        file_put_contents($file, implode('', $out));
        echo "REMOVED: $file\n";
    } else {
        echo "SKIP   : $file (no overlay)\n";
    }
}
