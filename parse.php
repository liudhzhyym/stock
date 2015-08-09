<?php

$command=$argv[1];
#$cluster=$argv[2];

if(empty($command))
{
    usage();
}

shell_exec("mkdir -p data/stock_data/ data/result data/income");

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
    case "test":
        test();
        break;
    default:
        usage();
        break;  
        
}

function usage() 
{
    echo "php parse.php -[h|s|c]\n";
    echo "php parse.php get_stock_data                      : get all stock data \n";
    echo "php parse.php check_stock sz300473 20150716 10      : check stock data pain \n";
    echo "php parse.php get_stock_by_strategy kdj002.st     : get_stock_by_strategy \n";
    echo "php parse.php check_income_by_strategy kdj002.st  : check_income_by_strategy \n";
    echo "php parse.php compute_win_random                  : compute_win_random \n";
    echo "php parse.php compare                             : compare \n";
    echo "php parse.php compute_average_random 3            : compute_average_random \n";
    exit(0);
}


function writeLog($log,$print=false)
{
    $logTime = date("YmdH",time());
    $time = date("Y-m-d H:i:s",time());
    $logStr = "${time} DEBUG $log\n";
    $logFile = "log/stock.{$logTime}";
    $fp = fopen($logFile, "a"); 
    fwrite($fp,$logStr); 
    if($print)
    {
        echo $logStr;
    }
}

function test()
{
    //匹配
    // $code1 = getArrayFromString(file_get_contents("data/result/kdj002-1.st/20140102"));
    // $code2 = getArrayFromString(file_get_contents("data/result/kdj002-2.st/20140102"));
    // $code3 = getArrayFromString(file_get_contents("data/result/kdj002-3.st/20140102"));
    // $code = array_intersect($code1,$code2,$code3);
    // $code = array_values($code);
    // print_r($code);
    //print_r($code2);
    //print_r($code3);
    //getCodeByStrategy("kdj001.st","20140102");

    // $allStockList = getAllStockList();
    // $shangzheng = getStockData("sh000001");
    // $stockList = array();
    // foreach($shangzheng as $value)
    // {
    //     $stockList[$value[0]] = $allStockList;
    // }
    // $day = 10;
    // $startDay = '20140101';
    // getTradingStrategy($stockList,$day,$startDay);

    // test computeAverageIncomeByStrategy

    // $stockList = array(
    //     '20150624' => array('002415'),
    //     '20150625' => array('002252'),
    // );

    //print_r($stockList);
    // computeAverageIncomeByStrategyTest();
    // return;
    //$ret = getCodeByStrategy("001.st","2015年08月07日");
    //getHistoryCodeByStrategy("001.st");
    //print_r($ret);
    $strategyListStr = shell_exec("cd strategy && ls 00*");
    writeLog("get strategyList ret is [$strategyListStr]");
    $listArr = explode("\n", $strategyListStr);
    foreach($listArr as $index=>$fileName)
    {
        if(!empty($fileName))
        {
            getHistoryCodeByStrategy($fileName);
            //echo $fileName."\n";
            //file_put_contents("strategy/00${index}.st", $line);
        }
    }    
    // $listStr = file_get_contents("allStrategy");
    // $listArr = explode("\n", $listStr);
    // foreach($listArr as $index=>$line)
    // {
    //     if(!empty($line))
    //     {
    //         file_put_contents("strategy/00${index}.st", $line);
    //     }
    // }
}


function computeAverageIncomeByStrategyTest()
{

    $randResult = array();
    $kdjResult = array();

    $day = 10;
    // 
    $stockList = array();
    $allStock = getAllStockList();
    // print_r($allStock);
    // return;
    //随机选股
    writeLog("start to compute randIncome");
    $timeSeries = getMarketTimeList();

    $strategyName = 'rand.st';
    $strategyDir = "data/result/$strategyName/";
    //$stockList['20140122'] = $allStock;
    //$stockList['20150513'] = $allStock;
    //$stockList['20150709'] = $allStock;
    foreach($timeSeries as $index=>$dayTime)
    {
        $stockList[$dayTime] = $allStock;
        // if($index>1)
        // {
        //     break;
        // }
    }
    $smallCount = 20;
    $allaverageRet = computeAverageIncomeByStrategy($stockList,$day,$smallCount);
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
    file_put_contents("data/income/${strategyName}.all", implode("\n", $allArr));
    file_put_contents("data/income/${strategyName}.${smallCount}", implode("\n", $smallArr));
    //return;

    //策略选股计算
    writeLog("start to compute kdj002 income");
    $strategyName = 'kdj002.st';
    $strategyDir = "data/result/$strategyName/";
    foreach($timeSeries as $index=>$dayTime)
    {
        $file = $strategyDir.$dayTime;
        if(file_exists($file))
        {
            $strategyCodeListStr = file_get_contents($file);
            $strategyCodeList = getArrayFromString($strategyCodeListStr);
            $buyDay = $timeSeries[$index+1];
            if(!empty($strategyCodeList)&&!empty($buyDay))
            {
                $stockList[$buyDay] = $strategyCodeList;
            }            
        }

    }
    writeLog("get trade list of [$strategyName] ret is [".var_export($stockList,true));
    $smallCount = 2;
    $allaverageRet = computeAverageIncomeByStrategy($stockList,$day,$smallCount);
    $kdjResult = $allaverageRet;
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
    file_put_contents("data/income/${strategyName}.all", implode("\n", $allArr));
    file_put_contents("data/income/${strategyName}.${smallCount}", implode("\n", $smallArr));
    //file_put_contents("data/income/$strategyName", $resultStr);   

    //合并成一个文件
    $tempArr = array();
    writeLog("get kdjResult result is [".var_export($kdjResult,true)."] and randResult is [".var_export($randResult,true),true);
    foreach($kdjResult['averageResult'] as $day=>$value)
    {
        if(isset($randResult['averageResult'][$day]))
        {
            // 随机-所有均值，随机-20只股票，kdj 所有均值，kdj-2只均值
            $tempArr[] = "$day ".$randResult['averageResult'][$day]." ".$randResult['chooseResult'][$day]." ".$kdjResult['averageResult'][$day]." ".$kdjResult['chooseResult'][$day];
        }
    }
    writeLog("get all result is [".var_export($tempArr,true),true);
    file_put_contents("data/income/all", implode("\n", $tempArr)."\n");
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

function saveBuySignal($buySignal,$nowSignal)
{
    $dir = "data/signal/";
    $buySignalFile = $dir."buy";
    $nowSignalFile = $dir."now";
    shell_exec("mkdir -p $dir");
    $buyArr = getArrayFromString(file_get_contents($buySignalFile));
    $nowArr = getArrayFromString(file_get_contents($nowSignalFile));
    foreach($buySignal as $value)
    {
        if(!empty($value)&&!in_array($value, $buyArr))
        {
            $buyArr[] = $value;
        }
    }
    foreach($nowSignal as $value)
    {
        if(!empty($value)&&!in_array($value, $nowArr))
        {
            $nowArr[] = $value;
        }
    } 
    file_put_contents($buySignalFile, implode("\n", $buyArr));
    file_put_contents($nowSignalFile, implode("\n", $nowArr));
}

function getCodeByStrategy($strategyName,$time)
{
    writeLog("getCodeByStrategy of [$strategyName] at time [$time]");
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
    writeLog("url = $url ");
	// curl抓取网页
	$htmlContent = file_get_contents($url);
    $rule = '/"token":"([0-9a-z]+)","staticList"/';  
    preg_match_all($rule,$htmlContent,$result);  
    $token = $result[1][0];
    if(empty($token))
    {
        echo "FAILED! Get token failed!\n";
        return;
    }
    $page = 1;
    $codeList = array();
    $codeInfoList = array();
    $buySignal = array();
    $nowSignal = array();
    $buysignalIndex = -1;
    $zxstIndex = -1;
    while (true) 
    {
        $apiUrl = "http://www.iwencai.com/stockpick/cache?token={$token}&p={$page}&perpage=30&showType=";
        //echo $apiUrl;
        $apiRet = file_get_contents($apiUrl);
        $apiData = (array)json_decode($apiRet);
        //break;
        if(empty($apiData['result'])||$page>100)
        {
            break;
        }
        $oriIndexID = $apiData['oriIndexID'];
        foreach($oriIndexID as $key => $value)
        {
            if($value == "web:buysignal")
            {
                $buysignalIndex = $key;
            }
            if($value == "web:zxst")
            {
                $zxstIndex = $key;
            }
        }
        // print_r($oriIndexID);
        // return;
        $codeInfoList = array_merge($codeInfoList,$apiData['result']);
        $page++;
    }
    foreach($codeInfoList as $info)
    {
        $str = strtolower($info[0]);
        if(!empty($str))
        {
            $tempArr = explode(".", $str);
            $code = $tempArr[1].$tempArr[0];
            if(strpos($code, "3")==2)
            {
                continue;
                // 过滤掉创业板
            }

            $codeList[] = $code;

            //if(strpos(haystack, needle))
            if($buysignalIndex>0)
            {
                $signArr = explode("||", $info[$buysignalIndex]);
                foreach($signArr as $sign)
                {
                    if(!empty($sign)&&$sign!="--"&&!in_array($sign, $buySignal))
                    {
                        $buySignal[] = $sign;
                    }
                }
            }
            if($zxstIndex>0)
            {
                $nowArr = explode("||", $info[$zxstIndex]);
                foreach($nowArr as $sign)
                {
                    if(!empty($sign)&&$sign!="--"&&!in_array($sign, $nowSignal))
                    {
                        $nowSignal[] = $sign;
                    }
                }         
            }
            
          
        }

    }
    writeLog("saveBuySignal of [$strategyName] at time [$time] ret is [".json_encode($buySignal));
    saveBuySignal($buySignal,$nowSignal);
	//$htmlContent = getHtmlByStrategy($strategyStr,$time);
	//$codeList = getCodeFromHtml($htmlContent);
    // print_r($buySignal);
    // print_r($nowSignal);
    writeLog("get code list of [$strategyName] at time [$time] ret is [".var_export($codeList,true));
	return $codeList;
}

//获取上证指数的开盘时间序列
function getMarketTimeList()
{
    $data = getStockData("sh000001");
    $timeArr = array();
    foreach($data as $info)
    {
        // if($info[0]<'20150615')
        // {
        //     continue;
        // }
        $timeArr[] = $info[0];
    }
    return $timeArr;
}

function getHistoryCodeByStrategy($strategyName)
{
    shell_exec("mkdir -p data/result/${strategyName}");
    $list = getMarketTimeList();
	//for($index = 0; $index < $days ; $index++)
    foreach($list as $time)
	{
        //$time = "20150805";
        $year = substr($time,0,4);
        $month = substr($time,4,2);
        $day = substr($time,6,2);
        $dayTime = "{$year}年{$month}月{$day}日";
        writeLog("strategyName = [$strategyName], time = ${time} dayTime = ${dayTime}",true);
		$codeList = getCodeByStrategy($strategyName,$dayTime);
		if(!empty($codeList))
		{
			$str = implode("\n", $codeList);
            //$str = $str."\n";
			file_put_contents("data/result/${strategyName}/${time}", $str);
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
    foreach($codeListArr as $code)
    {
        //$code='sh600062';
        //$code='sh600072';
        //echo "getStockData of [$code]\n";
        //$code = str_replace(array("sh","sz"), "", $code);
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
    // 如果是创业板，略过
    if(preg_match("/^(sh|sz)3/",$code)==1||preg_match("/^3/",$code)==1)
    {
        writeLog("code [$code] is gem board,skip it!");
        return;
    }
    $test_re="/^[0-9]+/";

    if(preg_match($test_re,$code)==1)
    {
        $stock = getStockData("sz${code}");
        if(empty($stock))
        {
            $stock = getStockData("sh${code}");
        } 
    }
    else
    {
        $stock = getStockData($code); 
    }
    
    $matchStockData = array();
    if(empty($stock)||$stock[0][0]>$time)
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
    if(empty($startPrice)||$startPrice<3||$startPrice>200||$first[1]==$first[2])
    {
        writeLog("code = [$code] startPrice = $startPrice skip it!");
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
        //writeLog("code = [$code], startPrice = $startPrice ,endPrice = $endPrice");
        if($volPercent<-10)
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
        'startDay' => $first[0],
        'startPrice' => $startPrice,
        'endPrice' => $endPrice,
        'volPercent' => $volPercent,
    );
    $res['data'] = $data;
    writeLog("res data is [".json_encode($res));
    //echo "vol percent of [$code] at [$time] and [$day] days is [${volPercent}%],startPrice=[$startPrice] \n";
    return $res;
}

function getStockByStrategy($strategyName)
{
    getHistoryCodeByStrategy($strategyName);
    //$list = getCodeByStrategy($strategyName,'2015年06月29日');    
    //print_r($list);
//    $list = getCodeByStrategy($strategyName,'20150629');    
//    print_r($list);
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
    writeLog("checkIncomeByStrategy of strategyList is ".var_export($strategyList,true),true);
    //print_r($strategyList);
    $ret = computeWin($strategyList,$day);
    writeLog("checkIncomeByStrategy of ret is ".var_export($ret,true),true);
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
function computeAverageIncomeByStrategy($stockList,$day,$stockCount=-1)
{
    $result = array();
    foreach($stockList as $dayTime=>$list)
    {
        foreach($list as $code)
        {
            $ret = checkStock($code,$dayTime,$day);
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
        writeLog("day income of [$dayTime] result is [".json_encode($incomeList)."] and ret is [".$averageResult[$dayTime]);
    }
    $data = array(
        'averageResult' => $averageResult,
        'chooseResult' => $chooseResult,
    );

    writeLog("computeAverageIncomeByStrategy result is [".var_export($data,true),true);
    return $data;
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
        writeLog("day=[$dayTime], code=[$code] , startPriceDiff = [$startPriceDiff], endPriceDiff = [$endPriceDiff]",true);
    }
    $ret = computeWin($strategyList,$day);
    computeAverageIncomeByStrategy($strategyList,$day);
    //print_r($strategyList);
}


function getTradingStrategy($stockList,$day,$startDay)
{

    print_r($stockList);
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
