<?php
    use AmoCRM\Helpers\EntityTypesInterface;
    use AmoCRM\Exceptions\AmoCRMApiException;

    ini_set('error_log', 'error_in_templates.log');
    date_default_timezone_set('Europe/Moscow');
    header('Content-type: application/json;charset=utf8');
//    header('Content-type: text/html; charset=utf8');
	header('Access-Control-Allow-Origin: *');

    include_once __DIR__ . '/../../api_google/vendor/autoload.php';
    include_once 'config.php';

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

    $Config = new Config();

    if ($_POST['method'] == 'settings') {
        // echo 'Блок первичных настроек Авторизации виджета <br>';
        echo '<div id="settings_WidgetGoogle">';
        $path = $Config->Set_Path_From_Domain($_POST['domain']);
        $settings = $_POST['settings'];
        $settings['secret'] =  $_POST['secret'];
        $Config->SaveSettings($settings);

        if (($_POST['settings']['active'] == 'Y') || ($_POST['settings']['status'] == 'installed')) {
            echo $Config->Authorization();
            if ($Config->CheckToken()) include_once 'templates/advancedsettings.html';
        } else {
            $Config->deleteToken();
            echo 'Виджет еще не установлен. Установите. <br>';
        }

        echo '</div>';
        exit;
    }

    $Config->GetSettings($_POST['domain']);
    if ($Config->CheckToken()) $apiClient = $Config->getAMO_apiClient();
    else {
        if ($_POST['method'] == 'advancedsettings') echo $Config->Authorization();
        exit;
    }

    /* ##################################################################### */

    // список столбцов контейнеров с гугл таблицы
    if ($_POST['method'] == 'showSettingsJSON' && $Config->CheckToken()) {
        $settings = [];

        // поля сделок
        $customFieldsService = $apiClient->customFields(EntityTypesInterface::LEADS);
        try {
            $fields = $customFieldsService->get();
            usleep(20000);
        } catch (AmoCRMApiException $e) {}

        if ($fields->count() > 0) $fields_count = true;
        while ($fields_count) {
            foreach ($fields as $field) { $settings['fields_leads'][] = [$field->getId(), $field->getName()]; }

            if ($fields->getNextPageLink()) {
                try {
                    $fields = $customFieldsService->nextPage($fields);
                    usleep(20000);
                    $fields_count = true;
                } catch (AmoCRMApiException $e) {}
            } else $fields_count = false;
        }

        // поля контактов
        $customFieldsService = $apiClient->customFields(EntityTypesInterface::CONTACTS);
        try {
            $fields = $customFieldsService->get();
            usleep(20000);
        } catch (AmoCRMApiException $e) {}

        if ($fields->count() > 0) $fields_count = true;
        while ($fields_count) {
            foreach ($fields as $field) { $settings['fields_contacts'][] = [$field->getId(), $field->getName()]; }

            if ($fields->getNextPageLink()) {
                try {
                    $fields = $customFieldsService->nextPage($fields);
                    usleep(20000);
                    $fields_count = true;
                } catch (AmoCRMApiException $e) {}
            } else $fields_count = false;
        }

        // столбцы листов
        $container = [];
        $settings['container'] = [];
        $is_client = false;

        foreach ($response->getSheets() as $sheet) {
            $sheet_title = mb_strtolower($sheet->getProperties()->title);
            sleep(1);

            // Подбор
            if ($sheet_title === 'подбор') {
                $list = getValues($service, $sheet_ID, $sheet_title);

                foreach ($list['values'][0] as $key => $item) {
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                    $settings['selection'][] = $item;
                }
            // Ожидают отправку
            } else if ($sheet_title === 'ожидают отправку') {
                $list = getValues($service, $sheet_ID, $sheet_title);

                foreach ($list['values'][0] as $key => $item) {
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                    $settings['expect'][] = $item;
                }
            // остальные листы
            } else {
                $list = getValues($service, $sheet_ID, $sheet_title);

                // проверка на соответствие листа
                $is_list = false;
                foreach ($list['values'][0] as $key => $item) {
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                    if (mb_strtolower($item) === 'смена статуса всех сделок в листе') $is_list = true;
                }
                if (!$is_list) continue;

                foreach ($list['values'][0] as $key => $item) {
                    if ($key === 0 && $is_client === true) continue;
                    $item = trim(preg_replace('/\s+/', ' ', $item));
                    $container[] = $item;
                }
            }
        }

        foreach ($container as $item) {
            if (!in_array($item, $settings['container'])) $settings['container'][] = $item;
        }

        // если файлы с настройками существуют, получаем данные
        if (file_exists('selection.json')) {
            $file = file_get_contents('selection.json');
            $settings['selection_JSON'] = json_decode($file, true);
        }

        if (file_exists('expect.json')) {
            $file = file_get_contents('expect.json');
            $settings['expect_JSON'] = json_decode($file, true);
        }

        if (file_exists('container.json')) {
            $file = file_get_contents('container.json');
            $settings['container_JSON'] = json_decode($file, true);
        }

        print_r(json_encode($settings));
    }

    // сохраняем настройки в файлы JSON
    if ($_POST['method'] == 'saveSettingsJSON' && $Config->CheckToken()) {
        $selection = [];
        $expect = [];
        $container = [];

        if ($_POST['fields']['selection']) $fields_selection = $_POST['fields']['selection'];
        if ($_POST['fields']['expect']) $fields_expect = $_POST['fields']['expect'];
        if ($_POST['fields']['container']) $fields_container = $_POST['fields']['container'];

        if (!$fields_selection || count($fields_selection) === 0) {
            if (file_exists('selection.json')) unlink('selection.json');
        }
        else {
            foreach ($fields_selection as $item) { $selection[] = [$item['title'] => $item['code']]; }
            file_put_contents('selection.json', json_encode($selection));
        }

        if (!$fields_expect || count($fields_expect) === 0) {
            if (file_exists('expect.json')) unlink('expect.json');
        }
        else {
            foreach ($fields_expect as $item) { $expect[] = [$item['title'] => $item['code']]; }
            file_put_contents('expect.json', json_encode($expect));
        }

        if (!$fields_container || count($fields_container) === 0) {
            if (file_exists('container.json')) unlink('container.json');
        }
        else {
            foreach ($fields_container as $item) { $container[] = [$item['title'] => $item['code']]; }
            file_put_contents('container.json', json_encode($container));
        }
    }

    // сохраняем ссылку для ВК в базу
    if ($_POST['method'] == 'saveLinkVK' && $Config->CheckToken()) {
        $link = $_POST['link'];
        $contact_ID = $_POST['contact_ID'];
        if (!$link || !$contact_ID) return;

        include 'db_connect.php';

        $select = '
            SELECT *
            FROM google_sheets_vk
            WHERE contact_ID = "' . $_POST['contact_ID'] . '"
        ';

        $insert = '
            INSERT INTO google_sheets_vk 
            VALUES(
                null,
                "' . $_POST['contact_ID'] . '",
                "' . $_POST['link'] . '"
            )
        ';

        $result = $mysqli->query($select);
        if (!$result->num_rows) $mysqli->query($insert);
    }

    // create install.widget
    if ($_POST['method'] == 'widget_status' && $Config->CheckToken()) {
        if ($_POST['status'] === 'install') {
            if (file_exists('install.json')) return;
            file_put_contents('install.json', '');
        } else if ($_POST['status'] === 'destroy') {
            if (!file_exists('install.json')) return;
            unlink('install.json');
        }
    }

//    if ($_POST['method'] == 'testing' && $Config->CheckToken()) {
//        // code
//    }
