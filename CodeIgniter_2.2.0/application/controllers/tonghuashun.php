<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tonghuashun extends MY_Controller {


    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');

    }


	public function getListByStrategy($strategyName)
	{
		$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
		//$url = "http://www.iwencai.com/stockpick/tipssummary?index_name=MACD";
		$url = "http://www.iwencai.com/stockpick/tipssummary?index_name=${strategyName}";
		// curl抓取网页
	    $_ret = $this->httpCall($url);
	    $htmlContent = $_ret['data'];
	    //echo $htmlContent;
	    //解析总股票数
	    $totalRule = '/data_item.typeData.*(\[{.*}\]);/';  
	    preg_match_all($totalRule,$htmlContent,$result); 
	    $list = $result[1][0];
	    $listArr = json_decode($list,true);
	    $strategyList = array();
	    $excludeList = array('分钟','周','月');
	    $excludeRule = '/(分钟|周|月)/';  
	    foreach($listArr as $info)
	    {
	    	$temp = explode("_", $info['sub_querys']);
	    	if(!empty($temp))
	    	{
	    		foreach($temp as $name)
	    		{
	    			//$name = "60分钟";
	    			preg_match_all($excludeRule,$name,$matchRet);
	    			if(empty($matchRet[0]))
	    			{
	    				$strategyList[] = $name;
						$data = array(
							'strategy' => $name,
						);
						$mysqlRet = $this->db->insert('strategy',$data,true);
	    				//$strategyList = array_merge($strategyList,$temp);
	    			}
	    			else
	    			{
	    				log_message("debug","skip [$name] because container [$excludeRule]");
	    			}
	    			//print_r($matchRet);
	    			//break;
	    			//foreach()
	    			//
	    		}
	    		
	    	}
	    }
	    $res['data'] = $strategyList;
	    log_message("debug","getListByStrategy of [$strategyName] res is [".var_export($res,true),true);
	    return $res;
	}

	public function getAllStrategyList()
	{
		$nameList = $this->getList('strategyType.conf');
		foreach($nameList as $name)
		{
			$this->getListByStrategy($name);
		}
		//print_r($nameList);
	}

	public function queryByStrategyIndexAndDay($index,$day)
	{
		//$name = 'macd金叉';
		//$day = '20140102';
		$query = $this->db->get('strategy');
		$strategyList = array();
		foreach ($query->result_array() as $row)
        {
            $strategyList[] = strtolower($row['strategy']);
            //$stockData[$day][$row['name']] = $row['value'];
        }
        $strategyName = $strategyList[$index];
        log_message("debug","getListByStrategy index is [$index],day is [$day], name is [$strategyName], ret is [".var_export($strategyList,true));
		$this->queryByStrategyAndDay($strategyName,$day);
		$this->checkMem();
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */