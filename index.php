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

$app->run();

?>