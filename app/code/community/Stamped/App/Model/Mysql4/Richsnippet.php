<?php

class Stamped_App_Model_Mysql4_Richsnippet extends Mage_Core_Model_Mysql4_Abstract {
	
    protected function _construct()
    {
        $this->_init('stamped_app/richsnippet', 'richsnippet_id');
    }   
}