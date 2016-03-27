<?php
$before = microtime(true);
require_once('bootstrap.php');
require_once('config'.DS.'config.php');
require_once('basics.php');
require_once('classes/controller.php');
require_once('classes/model.php');
require_once('classes/view.php');

if ($argv) {
    foreach ($argv as $i=>$v)
    {
        $it = explode("=",$argv[$i]);
        if (isset($it[1]))
        {
          $_GET[$it[0]] = $it[1];
        }
        else
        {
          $_GET[] = $v;
          if(isset($_GET[1]))
          {
            $_GET['page'] = $_GET[1];
          }
        }
    }
}
$request = array_merge($_GET, $_POST);
$controller = new Controller($request,DS.'cli');
echo $controller->display();

if($config['debug'] == true):
  $after = microtime(true);
  echo ($after-$before) . " sec";
endif;