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

// проверка файлов старта и паузы на наличие багов (жизни файла более 15 минут)
if (file_exists('google_sheets/start')) {
    $file_start = filemtime('google_sheets/start');
    $file_start = date('Y-m-d H:i:s', $file_start);
    $diff = (new DateTime())->diff(new DateTime($file_start));

    if ($diff->i > 15) unlink('google_sheets/start');
}

if (file_exists('google_sheets/pause')) {
    $file_pause = filemtime('google_sheets/pause');
    $file_pause = date('Y-m-d H:i:s', $file_pause);
    $diff = (new DateTime())->diff(new DateTime($file_pause));

    if ($diff->i > 15) unlink('google_sheets/pause');
}

if (!file_exists('google_sheets/start')) {
    exec('php ' . __DIR__ . '/google_sheets/save_selection.php ' . 'projapan.amocrm.ru' . ' &> /dev/null &');
    //exec('php ' . __DIR__ . '/google_sheets/save_selection.php ' . 'integratortechaccount.amocrm.ru' . ' &> /dev/null &');
}