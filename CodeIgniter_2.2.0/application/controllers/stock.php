<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function httpCall($url, array $post = array(), array $options = array(), $timeout = 10, $retry = 1) {

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

        log_message("error","httpCall ret is [".json_encode($res));
        return $res;
    }

    public function insert($strategy,$day,$page,$result)
    {
    	$sql = "INSERT INTO `stock` (`strategy`, `day`, `page`, `result`) VALUES (\"$strategy\", \"$day\", $page , '$result') ON DUPLICATE KEY UPDATE `result`= '$result'";
    	//$sql = "INSERT INTO `stock` (`strategy`, `day`, `page`, `result`) VALUES (\"$strategy\", \"$day\", $page , '$result')";
    	$ret = $this->db->query($sql);
    	log_message('debug', "insert data of [$strategy][$day][$page] , and result is [$ret]");
    }

	public function index()
	{
		//$query = $this->db->query("SELECT * FROM product limit 3");
		//print_r($query->result());
		$data = array(
			'strategy' => '2015年08月01日kdj金叉；',
			'day' => '20150801',
			'page' => 1,
			'result' => '{"title":["\u80a1\u7968\u7b80\u79f0","\u80a1\u7968\u4ee3\u7801","\u6da8\u8dcc\u5e45(%)",',
		);
		//$ret = $this->db->insert('stock', $data); 
		$ret = $this->insert($data['strategy'],$data['day'],$data['page'],$data['result']);
		print_r($ret);
		// $url = 'http://www.cnblogs.com/lida/archive/2011/02/18/1958211.html';
		// $ret = $this->httpCall($url);
		// print_r($ret);
	}

	public function 

	public function test()
	{
//		log_message('debug','abcd',true);
		$dayTime = "20150801";
		$strategy = "kdj金叉";
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
	 	while (true) 
	    {
	        $apiUrl = "http://www.iwencai.com/stockpick/cache?token={$token}&p={$page}&perpage=30&showType=";
	        $_ret = $this->httpCall($apiUrl);
	        if(!empty($_ret['data']))
	        {
	        	$ret = $this->insert($strategy,$dayTime,$page,$_ret['data']);
	        }
	        $apiData = (array)json_decode($_ret['data']);
	        if(empty($apiData['result'])||$page>100)
	        {
	            break;
	        }
	        $codeInfoList = array_merge($codeInfoList,$apiData['result']);
	        $page++;
	        //break;
	    } 
	    //print_r($codeInfoList);
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */