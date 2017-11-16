<?php
/**
 * Created by PhpStorm.
 * User: nizam
 * Date: 12/11/2017
 * Time: 12:47
 */

require_once __DIR__ . '/vendor/autoload.php';
session_start();
define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', '5NAYdcS3ZYZKN6AFluXC-FXG.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/gmail-php-quickstart.json
define('SCOPES', implode(' ', array(
        Google_Service_Gmail::GMAIL_MODIFY,
    )
));

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
    $client->setAccessType('offline');
    $client->setRedirectUri('http://localhost:82');

    $client->setHttpClient(new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false,),)));

    // Load previously authorized credentials from a file.
    $credentialsPath = CREDENTIALS_PATH;
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        if (isset($_GET['code'])) {
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            // Store the credentials to disk.
//        if (!file_exists(dirname($credentialsPath))) {
//            mkdir(dirname($credentialsPath), 0700, true);
//        }

            var_dump(file_put_contents($credentialsPath, json_encode($accessToken)));
            printf("Credentials saved to %s\n", $credentialsPath);
        } else {
            $url = $client->createAuthUrl();
            echo "<a href='$url'>Clique aqui para se logar</a>";
            exit;
        }
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

/**
 * Get list of Messages in user's mailbox.
 *
 * @param  Google_Service_Gmail $service Authorized Gmail API instance.
 * @param  string $userId User's email address. The special value 'me'
 * can be used to indicate the authenticated user.
 * @return array Array of Messages.
 */
function listMessages($service, $userId)
{
    $pageToken = NULL;
    $messages = array();
    $opt_param = array();
    do {
        try {
            if ($pageToken) {
                $opt_param['pageToken'] = $pageToken;
            }
            $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
            if ($messagesResponse->getMessages()) {
                $messages = array_merge($messages, $messagesResponse->getMessages());
                $pageToken = $messagesResponse->getNextPageToken();
            }
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    } while ($pageToken);

    foreach ($messages as $listUsersMessage) {
        $message = $service->users_messages->get('me', $listUsersMessage->getId());
//        var_dump($message->);
        echo 'ID message:  ' . $message->getId() . '<br><b>' . $message->getPayload()->getHeaders()[18]->value . '</b><br>';
        echo $message->getSnippet();
        echo '<br><br>';
        exit;
    }

    return $messages;
}

try {
    $client = getClient();
    $_SESSION['client'] = $client;

    $service = new Google_Service_Gmail($client);

    $user = 'me';
    $results = $service->users_labels->listUsersLabels($user);
    echo '<b>Labels</b><br>';
    if (count($results->getLabels()) == 0) {
        print "No labels found.\n";
    } else {
        print "Labels:\n";
        foreach ($results->getLabels() as $label) {
            print($label->getName() . " - ");
        }
    }
    echo '<br>';
//    foreach ($service->users_messages->listUsersMessages($user) as $listUsersMessage) {
//        $message = $service->users_messages->get($user, $listUsersMessage->getId());
//        echo '<b>' . $message->getPayload()->getHeaders()[18]->value . '</b><br>';
//        echo $message->getSnippet();
////        echo '<pre>';var_dump($message->getPayload());echo '</pre>';
////        exit;
//    }
    if($_GET['enviar_email']) {
        $strSubject = 'Test mail using GMail API' . date('M d, Y h:i:s A');
        $strRawMessage = "From: Nizam <nizamomari1234@gmail.com>\r\n";
        $strRawMessage .= "To: Nizam <nizam_omari@hotmail.com>\r\n";
        $strRawMessage .= 'Subject: =?utf-8?B?' . base64_encode($strSubject) . "?=\r\n";
        $strRawMessage .= "MIME-Version: 1.0\r\n";
        $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
        $strRawMessage .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
        $strRawMessage .= "this <b>is a test message!\r\n";

        // The message needs to be encoded in Base64URL
        $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
        $msg = new Google_Service_Gmail_Message();
        $msg->setRaw($mime);

        //The special value **me** can be used to indicate the authenticated user.
        $service->users_messages->send("me", $msg);
    }
    echo '<a href="?enviar_email=true">Clieque aqui para enviar um email de teste</a>';
    listMessages($service, 'me');
} catch (Exception $exception) {
    echo '<pre>';
    var_dump($exception->getMessage());
    echo '</pre>';
}

