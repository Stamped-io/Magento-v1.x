<?php

class Stamped_App_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_config;

    public function __construct ()
    {
        $this->_config = Mage::getStoreConfig('stamped_app');
    }

    public function showStampedWidget($thisObj, $product = null, $print=true)
    {
        $result = $this->getStampedBlock($thisObj, 'stamped-reviews', $product, $print);

        if ($print == false) {
            return $result;
        }
    }

    public function showStampedBadge($thisObj, $product = null, $print=true)
    {
        $result = $this->getStampedBlock($thisObj, 'stamped-badge', $product);
		return $result;

        if ($print == false){
            return $result;
        }
    }
	
    public function getRichSnippetData()
    {
        try {
            $product_id = Mage::registry('product')->getId();
            $rich_snippet = Mage::getModel('stamped_app/richsnippet')->getSnippetByProductIdAndStoreId($product_id, Mage::app()->getStore()->getId());
            
            if (($rich_snippet == null) || (!$rich_snippet->isValid())) {
                //no snippet for product or snippet isn't valid anymore. get valid snippet code from api
                $widget_data = Stamped_App_ApiStampedClient::getWidgetData($product_id, Mage::app()->getStore());
                $html_widget = '<div class="stamped-content">';
                $html_widget .= $widget_data["widget"];
                $html_widget .=  '</div>';
                $html_product = $widget_data["product"];
                $widget_content=str_replace('<div class="stamped-content"> </div>', $html_widget ,$html_product);
                $reviewsAverage = $widget_data["rating"];
                $reviewsCount = $widget_data["count"];
                $ttl = $widget_data["ttl"];
                if($ttl == ''){
                    $ttl = 86400;
                }
                if($widget_content != ''){
                   // echo '<script>console.log("here..!!!");</script>'; 
                    if ($rich_snippet == null) {
                        $rich_snippet = Mage::getModel('stamped_app/richsnippet');
                        $rich_snippet->setProduct_id($product_id);
                        $rich_snippet->setStore_id(Mage::app()->getStore()->getid());
                    }
                    $rich_snippet->sethtml_widget($widget_content);
                    $rich_snippet->setaverage_score($reviewsAverage);
                    $rich_snippet->setreviews_count($reviewsCount);
                    $rich_snippet->setExpiration_time(date('Y-m-d H:i:s', time() + $ttl));
                    
                    if($rich_snippet->save()){
                        return array("html_widget" => $widget_content, "average_score" => $reviewsAverage, "reviews_count" => $reviewsCount);
                    }
                }      
            }else{
              return array("html_widget" => $rich_snippet->getHtml_widget(), "average_score" => $rich_snippet->getAverageScore(), "reviews_count" => $rich_snippet->getReviewsCount());
            }

        } catch(Excpetion $ex) {
            Mage::log($ex);
        }
        return array();
    }

    private function getApiKey()
    {
        return trim(Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stamped_publickey',Mage::app()->getStore()));
    }

    private function getStampedBlock($thisObjct, $block_name, $product = null, $print=true)
    {
        $current_block = $thisObjct->getLayout()->getBlock('content')->getChild('stamped');

        if ($current_block == null) {
            return;
        }

        $current_block = $current_block->getChild($block_name);
        if ($current_block == null) {
            return;
        }

        if ($product != null)
        {
            $current_block->setAttribute('product', $product);
        }

        if ($current_block != null)
        {
            if ($print == true) {
                echo $current_block->toHtml();
            } else {
                return $current_block->toHtml();
            }
        }
    }
}