<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
	    $totalRule = '/{"companyCode.*/';  
	    $content = file_get_contents('application/data/user.log.2015092312');
	    preg_match_all($totalRule,$content,$result); 
	    
	    $list = array();
	    foreach($result[0] as $value)
	    {
	    	$temp = json_decode($value,true);
	    	if(!empty($temp))
	    	{
	    		$list[] = implode(",", array_values($temp));
	    		print_r($temp);
	    	}
	    }
	    $str = implode("\n", $list)."\n";
	    file_put_contents("application/data/list", $str);
	    //print_r($result);
	}

	public function test1()
	{
		echo "aaaaa\n";
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
