<?php
    include_once 'config.php';
    ini_set('error_log', 'error_in_google_config.log');

    $domain = 'integratortechaccount';
//    $domain = 'projapan';

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
//    $sheet_ID = '15QV9CeDMPNhsbRHyJlIXi95ZFOUQKyCCxyqIpLZl6MU';
    $sheet_ID = '1PqMkFgBF6G1UY5pJ1svKjUqKSUeeqX8Wh-wUU9wmkO8';
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
        while (file_exists('pause')) sleep(1);
    }

    // удаление паузы
    function deletePause() {
        while (file_exists('pause')) unlink('pause');
    }
