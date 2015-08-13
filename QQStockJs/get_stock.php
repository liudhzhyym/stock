<?php

	function get_data($url)
	{
		$data = file_get_contents($url);

		$arr = explode('\n\\', $data);
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

	function get_stock_data($code,$start_time=null,$end_time=null)
	{
		//$url = 'http://data.gtimg.cn/flashdata/hushen/daily/14/sh600062.js';
		$url = "http://data.gtimg.cn/flashdata/hushen/daily/14/${code}.js";
		$data_14 = get_data($url);
		$url = "http://data.gtimg.cn/flashdata/hushen/daily/15/${code}.js";
		$data_15 = get_data($url);	
		$all = array_merge($data_14,$data_15);
		$stock_data = array();
		foreach($all as $value)
		{
			$value[0] = '20'.$value[0];
			if($start_time!=null&&$end_time!=null)
			{
				if($value[0]>=$start_time&&$value[0]<=$end_time)
				{
					$stock_data[] = $value;
				}
			}
			else
			{
				$stock_data[] = $value;
			}
		}
		return $stock_data;
	}

	function save_stock_data($filename,$str)
	{
		//return "aaaaaaa";
		file_put_contents($filename, $str);
		return;
		$stock_data = json_decode($str,true);
		//le_put_contents($filename, var_export($stock_data,true));
		$data_str_arr = array();
		$key_arr = array_keys($stock_data[0]);
		foreach($stock_data as $value)
		{
			$temp_arr = array_values($value);
			$temp_str = implode(',', $temp_arr);
			$data_str_arr[] = $temp_str;
		}
		$data_str = implode("\n", $data_str_arr);
		file_put_contents($filename, $data_str);
		//获取键值
		$key_str = implode("\r\n", $key_arr);
		file_put_contents('key', $key_str);
	}

	//$ret = get_stock_data('sh201003');
	//print_r($ret);
	//return;

	// $code = 'sh600062';
	// $start_time = '20141028';
	// $end_time = '20150502';
	//echo json_encode($_POST);
	//$_POST['type'] = 'get_stock_list';
	$type = $_POST['type'];
	//$type = 'get_stock_list';
	if($type == 'get_stock_data')
	{
		$code = $_POST['code'];
		$start_time = $_POST['start_time'];
		$end_time = $_POST['end_time'];
		$stock_data = get_stock_data($code,$start_time,$end_time);
		//file_put_contents($code, $code);
		echo json_encode($stock_data);

	}
	else if($type == 'save_stock_data')
	{
		$code = $_POST['code'];
		$stock_data = $_POST['stock_data'];
		save_stock_data('data/'.$code, $stock_data);
		$ret = array(
			'error_code' => 0,
		);
		echo json_encode($ret);
	}
	else if($type == 'get_stock_list')
	{
		$content = file_get_contents("list.conf");
		$listArr = explode("\n", $content);
		$codeList = array();
		foreach($listArr as $value)
		{
			$value = trim($value);
			if(!empty($value))
			{
				$codeList[] = $value;
			}
		}
        //print_r($listArr);
        echo json_encode($codeList);
	}