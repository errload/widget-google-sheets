<?php
    // если виджет не установлен, выходим
    if (!file_exists('install')) return;

    // если открыты настройки ждем пока завершат реквесты
    while (file_exists('start_settings')) sleep(5);

    // ставим паузу для других реквестов
    while (file_exists('start_hook')) sleep(5);
    file_put_contents('pause', '');
    file_put_contents('start_hook', '');
    sleep(1);

    use AmoCRM\Exceptions\AmoCRMApiException;

    include_once __DIR__ . '/../../api_google/vendor/autoload.php';
    include_once 'google_config.php';

    /* ###################################################################### */

    $pipeline_ID = 6149530; // воронка Депозит (integratortechaccount)
//    $pipeline_ID = 605716; // воронка Депозит

    $selection_title = []; // столбцы листа Подбор
    $selection_file = []; // поля из файла настроек виджета
    $row = []; // массив полей сделки и контакта
    $result_row = []; // результирующий массив для записи в таблицу
    $lead_key_ID = null; // номер столбца с ID сделки

    // данные по webhook
    if ($_POST['leads']['status'][0]['id']) {
        $lead_ID = $_POST['leads']['status'][0]['id'];
        $lead_link = $_POST['account']['_links']['self'] . '/leads/detail/' . $_POST['leads']['status'][0]['id'];

        // сделка по ID
        try {
            $lead_info = $apiClient->leads()->getOne((int) $lead_ID, ['contacts']);
            usleep(20000);
        } catch (AmoCRMApiException $e) {}

        // если воронка и статус не соответствуют, пропускаем
        if (!$lead_info || $lead_info->getPipelineId() !== $pipeline_ID) {
            // удаляем файлы паузы и хука
            if (file_exists('pause')) unlink('pause');
            if (file_exists('start_hook')) unlink('start_hook');
            return;
        }
        if (!$lead_info || $lead_info->getStatusId() !== 142) {
            // удаляем файлы паузы и хука
            if (file_exists('pause')) unlink('pause');
            if (file_exists('start_hook')) unlink('start_hook');
            return;
        }

        // лист Подбор
        foreach ($response->getSheets() as $sheet) {
            $sheet_title = $sheet->getProperties()->title;
            sleep(1);

            if (mb_strtolower($sheet_title) !== 'подбор') continue;
            $list = getValues($service, $sheet_ID, $sheet_title);

            // столбцы листа Подбор
            try {
                foreach ($list['values'][0] as $key => $value) {
                    $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));

                    // ID сделки и ссылка на сделку
                    if ($value === 'id сделки') {
                        $row['id сделки'] = $lead_ID;
                        $lead_key_ID = $key;
                    }
                    if ($value === 'ссылка на сделку') $row['ссылка на сделку'] = $lead_link;

                    // клиент всегда первый столбец
                    if ($key === 0) $selection_title[] = 'клиент';
                    else $selection_title[] = $value;
                }
            } catch (Google_Service_Exception $exception) {
                $reason = $exception->getErrors();
                if ($reason) continue;
            }

            // если такая сделка уже есть в таблице, новую не пишем
            $is_lead = false;
            try {
                foreach ($list['values'] as $key => $value) {
                    if ($value[$lead_key_ID] === $lead_ID) $is_lead = true;
                }
            } catch (Google_Service_Exception $exception) {
                $reason = $exception->getErrors();
                if ($reason) continue;
            }
            if ($is_lead) continue;

            // поля из файла настроек виджета
            if (file_exists('selection.json')) {
                $files = file_get_contents('selection.json');
                $files = json_decode($files, true);
                foreach ($files as $file) { foreach ($file as $key => $value) { $selection_file[$key] = $value; }}
            }

            // контакты
            if ($lead_info->getContacts()) {
                // основной контакт
                $contact_ID = $lead_info->getMainContact()->getId();
                try {
                    $contact = $apiClient->contacts()->getOne((int) $contact_ID);
                    usleep(20000);
                } catch (AmoCRMApiException $e) {}

                // поля контакта
                $customFields = $contact->getCustomFieldsValues();
                if ($customFields) {
                    // телефон
                    $phoneField = $customFields->getBy('fieldCode', 'PHONE');
                    if ($phoneField) {
                        $phone = $phoneField->getValues()[0]->getValue();
                        $phone = str_replace('+', '', $phone);
                        $row['телефон'] = $phone;
                    }

                    foreach ($selection_title as $title_key => $title_value) {
                        foreach ($selection_file as $file_key => $code) {
                            // значение поля Клиент
                            if ($title_key === 0 && mb_strtolower($file_key) === 'клиент') {
                                $field = $customFields->getBy('fieldId', (int) $code);
                                if (!$field) continue;
                                $field = $field->getValues()[0]->getValue();
                                $row['клиент'] = $field;
                            }

                            // значение поля Фактический адрес
                            if ($title_value === mb_strtolower($file_key) &&
                                mb_strtolower($file_key) === 'фактический адрес') {
                                $field = $customFields->getBy('fieldId', (int) $code);
                                if (!$field) continue;
                                $field = $field->getValues()[0]->getValue();
                                $row['фактический адрес'] = $field;
                            }
                        }
                    }
                } else {
                    $phone = '';
                    $row['клиент'] = $contact->getName();
                }

                // ссылка на вк
                include 'db_connect.php';
                $select = 'SELECT * FROM google_sheets_vk WHERE contact_ID = "' . $contact_ID . '"';
                $result = $mysqli->query($select);
                if ($result->num_rows) {
                    $result = $result->fetch_array();
                    $result = $result['link'];
                    $row['где общаемся'] = $result;
                } else if ($phone) $row['где общаемся'] = $phone;
            }

            // бюджет
            $row['бюджет'] = $lead_info->getPrice();

            // ответственный
            $manager_ID = $lead_info->getResponsibleUserId();
            try {
                $manager = $apiClient->users()->getOne((int) $manager_ID)->getName();
                usleep(20000);
            } catch (AmoCRMApiException $e) {}
            $row['менеджер'] = $manager;

            // поля сделки
            $customFields = $lead_info->getCustomFieldsValues();
            if ($customFields) {
                foreach ($selection_title as $title_key => $title_value) {
                    foreach ($selection_file as $file_key => $code) {
                        // значение поля Фактический адрес
                        if ($title_value === mb_strtolower($file_key)) {
                            $field = $customFields->getBy('fieldId', (int) $code);
                            if (!$field) continue;
                            $field = $field->getValues()[0]->getValue();
                            $row[$title_value] = $field;
                        }
                    }
                }
            }

            // результирующий массив
            foreach ($selection_title as $item) {
                if (!$row[$item] && $row[$item] !== 0) $result_row[] = '';
                foreach ($row as $key => $value) { if ($item === $key) $result_row[] = $value; }
            }

            // запись в таблицу
            $value_range = new Google_Service_Sheets_ValueRange();
            $value_range->setValues([$result_row]);
            $options = ['valueInputOption' => 'USER_ENTERED'];
            $service->spreadsheets_values->update(
                $sheet_ID, $sheet_title . '!A' . (count($list['values']) + 1), $value_range, $options
            );
            sleep(1);
        }
    }

    // удаляем файлы паузы и хука
    if (file_exists('pause')) unlink('pause');
    if (file_exists('start_hook')) unlink('start_hook');
