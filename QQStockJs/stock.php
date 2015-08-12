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

	//code = 'sh600062'
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

	//函数k
	function get_ema($shoupan_price,$day)
	{
		$len = count($shoupan_price);
		$ema = array();
		//第一个为0
		$ema[] = $shoupan_price[0];
		$v = 2.0/($day+1);
		for($i=1;$i<$len;$i++)
		{
			$ema[$i] = $shoupan_price[$i]*$v+$ema[$i-1]*(1-$v);
		}
		return $ema;
	}

	// 函数A
	function sub($arr1,$arr2)
	{
		$ret = array();
		$len = count($arr1);
		for($i=0;$i<$len;$i++)
		{
			$ret[] = $arr1[$i] - $arr2[$i];
		}
		return $ret;
	}

	// 函数H
	function add($arr1,$arr2)
	{
		$ret = array();
		$len = count($arr1);
		for($i=0;$i<$len;$i++)
		{
			$ret[] = $arr1[$i] + $arr2[$i];
		}
		return $ret;
	}

	//函数T
	function multiply($arr,$data)
	{
		foreach($arr as &$value)
		{
			$value = $value*$data;
		}
		return $arr;
	}

	//函数O
	function divide($arr,$data)
	{
		foreach($arr as &$value)
		{
			$value = $value/$data;
		}
		return $arr;
	}

    // D = function(m, E) {
    //     var g = m.length,
    //         d = [];
    //     for (var l = 0; l < g; l++) {
    //         d.push(m[l] instanceof Array ? m[l].length : -1)
    //     }
    //     var U = [];
    //     for (var l = 0, f = Math.max.apply(null, d); l < f; l++) {
    //         var v = [];
    //         for (var p = 0; p < g; p++) {
    //             v.push(d[p] >= 0 ? m[p][l] : m[p])
    //         }
    //         U[l] = E.apply(U, v)
    //     }
    //     return U
    // },

    function c_abs($input)
    {
    	return abs($input[0]);
    }

    function c_max($input)
    {
    	$max = $input[0];
    	foreach($input as $value)
    	{
    		if($value==NAN||$value>$max)
    		{
    			$max = $value;
    		}
    	}
		return $max;    	
    }

    function c_divide($a,$b)
    {
    	return $a/$b;
    }

	//函数D
	function D($input_arr,$function_name)
	{
		$input_len = count($input_arr);
		$len_arr = array();
		for($index=0;$index<$input_len;$index++)
		{
			$len_arr[] = is_array($input_arr[$index])?count($input_arr[$index]):-1;
		}
		$U = array();
		for($l=0,$f=max($len_arr);$l<$f;$l++)
		{
			$temp_arr = array();
			for($p=0;$p<$input_len;$p++)
			{
				$temp_arr[] = $len_arr[$p]>=0?$input_arr[$p][$l]:$input_arr[$p];
			}
			$U[$l] = call_user_func($function_name,$temp_arr);
		}
		return $U;
	}

	function G($input,$offset)
	{
        $new_arr = array();
        for ($d = 0; $d < $offset; $d++) {
            $new_arr[] = NAN;
        }
        $new_arr = array_merge($new_arr,$input);
        $slice = array_slice($new_arr,0,count($input));
        
        return $slice;
	}

	function M($input)
	{
		return D(array($input),'c_abs');
	}

	//最大值
	function R($input,$data)
	{
		return D(array($input,$data),'c_max');
	}
	
	$l = 0;
	$f = 0;
	$p = 0;

    // N里面用到function
    function N_o($input)
    {
    	global $l,$f,$p;
    	$data = $input[0];
    	if($p!==false&&$p!=NAN)
    	{
    		$p = $l * $data + $f * $p;
    	}
    	else
    	{
    		$p = $data;
    	}
    	return $p;
    }

	function N($matrix,$g,$s)
	{
		global $l,$f,$p;
        // var l = s / g,
        //     f = 1 - l,
        //     p = !1,
        //     d = D([m], function(o) {
        //         return p = p !== !1 && !isNaN(p) ? l * o + f * p : o
        //     });
        // return d
        $l = $s/$g;
        $f = 1 - $l;
        $p = false;
        return D(array($matrix),'N_o');
	}

	function get_macd($code,$start_time,$end_time)
	{
		$code = 'sh600062';
		$start_time = '20141028';
		$end_time = '20150502';	
		$stock_data = get_stock_data($code,$start_time,$end_time);
		$shoupan_price = array();
		foreach($stock_data as $value)
		{
			$shoupan_price[] = $value[2];
		}	
		$ema_12 = get_ema($shoupan_price,12);
		$ema_26 = get_ema($shoupan_price,26);
		$DIFF = sub($ema_12,$ema_26);
		$DEA = get_ema($DIFF,9);
		$MACD = multiply(sub($DIFF,$DEA),2);	
		$ret = array(
			'DIFF' => $DIFF,
			'DEA' => $DEA,
			'MACD' => $MACD,
		);
		return $ret;
	}

	$code = 'sh600062';
	// ema start_time= 20140708
	// rsi start_time= 20141022
	$start_time = '20141028';
	$end_time = '20150502';
	get_macd($code,$start_time,$end_time);
	return;
	$stock_data = get_stock_data($code,$start_time,$end_time);
	$shoupan_price = array();
	foreach($stock_data as $value)
	{
		$shoupan_price[] = $value[2];
	}
	$m = G($shoupan_price,1);
	$g = sub($shoupan_price, $m);
	$d = M($g);

	$rrr = R($g,0);
	$nnn = N($d,12,1);
	print_r($nnn);


	