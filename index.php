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
function listMessages($service, $userId, $labelIds = array())
{
    $pageToken = NULL;
    $messages = array();
    $opt_param = array();
    do {
        try {
            if ($pageToken) {
                $opt_param['pageToken'] = $pageToken;
            }
            if (count($labelIds) > 0) {
                $opt_param['labelIds'] = $labelIds;
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
        $message_raw = $service->users_messages->get('me', $listUsersMessage->getId(), ['format' => 'raw']);
        $subject = '(Sem assunto)';
        $from = '';
        echo '<pre>';var_dump($message->getPayload()->getParts());echo '</pre>';
        foreach ($message->getPayload()->getParts() as $part) {
            echo '<pre>';
            var_dump($part->getbody());
            echo '</pre>';
        }

        foreach ($message->getPayload()->getHeaders() as $header) {
            if ($header->getName() == 'Subject') {
                $subject = $header->getValue();
            }
            if ($header->getName() == 'From') {
                $from = $header->getValue();
            }
        }

        echo 'ID message: ' . $message->getId() . '<br>De: ' . $from . '<br>Assunto: <b>' . $subject . '</b><br>';
        echo 'Snippet message: <b>' . $message->getSnippet() . '</b><br>';
        echo 'ID thread: <b>' . $message_raw->getThreadId() . '</b><br>';
        echo 'ID thread: <b>' . $message->getThreadId() . '</b><br>';
        echo 'Mensagem original: <b>' . rtrim(strtr(base64_decode($message_raw->getRaw()), '+/', '-_'), '=') . '</b><br>';
        echo '<br><a href="?delete_message=' . $message->getId() . '">Excluir mensagem: ' . $message->getId() . '</a>';
        echo '<br><br><br><br>';
    }

    return $messages;
}

function delete_message($service, $userId, $messageId)
{
    $service->users_messages->trash($userId, $messageId);
}

try {

    $client = getClient();
    $_SESSION['client'] = $client;

    $service = new Google_Service_Gmail($client);

    if (isset($_GET['delete_message']) && strlen($_GET['delete_message']) > 0) {
        delete_message($service, 'me', $_GET['delete_message']);
        $_GET['listar_emails'] = true;
    }
    echo 'Olá, ' . $service->users->getProfile('me')->getEmailAddress() . '<br><br>';
    echo '<a href="?enviar_email=true">Clique aqui para enviar um email de teste</a> || ';
    echo '<a href="?listar_emails=true">Clique aqui para listar todos os emails</a><br><br>';

    $user = 'me';
    $results = $service->users_labels->listUsersLabels($user);
    echo '<br><b>Labels</b><br>';
    if (count($results->getLabels()) == 0) {
        print "No labels found.\n";
    } else {
        print "Labels:\n";
        foreach ($results->getLabels() as $label) {
            print('<a href="?list_emails_label=' . $label->getId() . '">' . $label->getName() . "</a> - ");
        }
    }
    echo '<br><br>';
    if (isset($_GET['enviar_email']) && $_GET['enviar_email']) {
        echo '<form method="post">Assunto: <input name="subject"><br>Email: <input name="email_address"><br>Mensagem: <br><textarea name="message" id="" cols="30" rows="10"></textarea><button type="submit">Enviar</button></form>';
        if (isset($_POST['email_address']) && strlen($_POST['email_address']) > 0 && isset($_POST['message']) && strlen($_POST['message']) > 0) {
            $strSubject = $_POST['subject'];
            $strRawMessage = "From: " . $service->users->getProfile('me')->getEmailAddress() . " <" . $service->users->getProfile('me')->getEmailAddress() . ">\r\n";
            $strRawMessage .= "To: " . $_POST['email_address'] . " <" . $_POST['email_address'] . ">\r\n";
            $strRawMessage .= 'Subject: =?utf-8?B?' . base64_encode($strSubject) . "?=\r\n";
            $strRawMessage .= "MIME-Version: 1.0\r\n";
            $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
            $strRawMessage .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
            $strRawMessage .= $_POST['message'];

            // The message needs to be encoded in Base64URL
            $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
            $msg = new Google_Service_Gmail_Message();
            $msg->setRaw($mime);

            //The special value **me** can be used to indicate the authenticated user.
            $service->users_messages->send("me", $msg);
        }
    }
    if (isset($_GET['listar_emails']) && $_GET['listar_emails']) {
        listMessages($service, $user);
    }
    if (isset($_GET['list_emails_label']) && $_GET['list_emails_label']) {
        listMessages($service, $user, array($_GET['list_emails_label']));
    }
} catch (Exception $exception) {
    echo $exception->getMessage();
}

