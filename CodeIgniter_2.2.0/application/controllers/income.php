<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Income extends MY_Controller {


    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');

    }


    public function getStockData($stock)
    {
    	$conds = array(
    		'stock' => $stock,
    	);
    	$query = $this->db->get_where('stock_data', $conds);
    	$cnt = $query->num_rows();
    	$stockData = array();
    	foreach ($query->result_array() as $row)
    	{
    		$day = $row['day'];
    		$stockData[$day][$row['name']] = $row['value'];
    	}
    	return $stockData;
    	//print_r($stockData);
    }

	public function getMarketTimeList()
	{
	    $data = $this->getStockData("sh000001");
	    $timeArr = array();
	    foreach($data as $dayTime=>$info)
	    {
	        $timeArr[] = $dayTime;
	    }
	    sort($timeArr);
	    //print_r($timeArr);
	    return $timeArr;
	}

	public function checkStock($code,$time,$day)
	{
	    $res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
	    // 如果是创业板，略过
	    if(preg_match("/^(sh|sz)3/",$code)==1||preg_match("/^3/",$code)==1)
	    {
	        log_message("error","code [$code] is gem board,skip it!");
	        return;
	    }
	    $test_re="/^[0-9]+/";

	    if(preg_match($test_re,$code)==1)
	    {
	        $stock = $this->getStockData("sz${code}");
	        if(empty($stock))
	        {
	            $stock = $this->getStockData("sh${code}");
	        } 
	    }
	    else
	    {
	        $stock = $this->getStockData($code); 
	    }
	    
	    
	    if(empty($stock))
	    {
	        return;
	    }

	    $matchStockData = array();
	    foreach($stock as $key=>$value)
	    {
	        if($key>=$time)
	        {
	            $matchStockData[] = $value;
	        }
	    }
	    $cnt = count($matchStockData);
	    $first = $matchStockData[0];
	    $dayData = $matchStockData[$day-1];
	    $lastDayData = end($matchStockData);
	    $last = !empty($dayData)?$dayData:$lastDayData;
	    //print_r($first);
	    // 开盘价
	    $startPrice = $first['opening_price'];
	    //开盘直接涨停的，开盘=收盘
	    if(empty($startPrice)||$startPrice<3||$startPrice>200||$first['opening_price']==$first['closing_price'])
	    {
	        log_message("debug","code = [$code] startPrice = closing_price skip it!");
	        return;
	    }
	    //$endPrice = $last[2];
	    //$volPercent = round(($endPrice - $startPrice)*100/$startPrice,2);
	    // 当收益率≤ - 10 %时止损
	    $max = 0;
	    for($index=0;$index<min($cnt,$day);$index++)
	    {
	        $last = $matchStockData[$index];
	        $endPrice = $last['closing_price'];
	        $maxPrice = $last['max_price'];
	        $minPrice = $last['min_price'];
	        $volPercent = round(($endPrice - $startPrice)*100/$startPrice,2);
	        $maxPercent = round(($maxPrice - $startPrice)*100/$startPrice,2);
	        $minPercent = round(($minPrice - $startPrice)*100/$startPrice,2);
	        //writeLog("code = [$code], startPrice = $startPrice ,endPrice = $endPrice");
	        if($minPercent<-10)
	        {
	            // 当收益率≤ - 10 %时止损
	            $volPercent = -10;
	            break;
	        }
	    }
	    
	    //print_r($last);
	    if($volPercent>80)
	    {
	        return;
	    }

	    $data = array(
	        'startDay' => $time,
	        'startPrice' => $startPrice,
	        'endPrice' => $endPrice,
	        'volPercent' => $volPercent,
	    );
	    $res['data'] = $data;
	    log_message("debug","res data is [".json_encode($res));
	    //echo "vol percent of [$code] at [$time] and [$day] days is [${volPercent}%],startPrice=[$startPrice] \n";
	    //print_r($res);
	    return $res;
	}

	/*
	    params:
	        // 股票列表
	        $stockList = array(
	            '20150701' => array('111111','111222'),
	            '20150702' => array('222333','333444'),
	            ......
	        );
	        //持股天数
	        $day = 10;
	        //选股数
	        $stockCount = 2;
	    return:
	        // 单只股票持仓N天的收益
	        $income = array(
	            '20150701' => array(0.11,-0.12),
	            '20150702' => array(-0.1,0.3),
	        );
	*/
	// stockList 股票列表 array(20150701 => array()
	public function computeAverageIncomeByStrategy($stockList,$day,$stockCount=-1)
	{
	    $result = array();
	    foreach($stockList as $dayTime=>$list)
	    {
	        foreach($list as $code)
	        {
	            $ret = $this->checkStock($code,$dayTime,$day);
	            if($ret['data']['startPrice']>0)
	            {
	                $result[$dayTime][] = $ret['data']['volPercent'];                        
	            }            
	        }
	    }

	    $averageResult = array();
	    $chooseResult = array();
	    foreach($result as $dayTime=>$incomeList)
	    {
	        $cnt = count($incomeList);
	        if($cnt>0)
	        {
	            $averageResult[$dayTime] = round(array_sum($incomeList)/$cnt,2);
	            if($stockCount==-1)
	            {
	                $chooseResult[$dayTime] = $averageResult[$dayTime];
	            }
	            else
	            {
	                shuffle($incomeList);
	                $chooseList = array_slice($incomeList, 0,$stockCount);
	                $chooseResult[$dayTime] = round(array_sum($chooseList)/count($chooseList),2);                
	            }

	        }
	        log_message("debug","day income of [$dayTime] result is [".json_encode($incomeList)."] and ret is [".$averageResult[$dayTime]);
	    }
	    $data = array(
	        'averageResult' => $averageResult,
	        'chooseResult' => $chooseResult,
	    );

	    log_message("debug","computeAverageIncomeByStrategy result is [".var_export($data,true),true);
	    return $data;
	}


	public function test()
	{
	    $randResult = array();
	    $kdjResult = array();

	    $day = 10;
	    // 
	    $stockList = array();
	    $allStock = $this->getAllStockList();
	    // print_r($allStock);
	    // return;
	    //随机选股
	    log_message("debug","start to compute randIncome");
	    $timeSeries = $this->getMarketTimeList();
	    $strategyName = 'rand.st';

	    foreach($timeSeries as $index=>$dayTime)
	    {
	    	//$stockList[$dayTime] = array_slice($allStock,0,50);
	        $stockList[$dayTime] = $allStock;
	        if($index>1)
	        {
	            break;
	        }
	    }
	    //print_r($stockList);

	    $smallCount = 20;
	    $allaverageRet = $this->computeAverageIncomeByStrategy($stockList,$day,$smallCount);
	    //print_r($allaverageRet);
	    $randResult = $allaverageRet;
	    $allArr = array();
	    $smallArr = array();
	    foreach($allaverageRet['averageResult'] as $day=>$value)
	    {
	        $allArr[] = "$day $value";
	    }
	    foreach($allaverageRet['chooseResult'] as $day=>$value)
	    {
	        $smallArr[] = "$day $value";
	    }
	    file_put_contents($this->_resultDir."${strategyName}.all", implode("\n", $allArr));
	    file_put_contents($this->_resultDir."${strategyName}.${smallCount}", implode("\n", $smallArr));
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */