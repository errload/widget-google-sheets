<?php

// google sheets
if (file_exists('error_log')) {
    if (filesize('error_log') > 10000000)
        unlink('error_log');
}

if (file_exists('google_sheets/error_log')) {
    if (filesize('google_sheets/error_log') > 10000000)
        unlink('google_sheets/error_log');
}

if (!file_exists('google_sheets/start')) {
    exec('php ' . __DIR__ . '/google_sheets/save_selection.php ' . 'integratortechaccount.amocrm.ru' . ' &> /dev/null &');
}