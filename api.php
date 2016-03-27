<?php
require_once('bootstrap.php');
require_once('config'.DS.'config.php');
require_once('basics.php');
require_once('classes/controller.php');
require_once('classes/model.php');
require_once('classes/view.php');

$request = array_merge($_GET, $_POST);
$controller = new Controller($request,DS.'api');
echo $controller->display();