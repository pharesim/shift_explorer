<?php
class Controller
{

  private $config = null;
  private $view = null;
  private $model = null;
  private $template = '';

  public function __construct($request,$viewPath = null)
  {
    global $config;
    $this->viewPath = $viewPath;
    $this->config = $config;
    $this->request = $request;
    $this->template = 'home';
    $this->model = new Model();
    $this->requestType = 'web';
    $this->view = new View($this->viewPath);
  }

  public function display(){
    switch($this->viewPath)
    {
      case DS.'cli':
        $layout = 'ajax';
        break;
      case DS.'api':
        $layout = 'empty';
        break;
      default:
        $layout = $this->config['layout'];
    }

    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
    {
      $this->requestType = 'ajax';
      $layout = 'empty';
    }

    $this->view->setLayout($layout);

    if(isset($this->request['page']))
    {
      $this->template = str_replace('.','',$this->request['page']);
    }

    $this->innerView = new View($this->viewPath);
    $this->innerView->setTemplate($this->template);

    $this->_setParams();

    $this->view->assign('layout_content', $this->innerView->loadTemplate());
    return $this->view->loadLayout();
  }

  public function home($search = null)
  {
    if(!$search)
    {
      $latest = $this->model->fromBlockchain('eth_getBlockByNumber',array('latest',false));
      for($i = 1; $i < 11; $i++)
      {
        $blocks[] = $latest;
        $latest = $this->model->fromBlockchain('eth_getBlockByHash',array($latest['parentHash'],false));
      }
    } else {
      return $this->search($search);
    }

    $this->innerView->assign('supply', $this->model->getSupply());
    $this->innerView->assign('blocks', $blocks);
    return $this->innerView;
  }

  public function search($search)
  {
    $block   = null;
    $tx      = null;
    $address = null;

    if(substr($search, 0, 2) != '0x')
    {
    	$search = '0x'.dechex($search);
    }

    $block = $this->model->fromBlockchain('eth_getBlockByNumber',array($search,false));
    if(empty($block))
    {
      $block = $this->model->fromBlockchain('eth_getBlockByHash',array($search,false));
    }

    if(!empty($block))
    {
      $this->innerView->setTemplate('block');
      return $this->block($block['number']);
    }

    $tx = $this->model->fromBlockchain('eth_getTransactionByHash',array($search));
    if(!empty($tx))
    {
      $this->innerView->setTemplate('transaction');
      return $this->transaction($tx['hash']);
    }

    $address = $this->model->fromBlockchain('eth_getBalance',array($search));
    if($address > 0)
    {
      $this->innerView->setTemplate('address');
      return $this->address($search);
    }

    $this->innerView->setTemplate('empty_search');
    return $this->innerView;
  }

  public function block($number)
  {
    $block = $this->model->fromBlockchain('eth_getBlockByNumber',array($number,true));
    if(empty($block))
    {
      $block = $this->model->fromBlockchain('eth_getBlockByHash',array($number,true));
    }

    $block['dataFromHex'] = utf8_encode($this->model->hex2str($block['extraData']));
    $currentBlock = $this->model->fromBlockchain('eth_blockNumber');
    $block['conf'] = $currentBlock - $block['number'].' Confirmations';
    $this->innerView->assign('current',$currentBlock);
    $this->innerView->assign('block',$block);
    return $this->innerView;
  }


  public function transaction($hash)
  {
    $transaction = $this->model->fromBlockchain('eth_getTransactionByHash',array($hash));

    if(!isset($transaction['blockHash']) || empty($transaction['blockHash']))
    {
        $transaction['blockHash'] = 'pending';
    }

    if(!isset($transaction['blockNumber']) || empty($transaction['blockNumber']))
    {
      $transaction['blockNumber'] = 'pending';
      $transaction['conf']        = 'pending';
    } else {
      $transaction['conf'] = $this->model->fromBlockchain('eth_blockNumber') - $transaction['blockNumber'];
      if($transaction['conf'] == 0)
      {
        $transaction['conf'] = 'unconfirmed';
      }

      $block = $this->model->fromBlockchain('eth_getBlockByNumber',array($transaction['blockNumber'],false));
      if(!empty($block))
      {
        $transaction['time'] = $block['timestamp'];
      }

    }

    $transaction['gasPrice'] = $transaction['gasPrice'];

    $transaction['ethValue'] = $transaction['value'] / 1000000000000000000;

    $transaction['txprice'] = ($transaction['gas'] * $transaction['gasPrice']) / 1000000000000000000;

    $this->innerView->assign('tx',$transaction);
    return $this->innerView;
  }


  public function address($address)
  {
    $address = array(
      'address' => $address,
      'balance' => $this->model->fromBlockchain('eth_getBalance',array($address)) / 1000000000000000000,
      'txCount' => $this->model->fromBlockchain('eth_getTransactionCount',array($address)),
      'code'    => $this->model->fromBlockchain('eth_getCode',array($address))
    );
    $this->innerView->assign('address',$address);
    return $this->innerView;
  }


  private function _setParams()
  {
    if(isset($this->request['page']))
    {
      switch($this->request['page'])
      {
        case 'block':
          if(isset($this->request['block']))
          {
            $number = $this->request['block'];
          }
           elseif(isset($this->request[2]))
          {
            $number = $this->request[2];
          }

          if(substr($number,0,2) != '0x')
          {
            $number = '0x'.dechex($number);
          }

          break;
        case 'transaction':
          if(isset($this->request['tx']))
          {
            $hash = $this->request['tx'];
          }
          elseif(isset($this->request[2]))
          {
            $hash = $this->request[2];
          }

          break;
        case 'address':
          if(isset($this->request['address']))
          {
            $address = $this->request['address'];
          }
          elseif(isset($this->request[2]))
          {
            $address = $this->request[2];
          }

          break;
      }
    }

    switch($this->template){
      case 'block':
        $this->innerView = $this->block($number);
        break;
      case 'transaction':
        $this->innerView = $this->transaction($hash);
        break;
      case 'address':
        $this->innerView = $this->address($address);
        break;
      case 'home':
      default:
        $search = null;
        if(isset($this->request['searchFor']))
        {
          $search = trim($this->request['searchFor']);
        }

        $this->innerView = $this->home($search);
        break;
    }
  }

}