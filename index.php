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

$app->get('/login', function() use ($app){
    echo file_get_contents("login.html");
});
$app->post('/login', function() use ($app){
    $email = $_POST['email'];
    $fields = array(
                        'username' => $_POST['email'],
                        'password' => $_POST['password'],
                        'returnProfile'=>true,
                        'responseFilters'=>'PROFILE,SETTINGS,APPS,CONNECTIONS'

                );


    $ch = curl_init(); 

    // set url 
    curl_setopt($ch, CURLOPT_URL, "https://www.sirqul.com/api/3.05/account/get"); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Application-Key: 66750de6c373ccea7f0a009239e6df32',
        'Application-Rest-Key: 4ed060fd987153ca88db1419c103bfc9'
        ));
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);

    //return the transfer as a string 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    // $output contains the output string 
    $output = curl_exec($ch); 
    $response = json_decode($output);

    if($response->valid = true){
        echo file_get_contents("admin.html");
    }

    // close curl resource to free up system resources 
    curl_close($ch);      


});

$app->post('/status', function () use ($app, $db) {
    $beacon = $_POST['beacon'];
    $patient = $_POST['patient'];
    $time = $_POST['time'];

    $sql = "INSERT INTO status (patient_id, beacon_id, post_time) VALUES (:patient, :beacon, :time)";
    $insert = $db->prepare($sql);
    $insert->execute(array(":patient"=>$patient, ":beacon"=>$beacon, ":time"=>$time));


    // send a push notification

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
    $message = 'Patient status has changed.';

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
        'sound' => 'default',
        'update-type' => 'update'
        );

    // Encode the payload as JSON
    $payload = json_encode($body);

    // Build the binary notification
    $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

    // Send it to the server
    $result = fwrite($fp, $msg, strlen($msg));

    
    // Close the connection to the server
    fclose($fp);

    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode(array("status"=>"done"));

});

$app->get('/recent/:user', function($user) use ($app, $db) {
    $sql = "SELECT b.name, b.description, b.progressStep, s.post_time FROM status s INNER JOIN beacon b ON s.beacon_id = b.beacon_id WHERE s.patient_id = :user ORDER BY s.post_time DESC LIMIT 1";
    $query = $db->prepare($sql);
    $query->execute(array(":user"=>$user));

    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode($query->fetch(PDO::FETCH_ASSOC));
});

$app->get('/statuses', function () use ($app, $db) {

    $sql = "SELECT DISTINCT patient_id FROM status";
    $statement = $db->prepare($sql);
    $statement->execute();

    

    $response = array();

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        
        $sql = "SELECT s.patient_id, b.beacon_id, b.description, b.name FROM status s INNER JOIN beacon b ON s.beacon_id = b.beacon_id WHERE s.patient_id = :patient ORDER BY s.id DESC LIMIT 1";
        $query = $db->prepare($sql);
        $query->execute(array(":patient"=>$row['patient_id']));

        $result = $query->fetch(PDO::FETCH_ASSOC);
        array_push($response,$result);
    }

    $app->response->headers->set('Content-Type', 'application/json'); 
    echo json_encode($response);
});


$app->get('/sessions', function () use ($app, $db) {

    $sql = "SELECT * FROM sessions";
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

    $sql = "INSERT INTO sessions (patient, emergency) VALUES (:patient, :emergency)";
    $update = $db->prepare($sql);
    $update->execute(array(":patient"=>$user, ":emergency"=>time()));




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
        'update-type' => 'emergency'
        );

    // Encode the payload as JSON
    $payload = json_encode($body);

    // Build the binary notification
    $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

    // Send it to the server
    $result = fwrite($fp, $msg, strlen($msg));

    if (!$result) {
        echo 'Message not delivered' . PHP_EOL;
    }
    else {
        $app->response->headers->set('Content-Type', 'application/json'); 
        echo json_encode(array("status"=>"success"));
    }

    // Close the connection to the server
    fclose($fp);
});

$app->run();

?>