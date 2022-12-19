<?php
    use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseEnumCodeCustomFieldValueModel;
    use AmoCRM\Helpers\EntityTypesInterface;
    use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
    use AmoCRM\Collections\CustomFieldsValuesCollection;
    use AmoCRM\Filters\LeadsFilter;

    include_once __DIR__ . '/../../api_google/vendor/autoload.php';
    include_once 'config.php';
    include 'google_config.php';

    /* ###################################################################### */

    $IDs = []; // ID существующих сделок для запроса
    $expect_file = []; // поля с файла
    $expect_number_key = null; // номер столбца смены статуса и воронки
    $expect_lead_key = null; // номер столбца ID сделки
    $expect_title = []; // массив заголовков таблицы
    $expect_table = []; // массив строк таблицы
    $leads = []; // сделки с полями
    $fields = []; // поля сделок
    $fields_contacts = []; // поля контактов
    $leads_edit = []; // ID сделок для изменения цифры

    foreach ($response->getSheets() as $sheet) {
        $sheet_properties = $sheet->getProperties();
        if (mb_strtolower($sheet_properties->title) !== 'ожидают отправку') continue;
        $list = $service->spreadsheets_values->get($sheet_ID, $sheet_properties->title);

        // берем значения полей из файла настроек виджета
        if (file_exists('google_sheets/expect.json')) {
            $files = file_get_contents('google_sheets/expect.json');
            $files = json_decode($files, true);
            foreach ($files as $file) { foreach ($file as $key => $value) { $expect_file[$key] = $value; }}
        }

        // определяем номер столбца с цифрой изменения сделки и ID сделки
        foreach ($list['values'][0] as $key => $value) {
            $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));

            if ($value === 'id сделки') $expect_lead_key = $key;
            if ($value === 'смена воронки и статуса') $expect_number_key = $key;

            $expect_title[] = $value;
        }

        // ID существующих сделок для запроса
        foreach ($list['values'] as $key => $value) { $IDs[] = $value[$expect_lead_key]; }
        try {
            $leads_IDs = $apiClient->leads()->get((new LeadsFilter())->setIds($IDs));
            usleep(200);
        } catch (AmoCRMException $e) {}
        $IDs = [];
        foreach ($leads_IDs as $lead) { $IDs[] = $lead->getId(); }

        // получаем данные с цифрой копирования в сделку
        foreach ($list['values'] as $key => $value) {
            if ($key === 0) continue;
            if (!$value[$expect_number_key] || (int) $value[$expect_number_key] !== 1) continue;

            $expect_table[] = $value;
        }

        // если нет подходящих записей, выходим
        if (!count($expect_table)) return;

        // перебор полученных строк с таблицы
        foreach ($expect_table as $rows) {
            $lead_ID = $rows[$expect_lead_key];
            if (!$lead_ID) continue;

            // перебор столбцов таблицы
            foreach ($expect_title as $t_key => $title) {
                // пишем отдельно бюджет и телефон
                if ($title === 'телефон') $leads[$lead_ID]['телефон'] = $rows[$t_key];
                else if ($title === 'бюджет') $leads[$lead_ID]['бюджет'] = $rows[$t_key];
                else {
                    // если настроек нет, пропускаем
                    if (!$expect_file || count($expect_file) === 0) continue;

                    // иначе пишем ID и значения остальных полей
                    foreach ($expect_file as $f_key => $code) {
                        if (mb_strtolower($f_key) !== $title) continue;
                        $leads[$lead_ID][$code] = $rows[$t_key];
                    }
                }
            }
        }

        // перебираем поля сделок для определения типа поля
        $customFieldsLeads = $apiClient->customFields(EntityTypesInterface::LEADS);
        try {
            $customFields = $customFieldsLeads->get();
            usleep(200);
        } catch (AmoCRMException $e) {}

        if ($customFields->count() > 0) $fields_count = true;
        while ($fields_count) {
            foreach ($customFields as $customField) {
                $class = explode('\\', get_class($customField));
                $class = end($class);
                $fields[] = [$customField->getId(), $class];
            }

            if ($customFields->getNextPageLink()) {
                try {
                    $customFields = $customFieldsLeads->nextPage($customFields);
                    usleep(200);
                    $fields_count = true;
                } catch (AmoCRMException $e) {}
            } else $fields_count = false;
        }

        // перебираем поля контактов для определения типа поля
        $customFieldsContacts = $apiClient->customFields(EntityTypesInterface::CONTACTS);
        try {
            $customFields = $customFieldsContacts->get();
            usleep(200);
        } catch (AmoCRMException $e) {}

        foreach ($customFields as $customField) {
            $class = explode('\\', get_class($customField));
            $class = end($class);
            $fields_contacts[] = [$customField->getId(), $class];
        }

        // перебираем полученные сделки и меняем в них значения полей
        foreach ($leads as $ID => $lead) {
            // если такой сделки не существует, пропускаем
            if (!in_array($ID, $IDs)) continue;

            // находим сделку
            try {
                $lead_info = $apiClient->leads()->getOne((int) $ID, ['contacts']);
                usleep(200);
            } catch (AmoCRMException $e) {}

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

            // сохраняем сделку
            try {
                $apiClient->leads()->updateOne($lead_info);
                usleep(200);
                $leads_edit[] = $ID;
            } catch (AmoCRMException $e) {}

            // меняем контакт
            $contacts = $lead_info->getContacts();
            if ($contacts) {
                $contact_ID = $contacts->getBy('isMain', true)->getId();

                try {
                    $contact = $apiClient->contacts()->getOne((int) $contact_ID);
                    usleep(200);
                } catch (AmoCRMException $e) {}

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
                    usleep(200);
                } catch (AmoCRMException $e) {}
            }
        }

        // обновляем строки на значение с цифрой 2
        $result = [];
        foreach ($list['values'] as $key => $row) {
            // если не первая строка и смена статуса с цифрой 1, меняем на 2, иначе просто перезаписываем
            if ($key !== 0 &&
                (int) $row[$expect_number_key] === 1 &&
                // и сделка обновилась
                in_array($row[$expect_lead_key], $leads_edit))
                $row[$expect_number_key] = '2';

            $result[] = $row;
        }

        $value_range = new Google_Service_Sheets_ValueRange();
        $value_range->setValues($result);
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $service->spreadsheets_values->update(
            $sheet_ID, $sheet_properties->title . '!A1:Z', $value_range, $options
        );
        usleep(100);
    }
