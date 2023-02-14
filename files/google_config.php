<?php

    include_once 'config.php';

    $Config = new Config();
    $Config->GetSettings('integratortechaccount.amocrm.ru');
//    $Config->GetSettings('projapan.amocrm.ru');
    if (!$Config->CheckToken()) return;
    $apiClient = $Config->getAMO_apiClient();

    $google_account_key = __DIR__ . '/service_key.json';
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $google_account_key);

    $google = new Google_Client();
    $google->useApplicationDefaultCredentials();
    $google->addScope('https://www.googleapis.com/auth/spreadsheets');
    $service = new Google_Service_Sheets($google);
//    $sheet_ID = '1pMraZ5_whYqLIWOo8BBJb-Fhfk8rAxwfv7T-GZ2Jx_g';
//    $sheet_ID = '15QV9CeDMPNhsbRHyJlIXi95ZFOUQKyCCxyqIpLZl6MU';
    $sheet_ID = '1iwErd_uUFCVzIieSuMp87uqmy11VpP-ST9vqBFMn5wo';
    $response = $service->spreadsheets->get($sheet_ID);
    sleep(1);

    // получение данных таблицы
    function getValues($service, $sheet_ID, $sheet_title) {
        try {
            $list = $service->spreadsheets_values->get($sheet_ID, $sheet_title);
            sleep(1);
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors()[0]['reason'];
            if ($reason === 'rateLimitExceeded') {
                sleep(5);
                return getValues($service, $sheet_ID, $sheet_title);
            }
        }

        return $list;
    }

    // проверка паузы
    function isPause() {
        while (file_exists('google_sheets/pause')) sleep(5);
    }

    // обнуление файлов
    function nullStart() {
        if (file_exists('google_sheets/step2')) unlink('google_sheets/step2');
        if (file_exists('google_sheets/step3')) unlink('google_sheets/step3');
    }
