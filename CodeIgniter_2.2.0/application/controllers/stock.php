<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock extends MY_Controller {

	private $_stockDataDir;

	const MIN_PAGE = 10;

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

	private function getStockDataFromQQ($code,$startTime=null,$endTime=null)
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
			$this->getStockDataFromQQ($code);
		}
		//print_r($list);
	}

	//获取上证指数的开盘时间序列
	public function getMarketTimeList()
	{
	    $data = $this->getStockDataFromQQ("sh000001");
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
		$strategyList = $this->getList('strategyList.conf');
		$strategy = $strategyList[$index];
		$this->queryByStrategyAndDay($strategy,$dayTime);
		$this->parseDataByIndexAndDay($strategyIndex,$dayTime);
		$this->checkMem();
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
		log_message('debug', "insert data of [$stock][$dayTime][$name][$value]");
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
		//$strategyList = $this->getList('strategy2load.conf');
		$strategyList = $this->getList('strategyList.conf');
		$strategy = $strategyList[$index];
		//$this->queryByStrategyAndDay($strategy,$dayTime);
		//$this->checkMem();
    	$conds = array(
    		'strategy' => $strategy,
    		'day' => $dayTime,
    	);
    	$query = $this->db->get_where('tonghuashun', $conds);
    	$cnt = $query->num_rows();
    	log_message("debug","cnt is [$cnt],strategy is [$strategy],$dayTime is [$dayTime]");
    	$stockData = array();

    	$allPageStrategyList = array(
			"换手率从大到小排名",
			"涨跌幅从大到小排名",
			"量比从大到小排名",
			"A股流通市值从大到小排名",
			"A股总市值从大到小排名",
			"市盈率(pe)从大到小排名",
			"主力控盘比例从大到小排名",
    	);
    	if(in_array($strategy, $allPageStrategyList))
    	{
			if($cnt<self::MIN_PAGE)
			{
				log_message('error',"data page of [$strategy] [$strategyIndex] [$dayTime] is null,need reload!");
			}    		
    	}


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
    			switch ($strategy) {
    				case 'boll突破中轨':
    					// 0
		    			$name = 'boll_break_through';
		    			$value = 1;
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case 'kdj金叉':
    					// 1
		    			$name = 'kdj_gc';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case 'macd金叉':
	    				//2
		    			$name = 'macd_gc';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case 'macd买入信号':
	    				//3
		    			$name = 'macd_buy_signal';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case 'cci买入信号':
	    				//4
		    			$name = 'cci_buy_signal';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case 'bias买入信号':
	    				//5
		    			$name = 'bias_buy_signal';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case 'w&r超卖':
	    				//6
		    			$name = 'wr_oversold';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case 'asi(asi<30.0)':
	    				//7
		    			$name = 'asi';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case '创60个交易日以来新高':
    					// 8
    					$name = 'max_60';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);	
    					break;
    				case '行情收盘价上穿5日线':
    					// 9
    					$name = 'up_5_day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '行情收盘价上穿10日线':
    					// 10
    					$name = 'up_10_day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '5日线10日线20日线30日线60日线120日线多头排列':
    					// 11
    					$name = 'long_array';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '5日线>10日线':
    					// 12
    					$name = '5day_gt_10day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '5日线粘合10日线':
    					// 13
    					$name = '5day_eq_10day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '5日线粘合20日线':
    					// 14
    					$name = '5day_eq_20day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '5日线上穿20日线':
    					// 15
    					$name = '5day_through_20day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '10日线>30日线':
    					// 16
    					$name = '10day_gt_30day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '10日线>20日线':
    					// 17
    					$name = '10day_gt_20day';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '10日线上移':
    					// 18
    					$name = '10day_up';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '20日线上移':
    					// 19
    					$name = '20day_up';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '价升量涨':
    					// 20
    					$name = 'price_amount_up';
		    			$value = 1;
		    			//print_r($info);
		    			$this->updateStockData($stock,$dayTime,$name,$value);
    					break;
    				case '换手率从大到小排名':
    					// 21
    					$name = 'trunover_rate';
    					$value = floatval($info[4]);
    					if($value>0)
    					{
    						//print_r($info);
    						$this->updateStockData($stock,$dayTime,$name,$value);
    					}
    					
    					break;
    				case '涨跌幅从大到小排名':
    					// 22
    					$name = 'change_rate';
    					$value = trim($info[4]);
    					if($value!="--")
    					{
    						$value = floatval($value);
    						//print_r($info);
    						$this->updateStockData($stock,$dayTime,$name,$value);
    					}
    					break;
    				case '量比从大到小排名':
    					// 23
    					$name = 'volume_ratio';
    					//print_r($info);
    					$value = trim($info[4]);
    					if($value!="--")
    					{
    						$value = floatval($value);
    						//print_r($info);
    						$this->updateStockData($stock,$dayTime,$name,$value);
    					}
    					break;
    				case 'A股流通市值从大到小排名':
    					// 24
    					// 总股本

    					$name = 'total_count';
    					$total_count =  number_format($info[8],'','','');
    					if($total_count>0)
    					{
    						//$value = floatval($value);
    						//print_r($info);
    						$this->updateStockData($stock,$dayTime,$name,$total_count);
    					}
    					//流通市值
    					$name = 'circulation_count';
    					$value =  floatval($info[7]);
    					$circulation_count = $value*100000000;
    					if($circulation_count>0)
    					{
    						
    						//print_r($info);
    						$this->updateStockData($stock,$dayTime,$name,$circulation_count);
    					}
    					if($total_count>0&&$circulation_count>0)
    					{
    						$name = 'circulation_ratio';
    						$value =  $circulation_count/$total_count;
    						if($value<=1)
    						{
    							$this->updateStockData($stock,$dayTime,$name,$value);
    						}
    					}
    					break;
    				case 'A股总市值从大到小排名':
    					// 25
    					//print_r($info);
    					break;
    				case '市盈率(pe)从大到小排名':
    					// 26
    					//市盈率
    					$name = 'pe';
    					$value =  floatval($info[4]);
    					if($value>0)
    					{
    						$this->updateStockData($stock,$dayTime,$name,$value);
    					}
    					//市净率
    					$name = 'pb';
    					$value =  floatval($info[7]);
    					if($value>0)
    					{
    						$this->updateStockData($stock,$dayTime,$name,$value);
    					}
    					break;
    				case '主力控盘比例从大到小排名':
    					// 27
    					$name = 'main_percent';
    					//print_r($info);
    					$value = trim($info[4]);
    					if($value!="--")
    					{
    						$value = floatval($value);
    						//print_r($info);
    						$this->updateStockData($stock,$dayTime,$name,$value);
    					}
    					break;
    				default:
    					# code...
    					break;
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