<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Spss extends MY_Controller {


    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');

    }

    public function parseFromSpss($content)
    {
    	$res = array(
	        'errorCode' => 0,
	        'errorMsg' => 'ok',
	        'data' => array(),
	    );
	    //D:\mycloud\cloud\stock\compare\
		$fileNameRule = '/FILE="D:\\\mycloud\\\cloud\\\stock\\\compare\\\([^.]+)\.compare"/';  
		//$fileNameRule = '/FILE="D:\\\mycloud/';  
    	preg_match_all($fileNameRule,$content,$result); 
    	$spssResult = array();
    	$file = '';
    	if(!empty($result[1]))
    	{
	    	$file = $result[1][0];
	    	if(!empty($file))
	    	{
	    		$rule = '/\|V[0-9]\|.*/';
	    		preg_match_all($rule,$content,$ret);
	    		if(!empty($ret[0]))
	    		{
	    			foreach($ret[0] as $line)
	    			{
	    				$temp = explode("|", $line);
	    				$key = $temp[1];
	    				if(!empty($key))
	    				{

	    					$info = array(
								'avg' => floatval($temp[2]),
								'N' => floatval($temp[3]),
								'sd' => floatval($temp[4]),
								'sd_err' => floatval($temp[5]),
	    					);
	    					$spssResult['static'][$key] = $info;
	    				}
	    				//print_r($temp);
	    			}
	    		}
	    		//相关系数
	    		$rule = '/\|V[0-9] & V[0-9]\|.*/';
	    		preg_match_all($rule,$content,$ret);
	    		// |V2 & V3|391|.956    |.000|
	    		if(!empty($ret[0]))
	    		{
	    			foreach($ret[0] as $line)
	    			{
	    				$temp = explode("|", $line);
	    			 	$key = $temp[1];
	    				if(!empty($key))
	    				{
	    					$info = array(
								'N' => floatval($temp[2]),
								'cor' => floatval($temp[3]),
								'sig' => floatval($temp[4]),
	    					);
	    					$spssResult['cor'][$key] = $info;
	    				}
	    				//print_r($temp);
	    			}
	    		}

	    		//t检验
	    		$rule = '/\|V[0-9] - V[0-9]\|.*/';
	    		preg_match_all($rule,$content,$ret);
	    		// |V2 & V3|391|.956    |.000|
	    		if(!empty($ret[0]))
	    		{
	    			foreach($ret[0] as $line)
	    			{
	    				$temp = explode("|", $line);
	    			 	$key = $temp[1];
	    				if(!empty($key))
	    				{
	    					$info = array(
								'avg' => floatval($temp[2]),
								'sd' => floatval($temp[3]),
								'sd_err' => floatval($temp[4]),
								'lower' => floatval($temp[5]),
								'upper' => floatval($temp[6]),
								't' => floatval($temp[7]),
								'df' => floatval($temp[8]),
								'sig' => floatval($temp[9]),
	    					);
	    					$spssResult['ttest'][$key] = $info;
	    				}
	    				//print_r($temp);
	    			}
	    		}
	    	}	  
	    	$res['data'] = array(
	    		'file' => $file,
	    		'spss' => $spssResult,
	    	);  		
    	}
    	return $res;
    }

    public function generateSpssScripts()
    {
    	$ttestScripts = 'GET DATA
  /TYPE=TXT
  /FILE="D:\mycloud\cloud\stock\compare\{{%FILE%}}"
  /DELCASE=LINE
  /DELIMITERS=" "
  /ARRANGEMENT=DELIMITED
  /FIRSTCASE=1
  /IMPORTCASE=ALL
  /VARIABLES=
  V1 A8
  V2 F5.2
  V3 F5.2
  V4 F5.2
  V5 F5.2.
EXECUTE.

T-TEST PAIRS=V2 V4 V4 WITH V3 V5 V2 (PAIRED) 
  /CRITERIA=CI(.9500) 
  /MISSING=ANALYSIS.';
		$saveScripts = 'OUTPUT EXPORT
  /CONTENTS  EXPORT=ALL  LAYERS=PRINTSETTING  MODELVIEWS=PRINTSETTING
  /TEXT  DOCUMENTFILE="D:\mycloud\cloud\stock\spss.txt"
     ENCODING=UTF8  NOTESCAPTIONS=YES  SEPARATOR=SPACE
     COLUMNWIDTH=AUTOFIT  ROWBORDER="-"  COLUMNBORDER="|"
     IMAGEFORMAT=JPG
  /JPG  PERCENTSIZE=100  GRAYSCALE=NO.';		
		$listStr = shell_exec("cd application/data/result/ && ls *compare");
		$fileList = $this->getArrayFromString($listStr,"\n");
		$cmd = "";
		foreach($fileList as $index=>$file)
		{
			$temp = str_replace("{{%FILE%}}", $file, $ttestScripts);
			$cmd = $cmd . $temp . "\n";
			// if($index>5)
			// {
			// 	break;
			// }
			//echo $temp;
			//break;
		}
		$cmd = $cmd . $saveScripts."\n";
		file_put_contents("application/data/spssCmd.conf", $cmd);
		//echo $cmd;
		//print_r($fileList);
    }

    public function test()
    {
    	$resultFile = "application/data/spss_result.conf";
		$content = file_get_contents($resultFile);
		$resultArr = explode("GET DATA", $content);
		$spssResult = array();
		foreach($resultArr as $block)
		{
			$ret = $this->parseFromSpss($block);
			if(!empty($ret['data']['spss']))
			{
				$strategy = $ret['data']['file'];
				$data = $ret['data']['spss'];
				$spssResult[$strategy] = $data;
				$data = array(
					'strategy' => $strategy,
					'result' => json_encode($data),
				);
				$this->db->insert('spss',$data,true);
			}
		}
		print_r($spssResult);
    }

    //对要分析的文件进行打包
    public function tarResult()
    {
    	shell_exec("mkdir -p application/data/tmp/ && cp application/data/result/*.compare application/data/tmp/ && cd application/data/tmp/ && tar -cf ../compare.tar *.compare");

    }

    public function getSpssResult()
    {
    	$query = $this->db->get('spss');
    	$cnt = $query->num_rows();
    	$result = array();
    	$str = "";
    	$head = array("name","rand-avg","rand-sd","strategy-avg","strategy-sd","strategy-N","diff-avg","diff-sd","diff-sig","cnt");
    	$str = implode(" ", $head)."\n";
    	foreach ($query->result_array() as $row)
    	{
    		$temp = array();
    		$strategy = $row['strategy'];
    		$temp[] = $strategy;
    		$data = json_decode($row['result'],true);
    		if($data['static']['V2']['N']<50)
    		{
    			log_message("debug","too few data ,skip it [$strategy]");
    			continue;
    		}
    		if(!empty($data['ttest']['V4 - V2']))
    		{
	    		$strategyArr = explode("~", $strategy);
	    		$temp[] = $data['static']['V2']['avg'];
	    		$temp[] = $data['static']['V2']['sd'];
	    		$temp[] = $data['static']['V4']['avg'];
	    		$temp[] = $data['static']['V4']['sd'];
	    		$temp[] = $data['static']['V2']['N'];
	    		//ttest
	    		$temp[] = $data['ttest']['V4 - V2']['avg'];
	    		$temp[] = $data['ttest']['V4 - V2']['sd'];
	    		$temp[] = $data['ttest']['V4 - V2']['sig'];
	    		//策略个数
	    		$temp[] = count($strategyArr);
	    		$result[] =$temp;
	    		$str = $str.implode(" ", $temp)."\n";    			
    		}

    		//$res['data'] = $row;
    		//break;
    	}
    	file_put_contents("application/data/spssRet.conf", $str);
    	//print_r($str);
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */