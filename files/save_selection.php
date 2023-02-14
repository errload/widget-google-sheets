<?php
    // если процесс уже запущен, выходим, иначе запускаем
    if (file_exists('google_sheets/start')) return;
    else file_put_contents('google_sheets/start', '');

    use AmoCRM\Exceptions\AmoCRMApiException;
    use AmoCRM\Filters\LeadsFilter;

    include_once __DIR__ . '/../../api_google/vendor/autoload.php';
    include_once 'google_config.php';

    /* ###################################################################### */

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

    foreach ($response->getSheets() as $sheet) {
        isPause();
        $sheet_title = $sheet->getProperties()->title;
        sleep(1);

        if (mb_strtolower($sheet_title) !== 'подбор') continue;

        // если запущены другие реквесты, ожидаем завершения
        isPause();
        $list = getValues($service, $sheet_ID, $sheet_title);

        // ID листа для удаления строк
        isPause();
        $list_ID = $sheet->getProperties()->getSheetId();
        sleep(1);

        // заголовки листа и номера столбцов сделки и цифры смены статуса
        foreach ($list['values'][0] as $key => $value) {
            $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
            if ($value === 'смена воронки и статуса') $number_ID = $key;
            if ($value === 'id сделки') $lead_ID = $key;
            $selection_title[] = $value;
        }

        // ID существующих сделок для запроса
        foreach ($list['values'] as $key => $value) { $IDs[] = $value[$lead_ID]; }
        try {
            $leads_IDs = $apiClient->leads()->get((new LeadsFilter())->setIds($IDs));
            usleep(20000);
        } catch (AmoCRMApiException $e) {}

        $IDs = [];
        if ($leads_IDs) foreach ($leads_IDs as $lead) { $IDs[] = $lead->getId(); }

        // проверяем построчно сделки кроме первой (заголовка)
        foreach ($list['values'] as $key => $value) {
            if ($key === 0) continue;
            if (!$value[$number_ID] || (int) $value[$number_ID] !== 1) continue;
            // если такой сделки не существует, пропускаем
            if (!in_array($value[$lead_ID], $IDs)) continue;

            // находим сделки по ID
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
    }

    // если по какой-то причине сделки не сменили статус и массив пуст, выходим
    if (!count($leads_edit)) {
        deleteStart();
        return;
    }

    // копируем сделку в лист ожидания
    foreach ($response->getSheets() as $lead) {
        isPause();
        $sheet_title = $sheet->getProperties()->title;
        sleep(1);

        if (mb_strtolower($sheet_title) !== 'ожидают отправку') continue;

        isPause();
        $list_expect = getValues($service, $sheet_ID, $sheet_title);

        // получаем заголовки листа Ожидают отправку
        foreach ($list_expect['values'][0] as $item) {
            $item = mb_strtolower(trim(preg_replace('/\s+/', ' ', $item)));
            $expect_title[] = $item;
        }

        // проверяем построчно сделки кроме первой (заголовка)
        foreach ($list['values'] as $key => $value) {
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
            isPause();
            $value_range = new Google_Service_Sheets_ValueRange();
            $value_range->setValues([$result]);
            $options = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->append(
                $sheet_ID, $sheet_title . '!A1:Z', $value_range, $options
            );
            sleep(1);
        }
    }

    // удаляем перенесенные строки
    for ($i = count($list['values']) - 1; $i > 0; $i--) {
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

        isPause();
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(['requests' => $requests]);
        $service->spreadsheets->batchUpdate($sheet_ID, $batchUpdateRequest);
        sleep(1);
        deleteStart();
    }

