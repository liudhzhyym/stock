<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Convert extends MY_Controller {

	/**
	 * 从mysql转到mongodb
	 */

    function __construct() 
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('file');
        $this->load->library('cimongo');

    }

	public function index()
	{
		$this->load->view('welcome_message');
	}

	public function update_record($table,$value,$conds)
	{
		$this->cimongo->where($conds)->set($value)->update($table);
	}

	public function convert_mysql_mongodb()
	{
		$allList = $this->getAllStockList();
		foreach($allList as $code)
		{
			//$code = 'sz002635';
			log_message("debug","start to load code [$code]");
			$stock_data = $this->getStockData($code);
			foreach($stock_data as $dayTime => $info)
			{
				$codeInfo = array();
				$codeInfo['code'] = $code;
				$codeInfo['dayTime'] = $dayTime;
				$codeInfo['_id'] = $code."_".$dayTime;
				foreach($info as $key=>$value)
				{
					$key = str_replace(".", "_", $key);
					#$value = str_replace(".", "_", $value);
					$codeInfo[$key] = $value;
				}

				$this->cimongo->save('stock_data',$codeInfo);			
			}		
			$this->checkMem();	
		}

		// $query = $this->cimongo->get('stock_data');
		// print_r($query->result_array());
	}

	public function test()
	{
		$test_data = array(
			'a' => 1,
			'b' => 2,
		);

		$b = array(
			'cc' => 'ddd'
		);

		$this->cimongo->where(array('a'=>1))->set(array('a'=>$b))->update('stock_data');

		$this->cimongo->insert('stock_data',$test_data);
		$query = $this->cimongo->get('stock_data');
		print_r($query->result_array());
		echo "aaaaa\n";
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */