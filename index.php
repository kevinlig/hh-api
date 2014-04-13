<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim();

// make DB connection

$username = "b05bccbae358e4";
$password = "7aa40359";
$server = "us-cdbr-east-05.cleardb.net";
$db = "heroku_74679175fcd99e5";



try {
    $db = new PDO("mysql:host=" . $server . ";dbname=" . $db, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}


$app->post('/status', function () use ($app, $db) {
    $beacon = $_POST['beacon'];
    $patient = $_POST['patient'];
    $time = $_POST['time'];

    $sql = "INSERT INTO status (patient_id, beacon_id, post_time) VALUES (:patient, :beacon, :time)";
    $insert = $db->prepare($sql);
    $insert->execute(array(":patient"=>$patient, ":beacon"=>$beacon, ":time"=>$time));

    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode(array("status"=>"done"));

});

$app->get('/recent/:user', function($user) use ($app, $db) {
    $sql = "SELECT * FROM status WHERE patient_id = :user ORDER BY post_time DESC LIMIT 1";
    $query = $db->prepare($sql);
    $query->execute(array(":user"=>$user));

    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($query->fetch(PDO::FETCH_ASSOC));
});

$app->get('/statuses', function () use ($app, $db) {

    $sql = "SELECT * FROM status";
    $statement = $db->prepare($sql);
    $statement->execute();

    $response = $statement->fetchAll(PDO::FETCH_ASSOC);

    $app->response->headers->set('Content-Type', 'application/json'); 
    echo json_encode($response);

});

$app->get('/emergency/:user', function($user) use ($app, $db) {
    // get device token
    $sql = "SELECT deviceToken FROM sessions WHERE patient = :user LIMIT 1";
    $query = $db->prepare($sql);
    $query->execute(array(":user"=>$user));

    $results = $query->fetch(PDO::FETCH_ASSOC);

    // Put your device token here (without spaces):
    $deviceToken = 'ff12c28e30e013641b26847ae81dae500fbda61633f59fc3dba5b85029c37b87';

    // Put your private key's passphrase here:
    $passphrase = "DeathPanelP@ss";

    // Put your alert message here:
    $message = 'URGENT: The care provider needs your immediate attention.';

    ////////////////////////////////////////////////////////////////////////////////

    $ctx = stream_context_create();
    stream_context_set_option($ctx, 'ssl', 'local_cert', 'hhpush.pem');
    stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

    // Open a connection to the APNS server
    $fp = stream_socket_client(
        'ssl://gateway.sandbox.push.apple.com:2195', $err,
        $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);

    

    // Create the payload body
    $body['aps'] = array(
        'alert' => $message,
        'sound' => 'emergency.aif',
        'meta-urgent' => 'true'
        );

    // Encode the payload as JSON
    $payload = json_encode($body);

    // Build the binary notification
    $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

    // Send it to the server
    $result = fwrite($fp, $msg, strlen($msg));

    if (!$result)
        echo 'Message not delivered' . PHP_EOL;
    else
        $app->response->headers->set('Content-Type', 'application/json'); 
        echo json_encode(array("status"=>"success"));

    // Close the connection to the server
    fclose($fp);
});

$app->run();

?>