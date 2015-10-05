<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

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
		$this->load->view('welcome_message');
	}

	public function test()
	{
		$this->load->library('cimongo');
		$test_data = array(
			'a' => 1,
			'b' => 2
		);
		$this->cimongo->insert('stock_data',$test_data);
		$query = $this->cimongo->get('stock_data');
		print_r($query->result_array());
		echo "aaaaa\n";
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */