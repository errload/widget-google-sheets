<?php
	ini_set('error_log', 'error_in_token_update.log');
    //header('Content-type: application/html;charset=utf8');
    header('Content-type: text/html;charset=utf8');
	header('Access-Control-Allow-Origin: *');

    include_once 'config.php';

    $Config = new Config();
    $scanned_directory = array_diff(scandir($Config->dir), array('..', '.'));
    echo '<pre>'; print_r($scanned_directory); echo '</pre>';

    foreach ($scanned_directory as $key => $path) {
        $dir = $Config->dir . '/' . $path;

        if (is_dir($dir)) {
            $arr = explode('.', $path);
            $domain = '';
            for ($i = count($arr) - 1; $i >= 0; $i--) $domain .= $arr[$i] . '.';
            $domain = trim($domain, '.');
            echo $domain . '<br>';

            $_Config = new Config();
            $_Config->GetSettings($domain);

            if ($_Config->CheckToken()) {
                $accessToken = $_Config->getToken();
                echo date('Y.m.d H.i.s', $accessToken->getExpires()) . '<br>';
                $apiClient = $_Config->getAMO_apiClient();
                $accessToken = $_Config->getToken();
                echo date('Y.m.d H.i.s', $accessToken->getExpires()) . '<br>';
            }

            echo '<br>';
        }
    }
