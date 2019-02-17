<?php

class Stamped_App_Model_Mysql4_Richsnippet_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
	
	public function _construct() {
		$this->_init ('stamped_app/richsnippet');
	}
}