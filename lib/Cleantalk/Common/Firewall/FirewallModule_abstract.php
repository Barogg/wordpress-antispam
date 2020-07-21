<?php

namespace Cleantalk\Common\Firewall;

/*
 * The abstract class for any FireWall modules.
 * Compatible with any CMS.
 *
 * @version       1.0
 * @author        Cleantalk team (welcome@cleantalk.org)
 * @copyright (C) 2014 CleanTalk team (http://cleantalk.org)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @since 2.49
 */
abstract class FirewallModule_abstract {
	
	public $module_name;
	
	protected $db;
	protected $db__table__logs;
	protected $db__table__data;
	
	protected $service_id;
	
	protected $result_code = '';
	
	protected $ip_array = array();
	
	protected $test_ip;
	
	protected $passed_ip;
	
	protected $blocked_ip;
	
	/**
	 * FireWall_module constructor.
	 * Use this method to prepare any data for the module working.
	 */
	abstract public function __construct();
	
	/**
	 * Use this method to execute main logic of the module.
	 *
	 * @return array  Array of the check results
	 */
	abstract public function check();
	
}