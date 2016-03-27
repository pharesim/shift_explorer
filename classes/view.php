<?php
class View {

  private $path = 'views';
  private $layout = '';
  public $template = 'home';
  private $config = array();

  private $data = array();

  public function __construct($viewPath)
  {
    global $config;
    $this->config = $config;
    $this->path = $this->path.$viewPath;
  }

  public function assign($key, $value)
  {
    $this->data[$key] = $value;
  }

  public function setLayout($layout)
  {
    $this->layout = 'layouts'.DS.$layout;
  }

  public function loadLayout()
  {
    $layout = $this->layout;
    $file = $this->path.DS.$layout.'.phtml';
    return $this->__load($file,'layout');
  }

  public function setTemplate($template)
  {
    $this->template = 'templates'.DS.$template;
  }

  public function loadTemplate()
  {
    $tpl = $this->template;
    $file = $this->path.DS.$tpl.'.phtml';
    return $this->__load($file,'template');
  }

  public function element($element,$data = array())
  {
    $file = $this->path.DS.'elements'.DS.$element.'.phtml';
    $this->assign('element',$data);
    return $this->__load($file,'element');
  }

  private function __load($file,$type)
  {
    $exists = file_exists($file);

    if ($exists)
    {
      ob_start();

      include $file;
      $output = ob_get_contents();
      ob_end_clean();

      return $output;
    }
    else
    {
      return 'could not find '.$type.' '.$file;
    }
  }

  public function timeElapsed($time,$type = array('full'=>false,'limited'=>2))
  {
    if(!empty($time))
    {
      $now = new DateTime;
      try {
        $ago = new DateTime($time);
      } catch (Exception $e) {
          return false;
      }
      $diff = $now->diff($ago);

      $diff->w = floor($diff->d / 7);
      $diff->d -= $diff->w * 7;

      $string = array(
          'y' => 'year',
          'm' => 'month',
          'w' => 'week',
          'd' => 'day',
          'h' => 'hour',
          'i' => 'minute',
          's' => 'second',
      );
      foreach ($string as $k => &$v)
      {
          if ($diff->$k)
          {
              $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
          }
          else
          {
              unset($string[$k]);
          }
      }

      if (!$type['full'])
      {
        if($type['limited'])
        {
          $string = array_slice($string, 0, 2);
        }
        else
        {
          $string = array_slice($string, 0, 1);
        }
      }

      return $string ? implode(', ', $string) : 'just now';
    }

    return false;
  }


  public function debug($data)
  {
    if($this->config['debug'] == true)
    {
      echo '<div><pre>';
      var_dump($data);
      echo '</pre></div>';
    }

    return false;
  }

}