<?php

$command=$argv[1];
#$cluster=$argv[2];

if(empty($command))
{
    usage();
}

shell_exec("mkdir -p data/stock_data/");

switch($command)
{
    case "get_stock_data":
        getAllStockData();
        break;  
    case "check_stock":
        $code=$argv[2];
        $time=$argv[3];
        $day=$argv[4];
        checkStock($code,$time,$day);
        break;  
    case "get_stock_by_strategy":
        $strategyName = $argv[2];
        getStockByStrategy($strategyName);
        break;  
    case "check_income_by_strategy":
        $strategyName = $argv[2];
        $ret = checkIncomeByStrategy($strategyName);
        print_r($ret);
        break;  
    case "compare":
        compare();
        break;
    case "compute_win_random":
        computeWinByRandom();
        break;
    case "compute_average_random":
        $count = $argv[2];
        computeAverageRandom($count);
        break;
    default:
        usage();
        break;  
        
}

function usage() 
{
    echo "php parse.php -[h|s|c]\n";
    echo "php parse.php get_stock_data                      : get all stock data \n";
    echo "php parse.php check_stock 300473 20150716 10      : check stock data pain \n";
    echo "php parse.php get_stock_by_strategy kdj002.st     : get_stock_by_strategy \n";
    echo "php parse.php check_income_by_strategy kdj002.st  : check_income_by_strategy \n";
    echo "php parse.php compute_win_random                  : compute_win_random \n";
    echo "php parse.php compare                             : compare \n";
    echo "php parse.php compute_average_random 3            : compute_average_random \n";
    exit(0);
}


function getHtmlByStrategy($strategy,$time)
{
	return file_get_contents('0629kdj.html');
}

function getCodeFromHtml($html)
{
	$rule = '/stockpick\/search\?tid=stockpick&qs=stockpick_diag&ts=1&w=([0-9]+)/';  
    preg_match_all($rule,$html,$result);  
    return $result[1];
}

function getCodeByStrategy($strategyName,$time)
{
	$strategyFile = "strategy/${strategyName}";
	$strategyContent = file_get_contents($strategyFile);
	//替换时间
	$strategyContent = str_replace('${time}', $time, $strategyContent);
	$strategyContent = str_replace("\r\n", "\n", $strategyContent);
	$tempArr = explode("\n", $strategyContent);
	$strategyList = array();
	foreach($tempArr as $value)
	{
		$value = trim($value);
		if(!empty($value))
		{
			$strategyList[] = $value;
		}
	}
	$strategyStr = implode("；", $strategyList);
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
    //echo $url."\n";
	// curl抓取网页
	$htmlContent = file_get_contents($url);
	//$htmlContent = getHtmlByStrategy($strategyStr,$time);
	$codeList = getCodeFromHtml($htmlContent);
    //print_r($codeList);
	return $codeList;
}

function getHistoryCodeByStrategy($strategyName)
{
    shell_exec("mkdir -p result/${strategyName}");
	$days = 365;
	for($index = 0; $index < $days ; $index++)
	{
		$time = date("Ymd",strtotime("-${index} day"));
        $buyDay = $index-1;
		$buyTime = date("Ymd",strtotime("-${buyDay} day"));
        echo "buyTime = ${buyTime}\n";
		$timeC = date("Y年m月d日",strtotime("-${index} day"));
		$codeList = getCodeByStrategy($strategyName,$timeC);
		if(!empty($codeList))
		{
			$str = implode("\n", $codeList);
            $str = $str."\n";
			file_put_contents("result/${strategyName}/${buyTime}", $str);
		    //break;
		}
        $sleepTime = rand(5,10);
        sleep($sleepTime);
	}
}

function callCurl($url)
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

function getStockData($code,$startTime=null,$endTime=null)
{
    $dir = "data/stock_data";
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
	$data_14 = callCurl($url);
	$url = "http://data.gtimg.cn/flashdata/hushen/daily/15/${code}.js";
	$data_15 = callCurl($url);	
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

function getAllStockData()
{
    $listStr = file_get_contents("stock_list");
    $codeList = explode("\n",$listStr);
    foreach($codeList as $code)
    {
        //$code='sh600062';
        //$code='sh600072';
        echo "getStockData of [$code]\n";
        getStockData($code);    
    }
}

function getAllStockList()
{
    $listStr = file_get_contents("stock_list");
    $codeListArr = explode("\n",$listStr);
    $list = array();
    foreach($codeListArr as $codeStr)
    {
        //$code='sh600062';
        //$code='sh600072';
        //echo "getStockData of [$code]\n";
        $code = str_replace(array("sh","sz"), "", $codeStr);
        if(!empty($code))
        {
            $list[] = $code;
        }
    }
    return $list;
}

function checkStock($code,$time,$day)
{
    $res = array(
        'errorCode' => 0,
        'errorMsg' => 'ok',
        'data' => array(),
    );
    $stock = getStockData("sz${code}"); 
    if(empty($stock))
    {
        $stock = getStockData("sh${code}"); 
    }
    $matchStockData = array();
    if($stock[0][0]>$time)
    {
        return;
    }
    foreach($stock as $value)
    {
        if($value[0]>=$time)
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
    $startPrice = $first[1];
    //开盘直接涨停的，开盘=收盘
    if(empty($startPrice)||$startPrice<3||$first[1]==$first[2])
    {
        return;
    }
    //$endPrice = $last[2];
    //$volPercent = round(($endPrice - $startPrice)*100/$startPrice,2);
    // 当收益率≤ - 10 %时止损
    $max = 0;
    for($index=0;$index<min($cnt,$day);$index++)
    {
        $last = $matchStockData[$index];
        $endPrice = $last[2];
        $volPercent = round(($endPrice - $startPrice)*100/$startPrice,2);
        echo "startPrice = $startPrice ,endPrice = $endPrice\n";
        if($volPercent<-10)
        {
            // 当收益率≤ - 10 %时止损
            //$volPercent = -10;
            //break;
        }
    }
    
    //print_r($last);
    if($volPercent>100)
    {
        return;
    }

    $data = array(
        'startPrice' => $startPrice,
        'endPrice' => $endPrice,
        'volPercent' => $volPercent,
    );
    $res['data'] = $data;
    print_r($res);
    //echo "vol percent of [$code] at [$time] and [$day] days is [${volPercent}%],startPrice=[$startPrice] \n";
    return $res;
}

function getStockByStrategy($strategyName)
{
    getHistoryCodeByStrategy($strategyName);
//    getCodeByStrategy($strategyName,'2015年06月29日');    
}

function getArrayFromString($content,$split="\n")
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

function checkIncomeByStrategy($strategyName)
{
    $maxCount = 20;
    $day = 10;
    $dir = "result/${strategyName}";
    $fileListStr = shell_exec("ls $dir | sort -n  ");
    $fileList = getArrayFromString($fileListStr);
    $strategyList = array();
    $dayIndex = 0;
    $sampleStrategyList = array();
    $sampleDay = 10;
    foreach($fileList as $dayTime)
    {
        
        $codeListStr = file_get_contents($dir."/".$dayTime);
        $codeList = getArrayFromString($codeListStr);
        //shuffle($codeList);
        $codeList = array_slice($codeList,0,2);
        //选股数小于20，忽略
        //if(count($codeList)<$maxCount)
        if($dayIndex%$sampleDay==0)
        {
            $strategyList[$dayTime] = $codeList;
        }
        $dayIndex++;
        //print_r($codeList);
    }
    //print_r($strategyList);
    $ret = computeWin($strategyList,$day);
    return $ret;
    //print_r($ret);
}

function getTimeSeries($period)
{
    $series = array();
    $day = 365;
    for($index=0;$index<$day;$index+=$period)
    {
        $series[] = date("Ymd",strtotime("-${index} day"));
    }
}

function computeWinByRandom()
{
    $day = 10;
    $maxCount = 20;
    $stockList = getAllStockList();
    $stockCnt = count($stockList);
    //print_r($stockList);
    $eachDayCount = 2;
    $dir = "result/kdj002.st";
    $fileListStr = shell_exec("ls $dir | sort -n ");
    $fileList = getArrayFromString($fileListStr);
    $codeDatadir = "data/stock_data/";
    $dayIndex = 0;
    $sampleStrateguList = array();
    foreach($fileList as $dayTime)
    {
        $stockArr = array();
        for($index=0;count($stockArr)<$eachDayCount;$index++)
        {

            $randIndex=rand(1,$stockCnt-1);

            $code = $stockList[$randIndex];
            //去除创业板
            if(strpos('sh'.$code,'sh3') === false&&(file_exists($codeDatadir."sh".$code)||file_exists($codeDatadir."sz".$code)))
            {
                $stockArr[] = $code;
            }
        }
        $codeList = $stockArr;
        //选股数小于20，忽略
        if(count($codeList)<$maxCount)
        {
            $strategyList[$dayTime] = $codeList;
        }
        if($dayIndex%$day==0)
        {
            $sampleStrateguList[$dayTime] = $codeList;
        }
        $dayIndex++;
        //print_r($codeList);
    } 
    $ret = computeWin($sampleStrateguList,$day);
    return $ret;
    //print_r($ret);
    //print_r($sampleStrateguList);
}

function computeAverageRandom($count=1)
{
    $result = array();
    for($i=0;$i<$count;$i++)
    {
        //$ret = computeWinByRandom();
        $ret = checkIncomeByStrategy('kdj002.st');
        print_r($ret);
        foreach($ret as $key=>$value)
        {
            $result[$key]+=$value;
        }
    }
    foreach($result as $key=>&$value)
    {
        $value=$value/$count;
    }
    print_r($result);
}

function computeWin($strategy,$day)
{
    $deal = array(
        'total' => 0,
        'win' => 0,
        'loss' => 0,
        'ratio' => 0,
        'winTotal' => 0,
        // 累积收益
        'winTotalAccumulation' => 1,
    );

    foreach($strategy as $dayTime=>$list)
    {
        $cnt = count($list);
        $base = $deal['winTotalAccumulation'];
        $tempWin = 0;
        foreach($list as $code)
        {
            $ret = checkStock($code,$dayTime,$day);
            if($ret['data']['startPrice']>0)
            {
                if($ret['data']['volPercent']>=0)
                {
                    $deal['win']++;
                }
                else
                {
                    $deal['loss']++;
                } 
                $deal['winTotal'] += $ret['data']['volPercent'];
                $tempWin += $ret['data']['volPercent'];
                //$deal['winTotalAccumulation'] *= (100+$ret['data']['volPercent'])/100;
                $deal['total']++;                           
            }
        }
        if($cnt>0)
        {
            $deal['winTotalAccumulation'] *= (100+$tempWin/$cnt)/100;
        }
        
    }
    if($deal['total']>0)
    {
        $deal['ratio'] = round(100*$deal['win']/$deal['total'],2);
        $deal['average'] = $deal['winTotal']/$deal['total'];
    }
    return $deal;
}

// 与问财的回测策略结果进行比较，确认是否回测结果进行匹配
function compare()
{
    $day = 10;
    $file = 'data/kdj002_result';
    $content = file_get_contents($file);
    $lineArr = getArrayFromString($content,"\n");
    $deal = array(
        'total' => 0,
        'win' => 0,
        'loss' => 0,
        'winTotal' => 0,
        'winTotalAccumulation' => 1,
    );
    $strategyList = array();
    foreach($lineArr as $line)
    {
        $info = getArrayFromString($line,"\t");
        $code = $info[0];
        $startPrice = $info[4];
        $endPrice = $info[5];
        $dayTime = str_replace("-", "", $info[2]);
        $ret = checkStock($code,$dayTime,$day);
        $strategyList[$dayTime][] = $code; 
        // if($ret['data']['volPercent']>=0)
        // {
        //     $deal['win']++;
        // }
        // $deal['winTotal'] += $ret['data']['volPercent'];
        // $deal['winTotalAccumulation'] *= (100+$ret['data']['volPercent'])/100;
        // $deal['total']++;
        $startPriceDiff = $startPrice-$ret['data']['startPrice'];
        $endPriceDiff = $endPrice - $ret['data']['endPrice'];
        echo "day=[$dayTime], code=[$code] , startPriceDiff = [$startPriceDiff], endPriceDiff = [$endPriceDiff]\n";
    }
    $ret = computeWin($strategyList,$day);
    print_r($ret);
}




// $content = file_get_contents('0629kdj.html');
// $codeList = getCodeFromHtml($content);
// print_r($codeList);
// $strategyName = 'kdj001.st';
// // $code = getCodeByStrategy($strategyName,'2015年6月29日');
// $code = getHistoryCodeByStrategy($strategyName);
// print_r($code);
	// stockpick/search?tid=stockpick&qs=stockpick_diag&ts=1&w=
	







?>
