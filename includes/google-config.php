<?php
// Load environment variables
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

function getGoogleClient() {
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->addScope("email");
    $client->addScope("profile");

    return $client;
}

function getGoogleLoginUrl() {
    $client = getGoogleClient();
    return $client->createAuthUrl();
}

function getGoogleUserInfo($code) {
    try {
        $client = getGoogleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return false;
        }

        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();

        return [
            'google_id' => $google_account_info->id,
            'email' => $google_account_info->email,
            'name' => $google_account_info->name,
            'picture' => $google_account_info->picture,
        ];
    } catch (Exception $e) {
        return false;
    }
}
?>
