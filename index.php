<?php session_start();
require_once("vendor/autoload.php");
use \Slim\App;

$settings = require_once(__DIR__.DIRECTORY_SEPARATOR.'settings.php'); 

$app = new App($settings);
require_once("router.php");
$app->run();

?>