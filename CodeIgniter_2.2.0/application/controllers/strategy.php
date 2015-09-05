<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Strategy extends MY_Controller {


    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');

    }

    public function checkStock($code,$time,$day)
    {
   	    $res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );

       	$conds = array(
    		'code' => $code,
    		'firstDay' => $time,
    		'keepDay' => $day,
    	);
    	$query = $this->db->get_where('income', $conds);
    	$cnt = $query->num_rows();
    	$stockData = array();
    	foreach ($query->result_array() as $row)
    	{
    		$res['data'] = $row;
    	}
    	if(empty($res['data']))
    	{
    		$res['errorCode'] = -1;
    	}
    	log_message("debug","day=[$time], code=[$code] ,day = [$day], res is [".json_encode($res));
    	return $res;
    }

	// 与问财的回测策略结果进行比较，确认是否回测结果进行匹配
	public function compare()
	{
	    $day = 10;
	    $file = 'application/data/kdj_result.conf';
	    $content = file_get_contents($file);
	    $lineArr = $this->getArrayFromString($content,"\n");
	    $strategyList = array();
	    foreach($lineArr as $line)
	    {
	        $info = $this->getArrayFromString($line,"\t");
	        $code = $info[0];
	        if(strpos($code,"0")==0)
	        {
	        	$code = "sz".$code;
	        }
	        else if(strpos($code,"6")==0)
	        {
	        	$code = "sh".$code;
	        }
	        //echo $code."\n";
	        $volPercent = floatval($info[6]);
	        $dayTime = str_replace("-", "", $info[2]);
	        $strategyList[$dayTime][] = $code;
	        $ret = $this->checkStock($code,$dayTime,$day);
	        $volDiff = $ret['data']['volPercent'] - $volPercent;
	        log_message("debug","day=[$dayTime], code=[$code] ,volPercent = [$volPercent], volDiff = [$volDiff]",true);
	    }
	    $this->computeAverageIncomeByStrategy($strategyList,$day,-1);
	}

	public function computeAverageIncomeByStrategy($stockList,$day,$stockCount=-1)
	{
		$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
	    $result = array();
	    foreach($stockList as $dayTime=>$list)
	    {
	        foreach($list as $code)
	        {
	            $ret = $this->checkStock($code,$dayTime,$day);
	            if($ret['errorCode']==0)
	            {
	                $result[$dayTime][$code] = $ret['data']['volPercent'];                        
	            }            
	        }
	        $this->checkMem();
	    }
	    // print_r($result);
	    // return;

	    $averageResult = array();
	    $chooseResult = array();
	    foreach($result as $dayTime=>$incomeList)
	    {
	        $cnt = count($incomeList);
	        if($cnt>0)
	        {
	            $averageResult[$dayTime] = round(array_sum(array_values($incomeList))/$cnt,2);
	            if($stockCount>0)
	            {
	                shuffle($incomeList);
	                $chooseList = array_slice($incomeList, 0,$stockCount);
	                $chooseResult[$dayTime] = round(array_sum(array_values($chooseList))/count($chooseList),2);
	            }
	            else
	            {
	            	$chooseResult[$dayTime] = $averageResult[$dayTime];                 
	            }
	        }

	        log_message("debug","day income of [$dayTime] result is [".json_encode($incomeList)."] and ret is [".$averageResult[$dayTime]);
	    }
	    $data = array(
	    	'all' => $result,
	        'averageResult' => $averageResult,
	        'chooseResult' => $chooseResult,
	    );
	    $res['data'] = $data;
	    log_message("debug","computeAverageIncomeByStrategy result is [".var_export($data,true),true);
	    return $res;
	}

	public function getStockListByConds($conds)
	{
		$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
    	$query = $this->db->get_where('stock_data', $conds);
    	$cnt = $query->num_rows();
    	$stockList = array();
    	foreach ($query->result_array() as $row)
    	{
    		if(!empty($row['stock']))
    		{
    			$stockList[] = $row['stock'];
    		}
    		
    	}
    	$res['data'] = $stockList;
    	return $res;
	}

	public function generateList($strategyList,$indexList)
	{
		
	}

	public function getCombineList()
	{
		$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );

		$strategyList = array(
			'boll_break_through' => 1,
			'kdj_gc' => 1,
			'macd_gc' => 1,
			'macd_buy_signal' => 1,
			'cci_buy_signal' => 1,
			'bias_buy_signal' => 1,
			'wr_oversold' => 1,
			'asi' => 1,
			'max_60' => 1,
			'up_5_day' => 1,
			'up_10_day' => 1,
			'long_array' => 1,
			'5day_gt_10day' => 1,
			'5day_eq_10day' => 1,
			'5day_eq_20day' => 1,
			'5day_through_20day' => 1,
			'10day_gt_30day' => 1,
			'10day_gt_20day' => 1,
			'10day_up' => 1,
			'20day_up' => 1,
			'price_amount_up' => 1,
		);

		$list = array_keys($strategyList);
		$combineList = array();
		//单个组合
		foreach($list as $strategyName)
		{
			$temp = array();
			$temp[$strategyName] = 1;
			$combineList[] = $temp;
		}

		//两个个组合
		$cnt = count($list);
		for($i=0;$i<$cnt-1;$i++)
		{
			for($j=$i+1;$j<$cnt;$j++)
			{
				$temp = array();
				$temp[$list[$i]] = 1;
				$temp[$list[$j]] = 1;
				$combineList[] = $temp;
			}
		}
		$res['data'] = $combineList;
		log_message("debug","getCombineList result is [".var_export($res,true));
		return $res;
		//print_r($combineList);
		//排列组合
		// $strategy = array(
		// 	'long_array' => 1,
		// 	//'up_5_day' => 1,
		// );
		// //$this->getStockListByStrategy($strategy);
	}

	public function computeIncomeByIndex($index)
	{
		$ret = $this->getCombineList();
		$strategy = $ret['data'][$index];
		$this->getStockListByStrategy($strategy);
	}

	public function getStockListByStrategy($strategy)
	{
		ini_set('memory_limit', '-1');
		//$strategyName = 'long_array#up_5_day';
		// $strategy = array(
		// 	'long_array' => 1,
		// 	'up_5_day' => 1,
		// );
		$strategyArr = array();
		foreach($strategy as $name=>$value)
		{
			$strategyArr[] = $name."-".$value;
		}
		$strategyName = implode("~", $strategyArr);
		log_message("debug","getStockListByStrategy name is [$strategyName] and strategy is [".var_export($strategy,true));
		//echo $strategyName;
		//return;
		$stockList = array();
		$timeSeries = $this->getMarketTimeList();
		foreach($timeSeries as $index=>$dayTime)
		{
			//$dayTime = '20150513';
			$tempStockList = array();
			$first = true;
			foreach($strategy as $name => $value)
			{
				$conds = array(
					'day' => $dayTime,
					'name' => $name,
					//'value' => $value,
				);
				$ret = $this->getStockListByConds($conds);
				//$stockList[$name] = $ret['data'];
				if($first)
				{
					$first = false;
					$tempStockList = $ret['data'];
				}
				$tempStockList = array_intersect($tempStockList,$ret['data']);
			}		
			$tempStockList = array_values($tempStockList);
			log_message("debug","get stockList of [$dayTime] list is [".json_encode($tempStockList));
			$stockList[$dayTime] = $tempStockList;
			// if($index>10)
			// {
			// 	break;
			// }
			//break;
		}

		$chooseList = array();
		// print_r($stockList);
		// return;
		//转化成选股策略表
		foreach($timeSeries as $index=>$dayTime)
		{
			$buyDay = $timeSeries[$index+1];
			if(!empty($stockList[$dayTime]))
			{
				foreach($stockList[$dayTime] as $stock)
				{
					if($this->isStock($stock))
					{
						$chooseList[$buyDay][] = $stock;
					}
				}
			}
		}
	    $smallCount = 20;
	    $day = 10;
	    $allaverageRet = $this->computeAverageIncomeByStrategy($stockList,$day,$smallCount);
	    $this->dumpResult($strategyName,$allaverageRet['data']);
	    $this->generate($strategyName);
		//print_r($chooseList);
	}

	public function getDataMap($file)
	{
		$list = $this->getArrayFromString(file_get_contents($file));
		$dataMap = array();
		foreach($list as $line)
		{
			$temp = explode(" ", $line);
			$day = trim($temp[0]);
			$income = trim($temp[1]);
			if(!empty($day))
			{
				$dataMap[$day] = $income;
			}
		}
		return $dataMap;
	}

	public function generate($strategyName)
	{
		// long_array#up_5_day
		//$file1 = ""
		$data = array(
			'randAll' => $this->getDataMap($this->_resultDir."rand.st.all"),
			'randSmall' => $this->getDataMap($this->_resultDir."rand.st.20"),
			'strategyAll' => $this->getDataMap($this->_resultDir.$strategyName.".all"),
			'strategySmall' => $this->getDataMap($this->_resultDir.$strategyName.".small"),
		);

		$keys = array_keys($data);
		//建立结果集
		$combineRet = array();

		$timeSeries = $this->getMarketTimeList();
		foreach($timeSeries as $index=>$dayTime)
		{
			$flag = true;
			$tempData = array();
			$tempData[] = $dayTime;
			foreach($keys as $keyName)
			{
				if(!isset($data[$keyName][$dayTime]))
				{
					$flag = false;
					break;
				}
				$tempData[] = $data[$keyName][$dayTime];
			}
			if($flag)
			{
				$combineRet[] = implode(" ", $tempData);
			}
		}
		file_put_contents($this->_resultDir.$strategyName.".compare", implode("\n", $combineRet)."\n");
		//print_r(implode("\n", $combineRet)."\n");
		//print_r($combineRet);
	}


	public function dumpResult($name,$result)
	{
	    $allArr = array();
	    $smallArr = array();
	    foreach($result['averageResult'] as $day=>$value)
	    {
	        $allArr[] = "$day $value";
	    }
	    foreach($result['chooseResult'] as $day=>$value)
	    {
	        $smallArr[] = "$day $value";
	    }
	    file_put_contents($this->_resultDir."${name}.all", implode("\n", $allArr)."\n");
	    file_put_contents($this->_resultDir."${name}.small", implode("\n", $smallArr)."\n");
	}

	public function test3()
	{
		set_time_limit(-1);  
		ini_set('memory_limit', '-1');
	    $randResult = array();
	    $kdjResult = array();

	    $day = 10;
	    // 
	    $stockList = array();
	    $allStock = $this->getAllStockList();
	    //随机选股
	    log_message("debug","start to compute randIncome");
	    $timeSeries = $this->getMarketTimeList();
	    $strategyName = 'rand.st';

	    foreach($timeSeries as $index=>$dayTime)
	    {
	        $stockList[$dayTime] = $allStock;
	        //$stockList[$dayTime] = array_slice($allStock,0,50);
	        // if($index>1)
	        // {
	        //     break;
	        // }
	    }

	    $smallCount = 20;
	    $allaverageRet = $this->computeAverageIncomeByStrategy($stockList,$day,$smallCount);
	    $this->dumpResult($strategyName,$allaverageRet['data']);
	    // print_r($allaverageRet);
	    // return;
	    // $randResult = $allaverageRet;
	    // $allArr = array();
	    // $smallArr = array();
	    // foreach($allaverageRet['data']['averageResult'] as $day=>$value)
	    // {
	    //     $allArr[] = "$day $value";
	    // }
	    // foreach($allaverageRet['data']['chooseResult'] as $day=>$value)
	    // {
	    //     $smallArr[] = "$day $value";
	    // }
	    // file_put_contents($this->_resultDir."${strategyName}.all", implode("\n", $allArr)."\n");
	    // file_put_contents($this->_resultDir."${strategyName}.${smallCount}", implode("\n", $smallArr)."\n");

	}

	public function getListOrderByVol($dayTime)
	{
		$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
	    $winCount = 200;
	    $lossCount = 200;
		$mysql = "select * from income where firstDay='{$dayTime}' order by volPercent desc";
		$data = $this->queryDB($mysql);
		$list = array();
		foreach($data as $value)
		{
			$list[] = $value;
		}
		if(!empty($list))
		{
			$res['data']['win'] = array_slice($list, 0, $winCount);
			$res['data']['loss'] = array_slice($list, -$winCount);
		}
		//print_r($res);
		return $res;
		//print_r($list);
	}

	public function getKeys()
	{
		$mysql = "select distinct(name) from stock_data";
		$ret = $this->queryDB($mysql);
		$keyList = array();
		$keyList[] = 'day';
		foreach($ret as $value)
		{
			$keyList[] = $value['name'];
		}
		$keyList[] = 'volPercent';
		$keyList[] = 'type';
		//print_r($keyList);
		return $keyList;
		
	}

	public function getWinLossMap($day,$nextDay)
	{
		log_message("debug","getWinLossMap input is [$day] and nextDay is [$nextDay]");
		$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
		$_listRet = $this->getListOrderByVol($nextDay);
		//print_r($res);
		//return;
		$data = array();
		if(empty($_listRet['data']))
		{
			$res['errorCode'] = -1;
			return $res;
		}
		foreach($_listRet['data'] as $type => $stockList)
		{
			foreach($stockList as $stockData)
			{
				$stock = $stockData['code'];
				$vol = $stockData['volPercent'];
				$ret = $this->getStockDataByDay($stock,$day);
				//print_r($ret);
				if(!empty($ret))
				{
					$typeValue = ($type=='win')?1:-1;
					$ret['volPercent'] = $vol;
					$ret['type'] = $typeValue;
					$ret['day'] = $day;
					$data[] = $ret;					
				}
				//break;
			}
		}
		$dumpData = array();
		$keys = $this->getKeys();
		//$str = '';
		//$str = $str . implode(" ", $keys)."\n";
		$default = array(
			'boll_break_through',
			'kdj_gc',
			'macd_gc',
			'macd_buy_signal',
			'cci_buy_signal',
			'bias_buy_signal',
			'wr_oversold',
			'asi',
			'max_60',
			'up_5_day',
			'up_10_day',
			'long_array',
			'5day_gt_10day',
			'5day_eq_10day',
			'5day_eq_20day',
			'5day_through_20day',
			'10day_gt_30day',
			'10day_gt_20day',
			'10day_up',
			'20day_up',
			'price_amount_up',
			// 'trunover_rate',
			// 'change_rate',
			// 'volume_ratio',
			// 'total_count',
			// 'circulation_count',
			// 'circulation_ratio',
			// 'pe',
			// 'pb',
			// 'main_percent',
		);
		foreach($data as $info)
		{
			$temp = array();
			foreach($keys as $key)
			{
				if(isset($info[$key]))
				{
					$temp[$key] = $info[$key];
				}
				else if(in_array($key, $default))
				{
					$temp[$key] = 0;
				}
				else
				{
					$temp[$key] = 0;
				}
			}
			$dumpData[] = $temp;
		}
		$res['data'] = $dumpData;
		return $res;
		//file_put_contents("application/data/winloss.conf", $str);
	}

	public function test2()
	{
		$day = '20140106';
		set_time_limit(-1);  
		ini_set('memory_limit', '-1');
		// $a=array("a","b","c","d","e","f");
		// print_r(array_slice($a,0,3));
		// print_r(array_slice($a,-3));
		$timeSeries = $this->getMarketTimeList();
		//print_r($time)
		$keys = $this->getKeys();
		$str = '';
		$str = $str . implode(" ", $keys)."\n";
		foreach($timeSeries as $index=>$day)
		{
			if($index<20||$index>100)
			{
				continue;
			}
			if(isset($timeSeries[$index+1]))
			{
				$nextDay = $timeSeries[$index+1];
				$ret = $this->getWinLossMap($day,$nextDay);
				if(!empty($ret['data']))
				{
					foreach($ret['data'] as $value)
					{
						$str = $str . implode(" ", $value)."\n";
					}
					
				}
				//print_r($str);
				//echo $ret;
				//break;
			}
			// if($index>3)
			// {
			// 	break;
			// }
			
		}
		file_put_contents("application/data/winloss.conf", $str);
		//print_r($str);
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */