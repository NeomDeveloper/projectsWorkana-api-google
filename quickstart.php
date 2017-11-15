<?php
/**
 * Created by PhpStorm.
 * User: nizam
 * Date: 12/11/2017
 * Time: 12:47
 */

require_once __DIR__ . '/vendor/autoload.php';

define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', '5NAYdcS3ZYZKN6AFluXC-FXG');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/gmail-php-quickstart.json
define('SCOPES', implode(' ', array(
        Google_Service_Gmail::GMAIL_MODIFY)
));

//if (php_sapi_name() != 'cli') {
//    throw new Exception('This application must be run on the command line.');
//}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setRedirectUri('http://localhost:82');
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.

/************************************************
 * If we're logging out we just need to clear our
 * local access token in this case
 ************************************************/
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['access_token']);
    unset($_SESSION['csrf_token']);
}

try {
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setRedirectUri('http://localhost:82/quickstart.php');
    $client->setAccessType('offline');
    $authUrl = $client->createAuthUrl();
    echo $authUrl;

    $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false,),));
    $client->setHttpClient($guzzleClient);

    /************************************************
     * If we have a code back from the OAuth 2.0 flow,
     * we need to exchange that with the
     * Google_Client::fetchAccessTokenWithAuthCode()
     * function. We store the resultant access token
     * bundle in the session, and redirect to ourself.
     ************************************************/
//    if (isset($_GET['code'])) {
//        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
//        $client->setAccessToken($token);
//
//        // store in the session also
//        $_SESSION['access_token'] = $token;
//
//        // redirect back to the example
////        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
//    }

    /************************************************
    If we have an access token, we can make
    requests, else we generate an authentication URL.
     ************************************************/
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client->setAccessToken($_SESSION['access_token']);
    } else {
        $authUrl = $client->createAuthUrl();
    }

//    if ($client->getAccessToken() && isset($_REQUEST['url'])) {
//        $url = new Google_Service_Urlshortener_Url();
//        $url->longUrl = $_REQUEST['url'];
//        $short = $service->url->insert($url);
//        $_SESSION['access_token'] = $client->getAccessToken();
//    }

    $service = new Google_Service_Gmail($client);

    // Print the labels in the user's account.
    $user = 'me';
    $results = $service->users_messages;
    echo '<pre>';var_dump($results);echo '</pre>';
} catch (Google_Service_Exception $exception) {
    echo  '<pre>';var_dump($exception->getMessage());echo  '</pre>';
}