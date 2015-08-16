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

	public function getAllStockList()
	{
		$list = $this->getList('stockList.conf');
		$codeList = array();
		foreach($list as $code)
		{
		    // 如果是创业板，略过
		    if(preg_match("/^(sh|sz)3/",$code)==1||preg_match("/^3/",$code)==1)
		    {
		    	continue;
		    }
		    $codeList[] = $code;
		}
		//print_r($list);
		return $codeList;
	}

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */