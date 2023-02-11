<?php

    include_once 'config.php';

    $Config = new Config();
//    $Config->GetSettings('integratortechaccount.amocrm.ru');
    $Config->GetSettings('projapan.amocrm.ru');
    if (!$Config->CheckToken()) return;
    $apiClient = $Config->getAMO_apiClient();

    $google_account_key = __DIR__ . '/service_key.json';
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $google_account_key);

    $google = new Google_Client();
    $google->useApplicationDefaultCredentials();
    $google->addScope('https://www.googleapis.com/auth/spreadsheets');
    $service = new Google_Service_Sheets($google);
    $sheet_ID = '1pMraZ5_whYqLIWOo8BBJb-Fhfk8rAxwfv7T-GZ2Jx_g';
//    $sheet_ID = '15QV9CeDMPNhsbRHyJlIXi95ZFOUQKyCCxyqIpLZl6MU';
//    $sheet_ID = '1iwErd_uUFCVzIieSuMp87uqmy11VpP-ST9vqBFMn5wo';
    $response = $service->spreadsheets->get($sheet_ID);
    sleep(1);
