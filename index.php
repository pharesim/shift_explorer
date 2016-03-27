<?php
$before = microtime(true);

require_once('bootstrap.php');
require_once('config'.DS.'config.php');
require_once('basics.php');
require_once('classes/controller.php');
require_once('classes/model.php');
require_once('classes/view.php');

$request = array_merge($_GET, $_POST);
$controller = new Controller($request);
echo $controller->display();

if($config['debug'] == true):
  $after = microtime(true);
  echo ($after-$before) . " sec";
endif;
