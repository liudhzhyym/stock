<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Spss extends MY_Controller {


    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');

    }

    public function parseFromSpss($content)
    {
    	$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
		$fileNameRule = '/FILE="D:\\\mycloud\\\cloud\\\online\\\result\\\([^.]+)\.compare"/';  
		//$fileNameRule = '/FILE="D:\\\mycloud/';  
    	preg_match_all($fileNameRule,$content,$result); 
    	$spssResult = array();
    	$file = '';
    	if(!empty($result[1]))
    	{
	    	$file = $result[1][0];
	    	if(!empty($file))
	    	{
	    		$rule = '/\|V[0-9]\|.*/';
	    		preg_match_all($rule,$content,$ret);
	    		if(!empty($ret[0]))
	    		{
	    			foreach($ret[0] as $line)
	    			{
	    				$temp = explode("|", $line);
	    				$key = $temp[1];
	    				if(!empty($key))
	    				{

	    					$info = array(
								'avg' => floatval($temp[2]),
								'N' => floatval($temp[3]),
								'sd' => floatval($temp[4]),
								'sd_err' => floatval($temp[5]),
	    					);
	    					$spssResult['static'][$key] = $info;
	    				}
	    				//print_r($temp);
	    			}
	    		}
	    		//相关系数
	    		$rule = '/\|V[0-9] & V[0-9]\|.*/';
	    		preg_match_all($rule,$content,$ret);
	    		// |V2 & V3|391|.956    |.000|
	    		if(!empty($ret[0]))
	    		{
	    			foreach($ret[0] as $line)
	    			{
	    				$temp = explode("|", $line);
	    			 	$key = $temp[1];
	    				if(!empty($key))
	    				{
	    					$info = array(
								'N' => floatval($temp[2]),
								'cor' => floatval($temp[3]),
								'sig' => floatval($temp[4]),
	    					);
	    					$spssResult['cor'][$key] = $info;
	    				}
	    				//print_r($temp);
	    			}
	    		}

	    		//t检验
	    		$rule = '/\|V[0-9] - V[0-9]\|.*/';
	    		preg_match_all($rule,$content,$ret);
	    		// |V2 & V3|391|.956    |.000|
	    		if(!empty($ret[0]))
	    		{
	    			foreach($ret[0] as $line)
	    			{
	    				$temp = explode("|", $line);
	    			 	$key = $temp[1];
	    				if(!empty($key))
	    				{
	    					$info = array(
								'avg' => floatval($temp[2]),
								'sd' => floatval($temp[3]),
								'sd_err' => floatval($temp[4]),
								'lower' => floatval($temp[5]),
								'upper' => floatval($temp[6]),
								't' => floatval($temp[7]),
								'df' => floatval($temp[8]),
								'sig' => floatval($temp[9]),
	    					);
	    					$spssResult['ttest'][$key] = $info;
	    				}
	    				//print_r($temp);
	    			}
	    		}
	    	}	  
	    	$res['data'] = array(
	    		'file' => $file,
	    		'spss' => $spssResult,
	    	);  		
    	}
    	return $res;
    }

    public function test()
    {
    	$resultFile = "application/data/spss_result.conf";
		$content = file_get_contents($resultFile);
		$resultArr = explode("GET DATA", $content);
		$spssResult = array();
		foreach($resultArr as $block)
		{
			$ret = $this->parseFromSpss($block);
			if(!empty($ret['data']['spss']))
			{
				$spssResult[$ret['data']['file']] = $ret['data']['spss'];
			}
		}
		print_r($spssResult);
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */