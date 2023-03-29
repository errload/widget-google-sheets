<?php
    use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseEnumCodeCustomFieldValueModel;
    use AmoCRM\Helpers\EntityTypesInterface;
    use AmoCRM\Exceptions\AmoCRMApiException;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
    use AmoCRM\Collections\CustomFieldsValuesCollection;
    use AmoCRM\Filters\LeadsFilter;

    include_once __DIR__ . '/../../api_google/vendor/autoload.php';
    include_once 'google_config.php';
    ini_set('error_log', 'error_in_save_container.log');

    /* ###################################################################### */

    // остановка скрипта в случае ошибки
    function deleteStart() {
        if (file_exists('start')) unlink('start');
    }

//    $pipeline_ID = 6001285; // воронка Логистика (integratortechaccount)
    $pipeline_ID = 606067; // воронка Логистика

    $container_file = []; // поля с файла
    $fields = []; // поля сделок
    $fields_contacts = []; // поля контактов

    // берем значения полей из файла настроек виджета
    if (file_exists('container.json')) {
        $files = file_get_contents('container.json');
        $files = json_decode($files, true);

        foreach ($files as $file) {
            foreach ($file as $key => $value) {
                $container_file[$key] = $value;
            }
        }
    }

    // перебираем поля сделок для определения типа поля
    isPause();
    $customFieldsLeads = $apiClient->customFields(EntityTypesInterface::LEADS);
    try {
        $customFields = $customFieldsLeads->get();
        usleep(20000);
    } catch (AmoCRMApiException $e) {
        deleteStart();
    }

    if ($customFields) $fields_count = true;

    while ($fields_count) {
        if ($customFields) {
            foreach ($customFields as $customField) {
                $class = explode('\\', get_class($customField));
                $class = end($class);
                $fields[] = [$customField->getId(), $class];
            }
        }

        if ($customFields->getNextPageLink()) {
            try {
                $customFields = $customFieldsLeads->nextPage($customFields);
                usleep(20000);
                $fields_count = true;
            } catch (AmoCRMApiException $e) {
                deleteStart();
            }
        } else $fields_count = false;
    }

    // перебираем поля контактов для определения типа поля
    isPause();
    $customFieldsContacts = $apiClient->customFields(EntityTypesInterface::CONTACTS);
    try {
        $customFields = $customFieldsContacts->get();
        usleep(20000);
    } catch (AmoCRMApiException $e) {
        deleteStart();
    }

    if ($customFields) {
        foreach ($customFields as $customField) {
            $class = explode('\\', get_class($customField));
            $class = end($class);
            $fields_contacts[] = [$customField->getId(), $class];
        }
    }

    // перебираем листы таблицы для поиска обновляемых контейнеров
    foreach ($response->getSheets() as $sheet) {
        if (!file_exists('start')) continue;

        $IDs = []; // ID существующих сделок для запроса
        $container_number_key = null; // номер столбца смены статуса и воронки
        $container_lead_key = null; // номер столбца ID сделки
        $container_title = []; // массив заголовков таблицы
        $container_table = []; // массив строк таблицы
        $leads = []; // сделки с полями
        $leads_edit = []; // ID сделок для изменения цифры
        $status_ID = null;

        isPause();
        $sheet_title = $sheet->getProperties()->title;
        sleep(1);

        if (mb_strtolower($sheet_title) === 'подбор' ||
            mb_strtolower($sheet_title) === 'ожидают отправку') continue;

        isPause();
        $list = getValues($service, $sheet_ID, $sheet_title);
        // если нет первой строки (заголовка), пропускаем
        if (!$list['values'][0]) continue;

        // если в заголовках нет столбца смены статуса, пропускаем
        $is_list = false;
        isPause();

        try {
            foreach ($list['values'][0] as $key => $item) {
                $item = trim(preg_replace('/\s+/', ' ', $item));
                if (mb_strtolower($item) === 'смена статуса всех сделок в листе') $is_list = true;
            }
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }

        if (!$is_list) continue;

        // находим ID статуса по названию листа
        isPause();
        try {
            $pipelines = $apiClient->pipelines()->getOne($pipeline_ID)->getStatuses();
            usleep(20000);
        } catch (AmoCRMApiException $e) {}

        if (!$pipelines) continue;

        foreach ($pipelines as $status) {
            if (mb_strtolower($status->getName()) === mb_strtolower($sheet_title)) {
                $status_ID = $status->getId();
            }
        }

        if (!$status_ID) continue;

        // определяем номер столбца с цифрой изменения сделки и ID сделки
        isPause();
        try {
            foreach ($list['values'][0] as $key => $value) {
                $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));

                if ($value === 'id сделки') $container_lead_key = $key;
                if ($value === 'смена статуса всех сделок в листе') $container_number_key = $key;

                $container_title[] = $value;
            }
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }

        // если цифра не стоит, пропускаем лист
        if ($list['values'][1][$container_number_key] && (int) $list['values'][1][$container_number_key] !== 1) continue;

        // получаем данные с цифрой копирования в сделку и ID сделок
        isPause();
        try {
            foreach ($list['values'] as $key => $value) {
                if ($key === 0) continue;

                $container_table[] = $value;
                if ($container_lead_key) $IDs[] = $value[$container_lead_key];
            }
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }

        // если нет подходящих записей, пропускаем
        if (!count($container_table)) continue;

        isPause();
        try {
            $leads_IDs = $apiClient->leads()->get((new LeadsFilter())->setIds($IDs));
            usleep(20000);
        } catch (AmoCRMApiException $e) {}

        if (is_null($leads_IDs)) continue;

        foreach ($leads_IDs as $lead) {
            $IDs[] = $lead->getId();
        }

        // перебор полученных строк с таблицы
        foreach ($container_table as $rows) {
            $lead_ID = $rows[$container_lead_key];
            if (!$lead_ID) continue;

            // перебор столбцов таблицы
            foreach ($container_title as $t_key => $title) {
                // пишем отдельно бюджет и телефон
                if ($title === 'телефон') $leads[$lead_ID]['телефон'] = $rows[$t_key];
                else if ($title === 'бюджет') $leads[$lead_ID]['бюджет'] = $rows[$t_key];
                else {
                    // если настроек нет, пропускаем
                    if (!$container_file || count($container_file) === 0) continue;

                    // иначе пишем ID и значения остальных полей
                    foreach ($container_file as $f_key => $code) {
                        if (mb_strtolower($f_key) !== $title) continue;
                        $leads[$lead_ID][$code] = $rows[$t_key];
                    }
                }
            }
        }

        // перебираем полученные сделки и меняем в них значения полей
        if (!count($leads)) continue;

        foreach ($leads as $ID => $lead) {
            isPause();

            // если такой сделки не существует, пропускаем
            if (!in_array($ID, $IDs)) continue;

            // находим сделку
            try {
                $lead_info = $apiClient->leads()->getOne((int) $ID, ['contacts']);
                usleep(20000);
            } catch (AmoCRMApiException $e) {}

            if (!$lead_info) continue;

            // коллекция полей сделки
            $customFields = null;
            $customFields = $lead_info->getCustomFieldsValues();
            if (!$customFields) {
                $customFields = (new CustomFieldsValuesCollection());
                $lead_info->setCustomFieldsValues($customFields);
            }

            // перебираем полученные значения из таблицы и пишем в сделку
            foreach ($lead as $key => $value) {
                foreach ($fields as $field) {
                    if ($key !== $field[0]) continue;
                    $Config->SetFieldValue($customFields, $field[1], (int) $key, $value);
                }
            }

            // меняем бюджет
            $lead['бюджет'] ? $lead_info->setPrice($lead['бюджет']) : $lead_info->setPrice(0);

            // меняем статус сделки
            $lead_info->setPipelineId($pipeline_ID);
            $lead_info->setStatusId($status_ID);

            // сохраняем сделку
            try {
                $apiClient->leads()->updateOne($lead_info);
                usleep(20000);
                $leads_edit[] = $ID;
            } catch (AmoCRMApiException $e) {}

            // меняем контакт
            $contacts = $lead_info->getContacts();
            if ($contacts) {
                $contact_ID = $contacts->getBy('isMain', true)->getId();

                try {
                    $contact = $apiClient->contacts()->getOne((int) $contact_ID);
                    usleep(20000);
                } catch (AmoCRMApiException $e) {}

                if (!$contact) continue;

                // коллекция полей контакта
                $customFields = null;
                $customFields = $contact->getCustomFieldsValues();
                if (!$customFields) {
                    $customFields = (new CustomFieldsValuesCollection());
                    $contact->setCustomFieldsValues($customFields);
                }

                // телефон
                $phone = $customFields->getBy('fieldCode', 'PHONE');

                if (empty($phone)) {
                    $phone = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
                    $customFields->add($phone);
                }

                if (!$lead['телефон']) $phone->setValues((new NullCustomFieldValueCollection()));
                else $phone->setValues(
                    (new MultitextCustomFieldValueCollection())
                        ->add((new BaseEnumCodeCustomFieldValueModel())
                            ->setValue($lead['телефон']))
                );

                // ФИО и фактический адрес
                foreach ($fields_contacts as $field) {
                    foreach ($lead as $key => $value) {
                        if ($field[0] !== $key) continue;
                        $Config->SetFieldValue($customFields, $field[1], (int) $key, $value);
                    }
                }

                // обновляем контакт
                try {
                    $apiClient->contacts()->updateOne($contact);
                    usleep(20000);
                } catch (AmoCRMApiException $e) {}
            }
        }

        // алфавит таблицы для подстановки столбца
        $google_AZ = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
            'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG'
        ];

        // обновляем строки на значение с цифрой 2
        $result = [];
        isPause();

        try {
            foreach ($list['values'] as $key => $row) {
                // если не первая строка и смена статуса с цифрой 1, меняем на 2, иначе просто перезаписываем
                if ($key === 1 && (int) $row[$container_number_key] === 1) {
                    $result[] = 2;
                } else if ($row[$container_number_key]) $result[] = $row[$container_number_key];
                else $result[] = 'null';
            }
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }

        $result_row = [];
        foreach ($result as $item) {
            if ($item === 'null') $result_row[] = '';
            else $result_row[] = $item;
        }

        isPause();
        $value_range = new Google_Service_Sheets_ValueRange();
        $value_range->setMajorDimension('COLUMNS');
        $value_range->setValues([$result_row]);
        $options = ['valueInputOption' => 'USER_ENTERED'];

        try {
            $service->spreadsheets_values->update(
                $sheet_ID, $sheet_title . '!' . $google_AZ[$container_number_key] . '1:Z',
                $value_range, $options
            );
            sleep(1);
        } catch (Google_Service_Exception $exception) {
            $reason = $exception->getErrors();
            if ($reason) continue;
        }
    }

    if (file_exists('start')) unlink('start');
    exit;