<?php
	ini_set('error_log', 'error_in_token_get.log');
    header('Content-type: text/html;charset=utf8');

    include_once 'config.php';

    use AmoCRM\Client\AmoCRMApiClient;

    $Config = new Config();

    if (isset($_GET['referer'])) {
        $Config->GetSettings($_GET['referer']);
        $apiClient = new AmoCRMApiClient($Config->oauth_client_uuid, $Config->secret, $Config->redirectUrl);
        $accessToken = $Config->getToken();
        
        if ($accessToken) {
            try {
                $apiClient
                    ->setAccessToken($accessToken)
                    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
                    ->onAccessTokenRefresh(
                        function (AccessTokenInterface $accessToken, string $baseDomain) {
                            $Config->saveToken([
                                'accessToken' => $accessToken->getToken(),
                                'refreshToken' => $accessToken->getRefreshToken(),
                                'expires' => $accessToken->getExpires(),
                                'baseDomain' => $baseDomain,
                            ]);
                        }
                    );
            } catch (Exception $e) {
                if (file_exists($Config->dir . '/token.json')) unlink($Config->dir . '/token.json');
                $accessToken = false;
                // die((string) $e);
            }
        }

        if (!$accessToken){
            // session_start();
            if (isset($_GET['referer'])) $apiClient->setAccountBaseDomain($_GET['referer']);

            if (!isset($_GET['code'])) {
                // $state = bin2hex(random_bytes(16));
                // $_SESSION['oauth2state'] = $state;
                exit('Invalid token');
            } else if (empty($_GET['client_id']) || empty($Config->oauth_client_uuid) || ($_GET['client_id'] !== $Config->oauth_client_uuid)) {
                //unset($_SESSION['oauth2state']);
                exit('Invalid state');
            }
            
            /**
             * Ловим обратный код
             */
            try {
                $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

                if (!$accessToken->hasExpired()) {
                    $Config->saveToken([
                        'accessToken' => $accessToken->getToken(),
                        'refreshToken' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                        'baseDomain' => $apiClient->getAccountBaseDomain(), // $baseDomain
                    ]);
                }
            } catch (Exception $e) { die((string)$e); }
            
            // $ownerDetails = $apiClient->getOAuthClient()->getResourceOwner($accessToken);
            // printf('Hello, %s!', $ownerDetails->getName());
            // header('Location: ' . 'https://' . $apiClient->getAccountBaseDomain());
        }

        header('Location: ' . 'https://' . $_GET['referer'] . '/amo-market/#category-installed');
    }
