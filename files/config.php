<?php
    include_once 'config.ini.php';

    use League\OAuth2\Client\Token\AccessToken;
    use AmoCRM\Client\AmoCRMApiClient;
    use Symfony\Component\Dotenv\Dotenv;
	use Carbon\Carbon;
	use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;

    // текст
    use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // число
    use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // флаг
    use AmoCRM\Models\CustomFieldsValues\CheckboxCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\CheckboxCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\CheckboxCustomFieldValueModel;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // список
    use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseEnumCustomFieldValueModel;

    // мультисписок
    use AmoCRM\Models\CustomFieldsValues\MultiselectCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultiselectCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseEnumCustomFieldValueModel;

    // дата
    use AmoCRM\Models\CustomFieldsValues\DateCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\DateCustomFieldValueModel;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // ссылка
    use AmoCRM\Models\CustomFieldsValues\UrlCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\UrlCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // текстовая область
    use AmoCRM\Models\CustomFieldsValues\TextareaCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextareaCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // переключатель
    use AmoCRM\Models\CustomFieldsValues\RadiobuttonCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\RadiobuttonCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseEnumCustomFieldValueModel;

    // короткий адрес
    use AmoCRM\Models\CustomFieldsValues\StreetAddressCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\StreetAddressCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // адрес
    use AmoCRM\Models\CustomFieldsValues\SmartAddressCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\SmartAddressCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseEnumCodeCustomFieldValueModel;

    // день рождения
    use AmoCRM\Models\CustomFieldsValues\BirthdayCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\BirthdayCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BirthdayCustomFieldValueModel;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // юр. лицо
    use AmoCRM\Models\CustomFieldsValues\LegalEntityCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\LegalEntityCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseArrayCustomFieldValueModel;

    // Дата и Время
    use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateTimeCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\DateTimeCustomFieldValueModel;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\BaseCustomFieldValueModel;

    // тел email
    use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
    use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
    use AmoCRM\Models\CustomFields\MultitextCustomFieldModel;
    use AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;
    use AmoCRM\Models\CustomFields\EnumModel;

    use AmoCRM\Models\CustomFieldsValues\TrackingDataCustomFieldValuesModel;
    use AmoCRM\Models\CustomFieldsValues\ValueCollections\TrackingDataCustomFieldValueCollection;
    // use AmoCRM\Models\CustomFieldsValues\ValueModels\TrackingDataCustomFieldValueModel;
    use AmoCRM\Models\CustomFields\TrackingDataCustomFieldModel;


    class Config {
        public $baseDomain;
        public $oauth_client_uuid;
        public $widget_code;
        public $secret;
        public $redirectUrl = WEB_WIDGET_URL . 'token_get.php';
        public $secretsUrl = WEB_WIDGET_URL . 'token_secrets.php';
        public $logo = WEB_WIDGET_URL . 'logo.png';
        public $dir = __DIR__ . '/accounts';
        public function __construct() {}

        public function Set_Path_From_Domain($domain) {
            $this->baseDomain = $domain;
            $domain = explode('.', $domain);
            $path = '';
            for ($i = count($domain) - 1; $i >= 0; $i--) {
                $path .= $domain[$i] . '.';
            }
            $path = trim($path, '.');
            if(!is_dir($this->dir)) { mkdir($this->dir, 0777, true); }
            $this->dir = $this->dir . '/' . $path;
            if(!is_dir($this->dir)) { mkdir($this->dir, 0777, true); }
            return $this->dir;
        }

        public function SaveSettings($settings) {
            $this->oauth_client_uuid = $settings['oauth_client_uuid'];
            $this->widget_code = $settings['widget_code'];
            $this->secret = $settings['secret'];
            file_put_contents($this->dir . '/settings.json', json_encode($settings));
        }

        public function GetSettings($domain) {
            $this->Set_Path_From_Domain($domain);
            if (!file_exists($this->dir . '/settings.json')) return false;
            $file = file_get_contents($this->dir . '/settings.json');
            $settings = json_decode($file, true);
            $this->oauth_client_uuid = $settings['oauth_client_uuid'];
            $this->widget_code = $settings['widget_code'];
            $this->secret = $settings['secret'];
        }

        public function getToken() {
            // $this->setLog('get Token');

            if (!file_exists($this->dir . '/token.json')) {
                return false;
                // exit('Access token file not found');
            }

            $file = file_get_contents($this->dir . '/token.json');
            $accessToken = json_decode($file, true);

            if (isset($accessToken)
                && isset($accessToken['accessToken'])
                && isset($accessToken['refreshToken'])
                && isset($accessToken['expires'])
                && isset($accessToken['baseDomain'])
            ) {
                // $this->setLog('get Token ok');
                return new AccessToken([
                    'access_token' => $accessToken['accessToken'],
                    'refresh_token' => $accessToken['refreshToken'],
                    'expires' => $accessToken['expires'],
                    'baseDomain' => $accessToken['baseDomain'],
                ]);
            } else {
                // $this->setLog('get Token Invalid ' . $file);
                exit('Invalid access token ' . var_export($accessToken, true));
            }
        }

        public function saveToken($accessToken)
        {
            if (isset($accessToken)
                && isset($accessToken['accessToken'])
                && isset($accessToken['refreshToken'])
                && isset($accessToken['expires'])
                && isset($accessToken['baseDomain'])
            ) {
                $data = [
                    'accessToken' => $accessToken['accessToken'],
                    'expires' => $accessToken['expires'],
                    'refreshToken' => $accessToken['refreshToken'],
                    'baseDomain' => $accessToken['baseDomain'],
                ];

                $this->deleteToken();
                file_put_contents($this->dir  . '/token.json', json_encode($data));
            } else {
                // $this->setLog('saveToken Invalid access token');
                exit('Invalid access token ' . var_export($accessToken, true));
            }
        }

        public function deleteToken() {
            if (file_exists($this->dir . '/token.json')) unlink($this->dir . '/token.json');
        }

        public function CheckToken() {
            $accessToken = $this->getToken();
            if (!$accessToken) return false;
            if ($accessToken->getExpires() < time()) {
                $this->deleteToken();
                return false;
            }
            return true;
        }

        public function Authorization() {
            if (!$this->CheckToken()) {
                $file = file_get_contents(__DIR__ . '/templates/button.html');
                $file = str_replace('{data-client-id}', $this->oauth_client_uuid, $file);
                $file = str_replace('{data-redirect_uri}', $this->redirectUrl, $file);
                $file = str_replace('{data-secrets_uri}', $this->secretsUrl, $file);
                $file = str_replace('{data-logo}', $this->logo, $file);
                $file = str_replace('{WIDGET_DATA_NAME}', WIDGET_DATA_NAME, $file);
                $file = str_replace('{WIDGET_DATA_DESCRIPTION}', WIDGET_DATA_DESCRIPTION, $file);
                $file = str_replace('{WEB_WIDGET_URL}', WEB_WIDGET_URL, $file);
                return $file;
            } else {
                return 'Виджет установлен и авторизован. <br>';
                // Перейти в <a href="/settings/widgets/' . $this->widget_code . '/">настройки</a>.";
            }
        }

        public function getAMO_apiClient() {
            try {
                $apiClient = new AmoCRMApiClient($this->oauth_client_uuid, $this->secret, $this->redirectUrl);
            } catch (Exception $e) { die(); }

            $apiClient->setAccountBaseDomain($this->baseDomain);
            $accessToken = $this->get_and_updateToken($apiClient);

            try {
                $apiClient->setAccessToken($accessToken);
            } catch (Exception $e) { die(); }

            return $apiClient;
        }

        public function get_and_updateToken($apiClient) {
            $accessToken = $this->getToken();

            if ($accessToken) {
                if ($accessToken->getExpires() - time() < 12 * 60 * 60) {
                    try {
                        $baseDomain = $accessToken->getValues()['baseDomain'];
                        $accessToken = $apiClient->getOAuthClient()->getAccessTokenByRefreshToken($accessToken);
                        $this->saveToken([
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]);
                        $accessToken = $this->getToken();
                    } catch (Exception $e) { die((string)$e); }
                }
            }

            return $accessToken;
        }

        public function GetValueByTypeValues($values) {
            $class = get_class($values);
            $value = '';
            switch ($class) {
                // текст
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // число
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // флаг
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\CheckboxCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // список
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // мультисписок
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\MultiselectCustomFieldValueCollection':
                    $value = array();
                    for ($i = 0; $i < $values->count(); $i++) $value[$values[$i]->enumId] = $values[$i]->value;
                    break;
                // дата
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    $value = explode(' ', $value);
                    $value = $value[0];
                    break;
                // ссылка
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\UrlCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // текстова область
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\TextareaCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // переключатель
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\RadiobuttonCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // короткий адрес
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\StreetAddressCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    break;
                // адрес
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\SmartAddressCustomFieldValueCollection':
                    $value = array();
                    for ($i = 0; $i < $values->count(); $i++) $value[$values[$i]->enumId] = $values[$i]->value;
                    break;
                // день рождения
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\BirthdayCustomFieldValueCollection':
                    $value = $values[0]->getValue();
                    $value = explode(' ', $value);
                    $value = $value[0];
                    break;
                // юр. лицо
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\LegalEntityCustomFieldValueCollection':
                    $value = array();
                    $value = $values[0]->getValue();
                    break;
                // дата и время
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\DateTimeCustomFieldValueCollection':
                    // echo '<pre>'; print_r($values); echo '</pre>';
                    $value = $values[0]->getValue();
                    $value = str_replace(' ', 'T', $value);
                    break;
                // телефон и email
                case 'AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection':
                    // echo '<pre>'; print_r($values); echo '</pre>';
                    for ($i = 0; $i < $values->count(); $i++) $value .= $values[$i]->getValue() . ', ';
                    $value = trim($value, ', ');
                    break;
            }

            return $value;
        }

        public function SetFieldValue($customFields, $fieldType, $fieldId, $value) {
            // TextCustomFieldModel+
            // NumericCustomFieldModel+
            // CheckboxCustomFieldModel+
            // SelectCustomFieldModel+
            // DateCustomFieldModel+
            // UrlCustomFieldModel+
            // TextareaCustomFieldModel+
            // RadiobuttonCustomFieldModel+
            // StreetAddressCustomFieldModel+
            // BirthdayCustomFieldModel+
            // DateTimeCustomFieldModel+
            // MultiselectCustomFieldModel+
            // SmartAddressCustomFieldModel+
            // LegalEntityCustomFieldModel+


            // MultitextCustomFieldModel
            // echo '<pre>'; print_r($customFields); echo '</pre>';
            // echo $fieldType . ' ' . $fieldId;
            try {
                $fieldId = (int) $fieldId;
                    if (!empty($customFields)) {
                        switch ($fieldType) {
                            case 'TextCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                // echo '<pre>'; print_r($Field); echo '</pre>';
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new TextCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new TextCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel()) // TextCustomFieldValueModel
                                                    ->setValue((string) $value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new TextCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseCustomFieldValueModel()) // TextCustomFieldValueModel
                                                        ->setValue((string)$value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'NumericCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new NumericCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new NumericCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new NumericCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'CheckboxCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new CheckboxCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new CheckboxCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new CheckboxCustomFieldValueCollection())
                                                ->add(
                                                    (new CheckboxCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'SelectCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new SelectCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new SelectCustomFieldValueCollection())
                                            ->add(
                                                (new SelectCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new SelectCustomFieldValueCollection())
                                                ->add(
                                                    (new SelectCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'MultiselectCustomFieldModel':
                                if (!is_array($value)) {
                                    $array = array();
                                    $array[] = $value;
                                    $value = $array;
                                }

                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null) && (is_array($value))) {
                                    $Field = (new MultiselectCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Values = new MultiselectCustomFieldValueCollection();
                                    foreach( $value as $key => $val) {
                                        $Values->add(
                                            (new BaseEnumCustomFieldValueModel())
                                                // ->setEnumId($key)
                                                ->setValue($val)
                                        );
                                    }
                                    $Field->setValues($Values);
                                    $customFields->add($Field);
                                } else {
                                    if (($value != null) && (is_array($value))) {
                                        $Values = new MultiselectCustomFieldValueCollection();
                                        foreach( $value as $key => $val) {
                                            $Values->add(
                                                (new BaseEnumCustomFieldValueModel())
                                                    // ->setEnumId($key)
                                                    ->setValue($val)
                                            );
                                        }
                                        $Field->setValues($Values);
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'DateCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new DateCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new DateCustomFieldValueCollection())
                                            ->add(
                                                (new DateCustomFieldValueModel())
                                                    ->setValue(new Carbon($value))
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new DateCustomFieldValueCollection())
                                                ->add(
                                                    (new DateCustomFieldValueModel())
                                                        ->setValue(new Carbon($value))
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'UrlCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new UrlCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new UrlCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new UrlCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'TextareaCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new TextareaCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new TextareaCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new TextareaCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'RadiobuttonCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new RadiobuttonCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new RadiobuttonCustomFieldValueCollection())
                                            ->add(
                                                (new BaseEnumCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new RadiobuttonCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseEnumCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'StreetAddressCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new StreetAddressCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new StreetAddressCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new StreetAddressCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'SmartAddressCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new SmartAddressCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Values = new SmartAddressCustomFieldValueCollection();
                                    foreach($value as $key => $val) {
                                        $Values->add(
                                            (new BaseEnumCodeCustomFieldValueModel())
                                                ->setEnumCode($key)
                                                ->setValue($val)
                                        );
                                    }
                                    $Field->setValues($Values);
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Values = new SmartAddressCustomFieldValueCollection();
                                        foreach($value as $key => $val) {
                                            $Values->add(
                                                (new BaseEnumCodeCustomFieldValueModel())
                                                    ->setEnumCode($key)
                                                    ->setValue($val)
                                            );
                                        }
                                        $Field->setValues($Values);
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'BirthdayCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new BirthdayCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new BirthdayCustomFieldValueCollection())
                                            ->add(
                                                (new BirthdayCustomFieldValueModel())
                                                    ->setValue(new Carbon($value))
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new BirthdayCustomFieldValueCollection())
                                                ->add(
                                                    (new BirthdayCustomFieldValueModel())
                                                        ->setValue(new Carbon($value))
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'LegalEntityCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new LegalEntityCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new LegalEntityCustomFieldValueCollection())
                                            ->add(
                                                (new BaseArrayCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new LegalEntityCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseArrayCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'DateTimeCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new DateTimeCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new DateTimeCustomFieldValueCollection())
                                            ->add(
                                                (new DateTimeCustomFieldValueModel())
                                                    ->setValue(new Carbon($value))
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new DateTimeCustomFieldValueCollection())
                                                ->add(
                                                    (new DateTimeCustomFieldValueModel())
                                                        ->setValue(new Carbon($value))
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                            break;
                            // MultitextCustomFieldModel
                            case 'MultitextCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new MultitextCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new MultitextCustomFieldValueCollection())
                                            ->add(
                                                (new MultitextCustomFieldValueModel())
                                                    ->setValue($value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new MultitextCustomFieldValueCollection())
                                                ->add(
                                                    (new MultitextCustomFieldValueModel())
                                                        ->setValue($value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                            case 'TrackingDataCustomFieldModel':
                                $Field = $customFields->getBy('fieldId', $fieldId);
                                // echo '<pre>'; print_r($Field); echo '</pre>';
                                if (empty($Field) && ($value != null)) {
                                    $Field = (new TrackingDataCustomFieldValuesModel())->setFieldId($fieldId);
                                    $Field->setValues(
                                        (new TrackingDataCustomFieldValueCollection())
                                            ->add(
                                                (new BaseCustomFieldValueModel()) //TextCustomFieldValueModel
                                                    ->setValue((string)$value)
                                            )
                                    );
                                    $customFields->add($Field);
                                } else {
                                    if ($value != null) {
                                        $Field->setValues(
                                            (new TrackingDataCustomFieldValueCollection())
                                                ->add(
                                                    (new BaseCustomFieldValueModel()) // TextCustomFieldValueModel
                                                        ->setValue((string)$value)
                                                )
                                        );
                                    } else if ($Field) $Field->setValues((new NullCustomFieldValueCollection()));
                                }
                                break;
                        }
                    }
            } catch (Exception $e) {}

            return $customFields;
        }
    }