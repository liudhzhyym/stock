<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

	public $_resultDir;

    function __construct() 
    {
        parent::__construct();
        $this->_resultDir = "application/data/result/";
        if(!is_dir($this->_resultDir))
        {
        	@mkdir($this->_resultDir, 0777, true);
        }
    }

	public function getArrayFromString($content,$split="\n")
	{
	    $arr = explode($split, $content);
	    $result = array();
	    foreach($arr as $value)
	    {
	        $value = trim($value);
	        if(!empty($value))
	        {
	            $result[] = $value;
	        }
	    }
	    return $result;
	}

	public function getList($file)
	{
		$listFile = "application/data/${file}";
		$content = file_get_contents($listFile);
		$list = $this->getArrayFromString($content);
		log_message("debug","getList of file [$listFile] is [".var_export($list,true));
		return $list;
	}

	public function isStock($stock)
	{
		$stock = str_replace(array('sh','sz'), '', $stock);
	    if(preg_match("/^0/",$stock)==1||preg_match("/^6/",$stock)==1)
	    {
	    	return true;
	    }
	    return false;
	}

	public function getAllStockList()
	{
		$list = $this->getList('stockList.conf');
		$codeList = array();
		foreach($list as $code)
		{
		    if($this->isStock($code))
		    {
		    	$codeList[] = $code;
		    }
		    
		}
		//print_r($list);
		return $codeList;
	}

	public function checkMem()
	{
        $memBytes = memory_get_usage();
        $memM = round($memBytes*1.0/(1024*1024),2);
        log_message("debug","now mem used is [$memM]Mb");	
	}

	public function httpCall($url, array $post = array(), array $options = array(), $timeout = 15, $retry = 2, $post = 0) {

        $res = array(
            'errorCode' => 0,
            'errorMsg' => 'ok',
            'data' => array(),
        );

        $defaults = array(
            CURLOPT_POST => $post,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => $timeout,
        //    CURLOPT_POSTFIELDS => http_build_query($post),
        );
        if($post)
        {
        	$defaults[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        log_message("debug","module[stats] method[post] url[{$url}] postData=" . json_encode($post));

        $try = 0;
        for($try=0;$try<$retry;$try++)
        {
            $ch = curl_init();
            curl_setopt_array($ch, $options + $defaults);
            if (!($result = curl_exec($ch))) {
                $res = array(
                    'errorCode' =>  curl_errno($ch),
                    'errorMsg' => curl_error($ch),
                );
                log_message("error","http talk error at [$try:$retry], curl ret is [".json_encode($res));
                sleep(1);
                continue;
            }
            curl_close($ch);
            $res = array(
                'errorCode' => 0,
                'errorMsg' => 'ok',
                'data' => $result,
            );
            break;
        }

        //log_message("error","httpCall ret is [".json_encode($res));
        return $res;
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */