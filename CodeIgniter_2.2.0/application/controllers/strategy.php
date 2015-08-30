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

	public function getStockListByStrategy($conds)
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

	public function index()
	{
		ini_set('memory_limit', '-1');
		$strategyName = 'long_array#up_5_day';
		$strategy = array(
			'long_array' => 1,
			'up_5_day' => 1,
		);
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
				$ret = $this->getStockListByStrategy($conds);
				//$stockList[$name] = $ret['data'];
				if($first)
				{
					$first = false;
					$tempStockList = $ret['data'];
				}
				$tempStockList = array_intersect($tempStockList,$ret['data']);
			}		
			$tempStockList = array_values($tempStockList);
			log_message("debug","get stockList of [$dayTime] list is [".json_encode($tempStockList),true);
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
		print_r($chooseList);

		// $conds = array(
		// 	'day' => '20150513',
		// 	'name' => 'long_array',
		// 	'value' => 1,
		// );
		// $ret = $this->getStockListByStrategy($conds);
		//print_r($stockList);
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

	public function generate()
	{
		// long_array#up_5_day
		//$file1 = ""
		$data = array(
			'randAll' => $this->getDataMap($this->_resultDir."rand.st.all"),
			'randSmall' => $this->getDataMap($this->_resultDir."rand.st.20"),
			'longArrayAll' => $this->getDataMap($this->_resultDir."long_array#up_5_day.all"),
			'longArraySmall' => $this->getDataMap($this->_resultDir."long_array#up_5_day.small"),
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
		print_r(implode("\n", $combineRet)."\n");
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

	public function test()
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
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */