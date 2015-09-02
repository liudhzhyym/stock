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
        foreach($stockData as $dayTime=>$info)
        {
            if(!isset($info['opening_price'])||!isset($info['closing_price']))
            {
                unset($stockData[$dayTime]);
            }
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
        //解析总股票数
        $totalRule = '/"total":([0-9]+)/';  
        preg_match_all($totalRule,$htmlContent,$result);  
        $total = $result[1][0];
        $rule = '/"token":"([0-9a-z]+)","staticList"/';  
        preg_match_all($rule,$htmlContent,$result);  
        $token = $result[1][0];
        if(empty($token))
        {
            log_message('error', "FAILED! Get token failed!",true);
            return;
        }
        $conds = array(
            'strategy' => $strategy,
            'day' => $dayTime,
        );
        $query = $this->db->get_where('tonghuashun', $conds);
        $dbcnt = $query->num_rows();
        $nowPageCnt = ceil($total/30);
        log_message("debug","token = $token total = [$total], nowPageCnt = [$nowPageCnt], cnt = [$dbcnt], [$strategy] at [$dayTime]");
        if($nowPageCnt <= $dbcnt)
        {
            //数据是完整的，不需要再查询
            log_message('debug', "no need to query this data,  [$strategy] at [$dayTime]");
            return;
        }
        
        $page = 1;
        $codeInfoList = array();
        //return;
        while (true) 
        {
            $apiUrl = "http://www.iwencai.com/stockpick/cache?token={$token}&p={$page}&perpage=30&showType=";
            $_ret = $this->httpCall($apiUrl);
            $retStr = $_ret['data'];
            $apiData = json_decode($retStr,true);
            if(!empty($retStr)&&!empty($apiData['result']))
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
            if(empty($apiData['result'])||$page>100)
            {
                break;
            }
            //$codeInfoList = array_merge($codeInfoList,$apiData['result']);
            $page++;
        } 
        // if(empty($codeInfoList))
        // {
        //  log_message("error","get code list of [$strategy] at [$dayTime] failed!");
        // }
        
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */