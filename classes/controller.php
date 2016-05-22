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
      $latest = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByNumber',array('latest',false));
      for($i = 1; $i < 11; $i++)
      {
      	$latest['miner'] = substr($latest['miner'],2);
        $blocks[] = $latest;
        $latest = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByHash',array($latest['parentHash'],false));
      }
    } else {
      return $this->search($search);
    }

    $this->innerView->assign('hashrate', $this->model->getHashrate());
    $this->innerView->assign('supply', $this->model->getSupply());
    $this->innerView->assign('blocks', $blocks);
    return $this->innerView;
  }

  public function supply()
  {
  	$this->innerView->assign('supply', $this->model->getSupply());
  	return $this->innerView;
  }

  public function hashrate()
  {
  	$this->innerView->assign('hashrate', $this->model->getHashrate());
  	return $this->innerView;
  }

  public function blockCount()
  {
  	$latest = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByNumber',array('latest',false));
  	$this->innerView->assign('blockcount', hexdec($latest['number']));
  	return $this->innerView;
  }

  public function difficulty()
  {
  	$latest = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByNumber',array('latest',false));
  	$this->innerView->assign('difficulty', hexdec($latest['difficulty']));
  	return $this->innerView;
  }

  public function search($search)
  {
    $block   = null;
    $tx      = null;
    $address = null;

    if(substr($search, 0, 2) != '0x')
    {
    	if(is_numeric($search))
    	{
    		$search = '0x'.dechex($search);
    	}
    	else
    	{
    		$search = '0x'.$search;
    	}
    }

    $block = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByNumber',array($search,false));
    if(empty($block))
    {
      $block = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByHash',array($search,false));
    }

    if(!empty($block))
    {
      $this->innerView->setTemplate('block');
      return $this->block($block['number']);
    }

    $tx = $this->model->fromBlockchain($this->config['prefix'].'_getTransactionByHash',array($search));
    if(!empty($tx))
    {
      $this->innerView->setTemplate('transaction');
      return $this->transaction($tx['hash']);
    }

    $address = $this->model->fromBlockchain($this->config['prefix'].'_getBalance',array($search));
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
    $block = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByNumber',array($number,true));
    if(empty($block))
    {
      $block = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByHash',array($number,true));
    }

	$block['dataFromHex'] = utf8_encode($this->model->hex2str($block['extraData']));
	$currentBlock         = $this->model->fromBlockchain($this->config['prefix'].'_blockNumber');
	$block['conf']        = $currentBlock - $block['number'].' Confirmations';
	$block['miner']       = substr($block['miner'],2);
	$block['extraData']   = substr($block['extraData'],2);
	$block['hash']        = substr($block['hash'],2);
	foreach($block as $key=>$value)
	{
		if(!is_array($value) && substr($value,0,2) == '0x')
		{
			$block[$key] = hexdec($block[$key]);
		}
	}


    foreach($block['transactions'] as $key=>$transaction)
    {
		$block['transactions'][$key]['ethValue']    = $transaction['value'] / 1000000000000000000;
		$block['transactions'][$key]['hash']        = substr($transaction['hash'],2);
		$block['transactions'][$key]['from']        = substr($transaction['from'],2);
		$block['transactions'][$key]['to']          = substr($transaction['to'],2);
		foreach($block['transactions'][$key] as $k=>$v)
		{
			if(substr($v,0,2) == '0x')
			{
				$block['transactions'][$key][$k] = hexdec($transaction[$k]);
			}
		}
    }

    $this->innerView->assign('current',$currentBlock);
    $this->innerView->assign('block',$block);
    return $this->innerView;
  }


  public function transaction($hash)
  {
    $transaction = $this->model->fromBlockchain($this->config['prefix'].'_getTransactionByHash',array($hash));

    if(!isset($transaction['blockHash']) || empty($transaction['blockHash']))
    {
        $transaction['blockHash'] = 'pending';
    }

    if(!isset($transaction['blockNumber']) || empty($transaction['blockNumber']))
    {
      $transaction['blockNumber'] = 'pending';
      $transaction['conf']        = 'pending';
    } else {
      $transaction['conf'] = $this->model->fromBlockchain($this->config['prefix'].'_blockNumber') - $transaction['blockNumber'];
      if($transaction['conf'] == 0)
      {
        $transaction['conf'] = 'unconfirmed';
      }

      $block = $this->model->fromBlockchain($this->config['prefix'].'_getBlockByNumber',array($transaction['blockNumber'],false));
      if(!empty($block))
      {
        $transaction['time'] = $block['timestamp'];
      }

    }

	$transaction['ethValue']    = $transaction['value'] / 1000000000000000000;
	$transaction['txprice']     = ($transaction['gas'] * $transaction['gasPrice']) / 1000000000000000000;
	$transaction['hash']        = substr($transaction['hash'],2);
	$transaction['from']        = substr($transaction['from'],2);
	$transaction['to']          = substr($transaction['to'],2);
	$transaction['blockHash']   = substr($transaction['blockHash'],2);
	$transaction['blockNumber'] = hexdec($transaction['blockNumber']);
	$transaction['gas']         = hexdec($transaction['gas']);
	$transaction['nonce']       = hexdec($transaction['nonce']);
	$transaction['input']        = $this->model->hex2str($transaction['input']);

    $this->innerView->assign('tx',$transaction);
    return $this->innerView;
  }


  public function address($address)
  {
  	if(substr($address,0,2) != '0x')
  	{
  		$address = '0x'.$address;
  	}

    $address = array(
      'address' => substr($address,2),
      'balance' => $this->model->fromBlockchain($this->config['prefix'].'_getBalance',array($address)) / 1000000000000000000,
      'txCount' => hexdec($this->model->fromBlockchain($this->config['prefix'].'_getTransactionCount',array($address))),
      'code'    => $this->model->fromBlockchain($this->config['prefix'].'_getCode',array($address))
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

          if(substr($number,0,2) != '0x' && $number != 'latest')
          {
          	if(is_int($number))
          	{
          		$number = dechex($number);
          	}
          	
            $number = '0x'.$number;
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
      case 'supply':
      	$this->innerView = $this->supply();
      	break;
      case 'hashrate':
      	$this->innerView = $this->hashrate();
      	break;
      case 'blockcount':
      	$this->innerView = $this->blockCount();
      	break;
      case 'difficulty':
      	$this->innerView = $this->difficulty();
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