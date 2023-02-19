<?php
    // если виджет не установлен, выходим
    if (!file_exists('install')) return;
    // запускаем файлы проверки
    file_put_contents('start', '');

    use AmoCRM\Exceptions\AmoCRMApiException;
    use AmoCRM\Filters\LeadsFilter;

    include_once __DIR__ . '/../../api_google/vendor/autoload.php';
    include_once 'google_config.php';
    ini_set('error_log', 'error_in_save_selection.log');

    /* ###################################################################### */

    // переход к следующему шагу
    function goToStep() {
        exec('php ' . __DIR__ . '/save_expect.php ' . $domain . '.amocrm.ru' . ' &> /dev/null &');
        exit;
    }

    $pipeline_ID = 6001285; // воронка Логистика (integratortechaccount)
    $status_ID = 52185676; // статус Заполнение контейнера (integratortechaccount)
    $user_ID = 8981790; // ID пользователя (integratortechaccount)

//    $pipeline_ID = 606067; // воронка Логистика
//    $status_ID = 14928961; // статус Заполнение контейнера
//    $user_ID = 1177374; // ID пользователя

    $IDs = []; // ID существующих сделок для запроса
    $lead_ID = null; // номер столбца с ID сделкой
    $number_ID = null; // номер столбца с цифрой
    $leads_edit = []; // ID сделок для переноса в другой лист
    $selection_title = []; // столбцы листа Подбор
    $expect_title = []; // столбцы листа Ожидают отправку
    $list = []; // массив строк листа
    $list_ID = null; // ID листа для удаления строк таблицы

    // получаем лист Подбор
    isPause();
    $list = getValues($service, $sheet_ID, 'подбор');

    // ID листа для удаления строк
    foreach ($response->getSheets() as $sheet) {
        isPause();

        try {
            $sheet_title = $sheet->getProperties()->title;
            sleep(1);
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }

        if (mb_strtolower($sheet_title) !== 'подбор') continue;
        isPause();

        try {
            $list_ID = $sheet->getProperties()->getSheetId();
            sleep(1);
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }

        break;
    }

    if (!$list_ID) goToStep();

    // заголовки листа, номера столбцов сделки и цифры смены статуса
    isPause();
    try {
        foreach ($list['values'][0] as $key => $value) {
            $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
            if ($value === 'смена воронки и статуса') $number_ID = $key;
            if ($value === 'id сделки') $lead_ID = $key;
            $selection_title[] = $value;
        }
    } catch (Google_Service_Exception $exception) {
        $reason = $exception->getErrors();
        if ($reason) goToStep();
    }

    // ID существующих сделок для запроса
    isPause();
    try {
        foreach ($list['values'] as $key => $value) {
            $IDs[] = $value[$lead_ID];
        }
    } catch (Google_Service_Exception $exception) {
        $reason = $exception->getErrors();
        if ($reason) goToStep();
    }

    try {
        $leads_IDs = $apiClient->leads()->get((new LeadsFilter())->setIds($IDs));
        usleep(20000);
    } catch (AmoCRMApiException $e) {}

    if (is_null($leads_IDs)) goToStep();
    $IDs = [];

    foreach ($leads_IDs as $lead) {
        $IDs[] = $lead->getId();
    }

    // проверяем построчно сделки кроме первой (заголовка)
    try {
        foreach ($list['values'] as $key => $value) {
            isPause();

            if ($key === 0) continue;
            if (!$value[$number_ID] || (int) $value[$number_ID] !== 1) continue;
            // если такой сделки не существует, пропускаем
            if (!in_array($value[$lead_ID], $IDs)) continue;

            // находим сделку по ID
            try {
                $lead_info = $apiClient->leads()->getOne($value[$lead_ID]);
                usleep(20000);
            } catch (AmoCRMApiException $e) {}

            // меняем ответственного и статус
            $lead_info->setResponsibleUserId($user_ID);
            $lead_info->setPipelineId($pipeline_ID);
            $lead_info->setStatusId($status_ID);

            // обновляем сделки
            try {
                $apiClient->leads()->updateOne($lead_info);
                usleep(20000);
                $leads_edit[] = $value[$lead_ID];
            } catch (AmoCRMApiException $e) {}
        }
    } catch (Google_Service_Exception $exception) {
        $reason = $exception->getErrors();
        if ($reason) goToStep();
    }

    // если сделки не сменили статус и массив пуст, выходим
    if (!count($leads_edit)) goToStep();

    // копируем сделку в лист ожидания
    isPause();
    $list_expect = getValues($service, $sheet_ID, 'ожидают отправку');
    $count = count($list_expect['values']) + 1;

    // получаем заголовки листа Ожидают отправку
    isPause();
    try {
        foreach ($list_expect['values'][0] as $item) {
            $item = mb_strtolower(trim(preg_replace('/\s+/', ' ', $item)));
            $expect_title[] = $item;
        }
    } catch (Google_Service_Exception $exception) {
        $reason = $exception->getErrors();
        if ($reason) goToStep();
    }

    // проверяем построчно сделки кроме первой (заголовка)
    try {
        foreach ($list['values'] as $key => $value) {
            isPause();
            if ($key === 0) continue;

            // если такая сделка не имела цифру 1 и не изменила статус, пропускаем
            if (!in_array($value[$lead_ID], $leads_edit)) continue;

            // создаем результирующий массив для добавления в лист Ожидают отправку
            $result = [];

            foreach ($expect_title as $item) {
                $is_write = false;

                if ($item === 'смена воронки и статуса') continue;

                foreach ($selection_title as $title_key => $title_value) {
                    if ($item !== $title_value) continue;

                    $result[] = $value[$title_key];
                    $is_write = true;
                }

                // если ни один из столбцов листа Подбор не найден, пишем пустое значение
                if (!$is_write) $result[] = '';
            }

            // добавляем новую строку в лист Ожидают отправку
            $value_range = new Google_Service_Sheets_ValueRange();
            $value_range->setValues([$result]);
            $options = ['valueInputOption' => 'USER_ENTERED'];

            try {
                $service->spreadsheets_values->append(
                    $sheet_ID, 'ожидают отправку!A' . $count, $value_range, $options
                );
                sleep(1);
                $count++;
            } catch (Google_Service_Exception $exception) {
                $reason = $exception->getErrors();
                if ($reason) goToStep();
            }

        }
    } catch (Google_Service_Exception $exception) {
        $reason = $exception->getErrors();
        if ($reason) goToStep();
    }

    // удаляем скопированные строки с листа подбор
    try {
        for ($i = count($list['values']) - 1; $i > 0; $i--) {
            isPause();

            // если нет поля для цифры или она не стоит, пропускаем
            if (!$list['values'][$i][$number_ID] || (int) $list['values'][$i][$number_ID] !== 1) continue;
            // если сделка не поменяла статус, не переносим
            if (!in_array($list['values'][$i][$lead_ID], $leads_edit)) continue;

            $requests = [
                new Google_Service_Sheets_Request([
                    'deleteRange' => [
                        'range' => [
                            'sheetId' => $list_ID,
                            'startRowIndex' => $i,
                            'endRowIndex' => $i + 1,
                        ],
                        'shiftDimension' => 'ROWS'
                    ]
                ])
            ];

            try {
                $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(['requests' => $requests]);
                $service->spreadsheets->batchUpdate($sheet_ID, $batchUpdateRequest);
                sleep(1);
            } catch (Google_Service_Exception $exception) {
                $reason = $exception->getErrors();
                if ($reason) goToStep();
            }
        }
    } catch (Google_Service_Exception $exception) {
        $reason = $exception->getErrors();
        if ($reason) goToStep();
    }

    goToStep();
