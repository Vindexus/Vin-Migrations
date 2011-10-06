<?php
class Migrate extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('vinmigrate');
		$this->vinmigrate->init();
	}
	
	public function index()
	{
		return $this->current_version();
	}
	
	public function install()
	{
		echo "<pre>";
		if($this->vinmigrate->install())
		{
			echo join("\n", $this->vinmigrate->notices) . "\n";
			echo $this->vinmigrate->success_message;
		}
		else
		{
			echo join("\n", $this->vinmigrate->errors);
		}
		echo "</pre>";
	}
	
	public function run_to($number)
	{
		echo '<pre>';
		if($this->vinmigrate->migrate_to($number))
		{
			echo join("\n", $this->vinmigrate->notices) . "\n";
			echo $this->vinmigrate->success_message;
		}
		else
		{
			echo join("\n<br />", $this->vinmigrate->errors);
		}
		echo '<pre>';
	}
	
	public function current_version()
	{
		echo "Current version: " . $this->vinmigrate->get_current_version();
	}
	
	public function set_version($number)
	{
		$this->vinmigrate->set_version($number);
		echo '<pre>';
		echo join("\n", $this->vinmigrate->notices);
		
		if(!$this->vinmigrate->no_errors())
		{
			echo "\n-----ERRORS--------------------------\n";
			echo join("\n", $this->vinmigrate->errors);
		}
		
		echo '</pre>';
	}
	
	public function run_up($number)
	{
		$this->vinmigrate->run($number, 'up');
		echo '<pre>';
		echo join("\n", $this->vinmigrate->notices);
		echo '</pre>';
	}
	
	public function run_down($number)
	{
		$this->vinmigrate->run($number, 'down');
		echo '<pre>';
		echo join("\n", $this->vinmigrate->notices);
		echo '</pre>';
	}
	
	public function run_bootstrap($number)
	{
		$args = $this->uri->segment_array();
		$args = array_slice($args, 3);
		$result = $this->vinmigrate->run_bootstrap($number, $args);
		echo '<pre>';
		echo join("\n", $this->vinmigrate->notices);
		
		if(!$result)
		{
			echo "\n\nERRORS\n";
			echo join("\n", $this->vinmigrate->errors);
		}
		echo '</pre>';
	}
}