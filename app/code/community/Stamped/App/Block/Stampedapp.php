<?php

class Stamped_App_Block_Stampedapp extends Mage_Core_Block_Template
{
    public function __construct()
    {
    	parent::__construct();
    }
	
    public function getApiPublicKey()
    {
        return trim(Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stamped_publickey', Mage::app()->getStore()));
    }
	
    public function getStampedApiStoreUrl()
    {
        return trim(Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stampedapi_storeurl', Mage::app()->getStore()));
    }

    public function setProduct($product)
    {
    	$this->setData('product', $product);
    	$_product = $this->getReviewsProduct();
    	echo $_product->getName();
    }

    public function getReviewsProduct()
	{
        if (!$this->hasData('product'))
        {
            $this->setData('product', Mage::registry('product'));
        }

        $product = $this->getData('product');
        $configurable_product_model = Mage::getModel('catalog/product_type_configurable');
        $parentIds= $configurable_product_model->getParentIdsByChild($product->getId());
            if (count($parentIds) > 0) {
                $product = Mage::getModel('catalog/product')->load($parentIds[0]);
            }
        return $product;
    }

    public function getReviewsProductId()
    {
     	$_product = $this->getReviewsProduct();
     	$productId = $_product->getId();
    	return $productId;
    }

    public function getReviewsProductName()
    {
    	$_product = $this->getReviewsProduct();
    	$productName = $_product->getName();

    	return htmlspecialchars($productName);
    }

    public function getReviewsProductImageUrl()
    {
        $imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($this->getReviewsProduct()->getSmallImage());
        return $imageUrl;
    }

    public function getReviewsProductModel()
    {
    	$_product = $this->getReviewsProduct();
    	$product_Model = $_product->getData('sku');
    	return htmlspecialchars($product_Model);
    }

    public function getReviewsProductDescription()
    {
    	$_product = $this->getReviewsProduct();
    	$productShortDescription = Mage::helper('core')->htmlEscape(strip_tags($_product->getShortDescription()));
    	return $productShortDescription;
    }

    public function getReviewsProductUrl()
    {
        $product_Url = Mage::app()->getStore()->getCurrentUrl();
    	return $product_Url;
    }
}