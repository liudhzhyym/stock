<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock extends CI_Controller {

	private $_stockDataDir;

    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');

        //设置文件目录
        $this->_stockDataDir = "application/data/stock_data";
        if(!is_dir($this->_stockDataDir))
        {
        	@mkdir($this->_stockDataDir, 0777, true);
        }
    }

	private function getArrayFromString($content,$split="\n")
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

    private function httpCall($url, array $post = array(), array $options = array(), $timeout = 15, $retry = 2) {

        $res = array(
            'errorCode' => 0,
            'errorMsg' => 'ok',
            'data' => array(),
        );

        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_POSTFIELDS => http_build_query($post),
        );
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

    private function queryDataFromQQ($url)
    {
		$data = file_get_contents($url);
		$arr = explode("\\n\\", $data);
		$url_data = array();
		$test_re="/^[0-9]+/";
		foreach($arr as $value)
		{
			//echo $value;
			$value = trim($value);
			if(preg_match($test_re,$value)==1)
			{
				//$url_data[] = $value;
				$url_data[] = explode(" ", $value);
			}

		}
		return $url_data;
    }

	private function getStockData($code,$startTime=null,$endTime=null)
	{
	    $dir = $this->_stockDataDir;
		$codeFile = "${dir}/${code}";
		if(file_exists($codeFile))
		{
			$str = file_get_contents($codeFile);
	        $data = json_decode($str,true);
	        if(!empty($data))
	        {
	            return $data;
	        }
		}
		//$url = 'http://data.gtimg.cn/flashdata/hushen/daily/14/sh600062.js';
		$url = "http://data.gtimg.cn/flashdata/hushen/daily/14/${code}.js";
		$data_14 = $this->queryDataFromQQ($url);
		$url = "http://data.gtimg.cn/flashdata/hushen/daily/15/${code}.js";
		$data_15 = $this->queryDataFromQQ($url);	
		$all = array_merge($data_14,$data_15);
		$stock_data = array();
		foreach($all as $value)
		{
			$value[0] = '20'.$value[0];
			if($startTime!=null&&$endTime!=null)
			{
				if($value[0]>=$startTime&&$value[0]<=$endTime)
				{
					$stock_data[] = $value;
				}
			}
			else
			{
				$stock_data[] = $value;
			}
		}
	    if(!empty($stock_data))
	    {
		    file_put_contents($codeFile, json_encode($stock_data));
	    }
		return $stock_data;
	}

	public function getAllStockList()
	{
		$list = $this->getList('stockList.conf');
		//print_r($list);
		return $list;
	}

	public function getAllStockData()
	{
		$list = $this->getAllStockList();
		foreach($list as $code)
		{
			$this->getStockData($code);
		}
		//print_r($list);
	}

	//获取上证指数的开盘时间序列
	public function getMarketTimeList()
	{
	    $data = $this->getStockData("sh000001");
	    $timeArr = array();
	    foreach($data as $info)
	    {
	        // if($info[0]<'20150615')
	        // {
	        //     continue;
	        // }
	        $timeArr[] = $info[0];
	    }
	    //print_r($timeArr);
	    return $timeArr;
	}

	public function index()
	{

		// $url = 'http://www.cnblogs.com/lida/archive/2011/02/18/1958211.html';
		// $ret = $this->httpCall($url);
		// print_r($ret);
	}

	public function queryByStrategyAndDay($strategy,$dayTime)
	{
		log_message("debug","get code list of [$strategy] at [$dayTime]");
		if(empty($strategy)||empty($dayTime))
		{
			log_message('error', "strategy [$strategy] or dayTime [$dayTime] should not be null",true);
			return;
		}
		//$dayTime = "20150801";
		//$strategy = "kdj金叉";
		$year = substr($dayTime,0,4);
		$month = substr($dayTime,4,2);
		$day = substr($dayTime,6,2);
	    //$time = "2015年08月01日";
		$time = "{$year}年{$month}月{$day}日";
	    //$strategyStr = "${time}换手率从大到小排名；${time}涨跌幅从大到小排名；${time}量比从大到小排名；${time}A股流通市值从大到小排名；${time}A股总市值从大到小排名；";
	    $strategyStr = $time.$strategy."；";
	    //$strategyStr = str_replace('${time}', $time, $strategyStr);
	    $postData = array(
	        'typed'=>1,
	        'preParams'=>'',
	        'ts'=>1,
	        'f'=>1,
	        'qs'=>1,
	        'selfsectsn'=>'',
	        'querytype'=>'',
	        'searchfilter'=>'',
	        'tid'=>'stockpick',
	        'w'=>$strategyStr,
	    );

	    $dataArr = array();
	    foreach($postData as $key=>$value)
	    {
	        $dataArr[] = "${key}=".urlencode($value);
	    }
	    $urlBase = 'http://www.iwencai.com/stockpick/search';
	    $url = $urlBase."?".implode('&',$dataArr);
	    log_message('debug', "url = $url");
	    // curl抓取网页
	    $_ret = $this->httpCall($url);
	    $htmlContent = $_ret['data'];
	    //echo $htmlContent;
	    $rule = '/"token":"([0-9a-z]+)","staticList"/';  
	    preg_match_all($rule,$htmlContent,$result);  
	    $token = $result[1][0];
	    if(empty($token))
	    {
	    	log_message('error', "FAILED! Get token failed!",true);
	        return;
	    }
	    log_message("debug","token = $token ");
	    $page = 1;
	    $codeInfoList = array();
	    //return;
	 	while (true) 
	    {
	        $apiUrl = "http://www.iwencai.com/stockpick/cache?token={$token}&p={$page}&perpage=30&showType=";
	        $_ret = $this->httpCall($apiUrl);
	        $retStr = $_ret['data'];
	        if(!empty($retStr))
	        {
				$data = array(
					'strategy' => $strategy,
					'day' => $dayTime,
					'page' => $page,
					'result' => $retStr,
				);
				$mysqlRet = $this->db->insert('tonghuashun',$data,true);
		    	if($mysqlRet===false)
		    	{
		    		$mysql = $this->db->last_query();
		    		log_message('error', "insert data of [$stock][$dayTime] failed, mysql is [$mysql]",true);
		    	}
	        }
	        $apiData = json_decode($retStr,true);
	        if(empty($apiData['result'])||$page>100)
	        {
	            break;
	        }
	        //$codeInfoList = array_merge($codeInfoList,$apiData['result']);
	        $page++;
	    } 
	    // if(empty($codeInfoList))
	    // {
	    // 	log_message("error","get code list of [$strategy] at [$dayTime] failed!");
	    // }
	    
	}

	//public function getStrategyList()
	// file = 'strategyList.conf';
	public function getList($file)
	{
		$listFile = "application/data/${file}";
		$content = file_get_contents($listFile);
		$list = $this->getArrayFromString($content);
		log_message("debug","getList of file [$listFile] is [".var_export($list,true));
		return $list;
	}

	public function queryDataByIndexAndDay($strategyIndex,$dayTime)
	{
		ini_set('memory_limit', '-1');
		log_message("debug","queryDataByIndexAndDay index is [$strategyIndex] and dayTime is [$dayTime]");
		$index = (int)$strategyIndex;
		$strategyList = $this->getList('strategy2load.conf');
		$strategy = $strategyList[$index];
		$this->queryByStrategyAndDay($strategy,$dayTime);
		$this->checkMem();
	}

	private function checkMem()
	{
        $memBytes = memory_get_usage();
        $memM = round($memBytes*1.0/(1024*1024),2);
        log_message("debug","now mem used is [$memM]Mb");	
	}

	public function queryData()
	{
		$strategyList = $this->getList('strategy2load.conf');
		$timeList = $this->getMarketTimeList();
		foreach($strategyList as $strategy)
		{
			foreach($timeList as $dayTime)
			{
				$this->queryByStrategyAndDay($strategy,$dayTime);
		        $sleepTime = rand(3,6);
		        //sleep($sleepTime);

		        //echo number_format(memory_get_usage()) . "\n";
				//break;
			}
		}
		log_message("debug","get timeList [".json_encode($timeList)."] and strategyList is [".json_encode($strategyList),true);
	}

	public function updateStockData($stock,$dayTime,$name,$value)
	{
		if(empty($stock)||empty($dayTime)||empty($name))
		{
			log_message('error', "updateStockData stock [$stock] or dayTime [$dayTime] and name [$name] should not be null",true);
			return;
		}

		$data = array(
			'stock' => $stock,
			'day' => $dayTime,
			'name' => $name,
			'value' => $value,
		);
		//log_message('debug', "insert data of [$stock][$dayTime][$name][$value]");
    	$ret = $this->db->insert('stock_data',$data,true);
    	if($ret===false)
    	{
    		$mysql = $this->db->last_query();
    		log_message('error', "insert data of [$stock][$dayTime] failed, mysql is [$mysql]",true);
    	}
	}

	public function convertCode($code)
	{
		$temp = explode(".", $code);
		$newCode = $temp[1].$temp[0];
		return strtolower($newCode);
	}

	//处理同花顺的数据
	public function parseDataByIndexAndDay($strategyIndex,$dayTime)
	{
		ini_set('memory_limit', '-1');
		log_message("debug","parseDataByIndexAndDay index is [$strategyIndex] and dayTime is [$dayTime]");
		$index = (int)$strategyIndex;
		$strategyList = $this->getList('strategy2load.conf');
		$strategy = $strategyList[$index];
		//$this->queryByStrategyAndDay($strategy,$dayTime);
		//$this->checkMem();
    	$conds = array(
    		'strategy' => $strategy,
    		'day' => $dayTime,
    	);
    	$query = $this->db->get_where('tonghuashun', $conds);
    	$cnt = $query->num_rows();
    	log_message("debug","cnt is [$cnt],strategy is [$strategy],$dayTime is [$dayTime]",true);
    	$stockData = array();
    	foreach ($query->result_array() as $row)
    	{
    		$page = (int)$row['page'];
    		$resultArr = json_decode($row['result'],true);
    		if(empty($resultArr['title']))
    		{
    			//查询到的数据为空
    			//重新加载数据
    			log_message("error","reload [$strategy][$dayTime] data",true);
    			$this->queryByStrategyAndDay($strategy,$dayTime);
    			return;
    		}
    		//print_r($resultArr);
    		foreach($resultArr['result'] as $info)
    		{
    			$stock = $this->convertCode($info[0]);
    			//boll突破中轨
    			if($strategy=='boll突破中轨')
    			{
	    			$name = 'boll_break_through';
	    			$value = 1;
	    			//print_r($info);
	    			$this->updateStockData($stock,$dayTime,$name,$value);	
    			}
    			else if($strategy=='kdj金叉')
    			{
	    			$name = 'kdj_gc';
	    			$value = 1;
	    			//print_r($info);
	    			$this->updateStockData($stock,$dayTime,$name,$value);	
    			}
    			else if($strategy=='macd金叉')
    			{
	    			$name = 'macd_gc';
	    			$value = 1;
	    			//print_r($info);
	    			$this->updateStockData($stock,$dayTime,$name,$value);	
    			}
    		}
    	}
    	
    	//$row = $query->result_array();
    	//print_r($row);
	}


	public function test5()
	{
		$this->queryByStrategyAndDayNew('kdj金叉','20140102');

		// //测试ondup插入
		// $stock = '600123';
		// $dayTime = '20150102';
		// $name = 'macd';
		// $value = -3;
		// $this->updateStockData($stock,$dayTime,$name,$value);
	}

	public function dbtest()
	{
		//测试ondup插入
		$stock = '600123';
		$dayTime = '20150102';
		$name = 'macd';
		$value = 1231;
		$data = array(
			'stock' => $stock,
			'day' => $dayTime,
			'name' => $name,
			'value' => $value,
		);
    	$ret = $this->db->insert('stock_data',$data,$data);
    	$mysql = $this->db->last_query();
    	log_message('debug',"mysql is [$mysql] and ret is [$ret]",true);

    	//测试查询
    	$conds = array(
    		'stock' => $stock,
    	);
    	$query = $this->db->get_where('stock_data', $conds);
    	$row = $query->result_array();
    	print_r($row);

    	//update
    	$conds = array(
    		'stock' => $stock,
    		'day' => $dayTime,
    		'name' => 'macd',
    	);
    	$fields = array(
    		'value' => 222,
    	);
    	$this->db->update('stock_data', $fields, $conds);
       	//测试查询
    	$conds = array(
    		'stock' => $stock,
    	);
    	$query = $this->db->get_where('stock_data', $conds);
    	$row = $query->result_array();
    	print_r($row);
		//$this->db->insert('stock_data',$data);
	}

	public function loadQQData($stock)
	{
		$dir = 'application/data/qq_stock_data/';

		log_message('debug',"update stock info of [$stock]");
		//$stock = 'sh000001';
		$file = $dir."${stock}";
		$content = file_get_contents($file);
		$data = json_decode($content,true);
		foreach($data as $dayInfo)
		{
			$dayTime = $dayInfo['time'];
			unset($dayInfo['time']);
			foreach($dayInfo as $key=>$value)
			{
				if(!empty($key))
				{
					$this->updateStockData($stock,$dayTime,$key,$value);
				}
			}
		}
			//print_r($data);
			//break;
		//print_r($ret);
	}

	public function test2()
	{
		$dayTime = "20140102";
		$strategy = "kdj金叉";
		$year = substr($dayTime,0,4);
		$month = substr($dayTime,4,2);
		$day = substr($dayTime,6,2);
	    //$time = "2015年08月01日";
		$time = "{$year}年{$month}月{$day}日";
	    //$strategyStr = "${time}换手率从大到小排名；${time}涨跌幅从大到小排名；${time}量比从大到小排名；${time}A股流通市值从大到小排名；${time}A股总市值从大到小排名；";
	    $strategyStr = $time.$strategy."；";
	    $page = 1;
	    

		$_req = array(
			'strategy' => $strategy, 
			'day' => $dayTime, 
			'page' => $page,
		);
		$this->db->where('strategy',$strategy)->where('day',$dayTime)->where('page',$page);
		$this->db->limit(5);
		//$query = $this->db->get('stock')->where('strategy',$strategyStr);
		$query = $this->db->get('stock');
		foreach ($query->result() as $row)
		{
			print_r($row);
		    //echo $row['title'];
		}
		//print_r($ret);
		//$timeList = $this->getMarketTimeList();
		//file_put_contents("application/data/timeList.conf",implode("\n", $timeList));
//		log_message('debug','abcd',true);
		// $dayTime = "20150801";
		// $strategy = "kdj金叉";
		// $this->queryByStrategyAndDay($strategy,$dayTime);
		// $code = 'sh600062';
		// $ret = $this->getStockData($code);
		// print_r($ret);

	    //print_r($codeInfoList);
	}



}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */