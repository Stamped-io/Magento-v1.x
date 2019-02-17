<?php

class Stamped_App_Model_Richsnippet extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('stamped_app/richsnippet');
    }

    public function isValid()
    {
        $expirationTime = strtotime($this->getExpirationTime());
        return ($expirationTime > time());
    }

    public function getSnippetByProductIdAndStoreId($product_id, $store_id)
    {
        $col = $this->getCollection()->addFieldToFilter('store_id', $store_id);
        if ($col->getSize() == 0) {
            return null;
        }
        $snippet = $col->getItemByColumnValue('product_id', $product_id);
        return $snippet;
    }

    public function truncate($AUTO_INCREMENT = 1) {
        
        $collection = $this->getCollection();

        foreach ($collection as $item) {
            $item->delete();
        }
        $resource = Mage::getSingleton('core/resource');

        $writeConnection = $resource->getConnection('core_write');

        $table = $resource->getTableName('stamped_app/richsnippet');

        $query = "ALTER TABLE {$table} AUTO_INCREMENT = 1";

        $writeConnection->query($query);
       
        return true;
    }
}