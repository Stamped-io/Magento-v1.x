<?php
class Stamped_App_Adminhtml_StampedController extends Mage_Adminhtml_Controller_Action
{
    public function importOrdersAction() {  
        try {
            $mycurrent_store;
            $page = 0;
            $nowTime = time();
            $lastorders = $nowTime - (60*60*24*365); // 180 days ago
            $fromDate = date("Y-m-d", $lastorders);

            $store_info = Mage::app()->getRequest()->getParam('store');

            foreach (Mage::app()->getStores() as $store) {
                if ($store->getCode() == $store_info) {
                    global $mycurrent_store;
                    $mycurrent_store = $store;
                    break;
                }
            }

            $storeId = $mycurrent_store->getId();

            if (Stamped_App_ApiStampedClient::isAPIConfigured($mycurrent_store) == false)
            {
                Mage::app()->getResponse()->setBody('Please ensure you have configured the API Public Key and Private Key in Settings.');
                return;   
            }

            $salesOrderData=Mage::getModel("sales/order");
            $orderStatus = Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stamped_order_status_trigger', $mycurrent_store);
            if ($orderStatus == null) {
                $orderStatus = array('complete');
            } else {
                $orderStatus = array_map('strtolower', (explode(',', $orderStatus)));
            }
			
            $salesDataCollection = $salesOrderData->getCollection()
                    ->addFieldToFilter('status', $orderStatus)
                    ->addFieldToFilter('store_id', $storeId)
                    ->addAttributeToFilter('created_at', array('gteq' =>$fromDate))
                    ->addAttributeToSort('created_at', 'DESC')
                    ->setPageSize(200);

            $pages = $salesDataCollection->getLastPageNumber();

            do {
                try {
                    $page++;
                    $salesDataCollection->setCurPage($page)->load();
                 
                    $orders = array();

                    foreach($salesDataCollection as $order)
                    {
                        $order_data = array();

						// Get the id of the orders shipping address
						$shippingAddress = $order->getShippingAddress();

						// Get shipping address data using the id
						if(!empty($shippingAddress)) {
							$address = Mage::getModel('sales/order_address')->load($shippingAddress->getId());
							
							if (!empty($address)){
								$order_data["location"] = $address->getCountry();
							}
						}

                        $order_data["customerId"] = $order->getCustomerId();
                        $order_data["email"] = $order->getCustomerEmail();
                        $order_data["firstName"] = $order->getCustomerFirstname();
                        $order_data["lastName"] = $order->getCustomerLastname();
                        $order_data['orderNumber'] = $order->getIncrementId();
                        $order_data['orderId'] = $order->getIncrementId();
                        $order_data['orderCurrencyISO'] = $order->getOrderCurrency()->getCode();
                        $order_data["orderTotalPrice"] = $order->getGrandTotal();
                        $order_data["orderSource"] = 'magento';
                        $order_data["orderDate"] = $order->getCreatedAtDate()->toString('yyyy-MM-dd HH:mm:ss');
                        $order_data['itemsList'] = Stamped_App_ApiStampedClient::getOrdersProductData($order);
                        $order_data['apiUrl'] = Stamped_App_ApiStampedClient::getApiStoreUrlAuth($mycurrent_store)."/survey/reviews/bulk";
						
						$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($order->getCustomerEmail());
						
						$status = $subscriber->isSubscribed(); 

						if ($status) {
							 // put your logic here...
							$order_data['subscribed'] = true;
						} else {
							$order_data['subscribed'] = false;
						}

                        $orders[] = $order_data;
                    }

                    if (count($orders) > 0) 
                    {
						$resultLog = Stamped_App_ApiStampedClient::createReviewsRequestBulk($orders, $mycurrent_store);
						
                    }
                } catch (Exception $ex) {
					Mage::app()->getResponse()->setBody($e->getMessage());

					return;
                }

                $salesDataCollection->clear();

            } while ($page <= (3000 / 200) && $page < $pages);

        } catch(Exception $ex) {
            Mage::log('Failed to import history orders. Error: '.$ex);
        }

        Mage::app()->getResponse()->setBody(1);
    }

    public function accessDashboardAction() {
        $store_info;
        $store_param = Mage::app()->getRequest()->getParam('store');

        foreach (Mage::app()->getStores() as $store) {
            if ($store->getCode() == $store_param) {
                global $store_info;
                $store_info = $store;
                break;
            }
        }

        if (Stamped_App_ApiStampedClient::isAPIConfigured($store_info) == false)
        {
            Mage::app()->getResponse()->setBody('Please ensure you have configured the API Public Key and Private Key in Settings.');
            return;   
        }else{
            $Api_public_key = (string)Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stamped_publickey', $store_info);
            $Api_private_key = (string)Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stamped_apisecretkey', $store_info);
            $passwordhash_base64 = base64_encode($Api_public_key.":".$Api_private_key);
            $dashboardurl="https://go.stamped.io/v2/#/signin?passwordHash=".$passwordhash_base64;
            Mage::app()->getResponse()->setBody($dashboardurl);  
        }
        return;
    }

    public function clearStampedCacheAction(){
        $store_info;
        $stParam = Mage::app()->getRequest()->getParam('store');

        foreach (Mage::app()->getStores() as $store) {
            if ($store->getCode() == $stParam) {
                global $store_info;
                $store_info = $store;
                break;
            }
        }

        if (Stamped_App_ApiStampedClient::isAPIConfigured($store_info) == false)
        {
            Mage::app()->getResponse()->setBody('Please ensure you have configured the API Public Key and Private Key in Settings.');
            return;   
        }else{
           $output = Mage::getModel('stamped_app/richsnippet')->truncate(); 
           if($output){
             Mage::app()->getResponse()->setBody(1);
           }    
        }
        return;
    }
    public function syncProductAction(){
        try{
            $store_info;
            $stParam = Mage::app()->getRequest()->getParam('store');

            foreach (Mage::app()->getStores() as $store) {
                if ($store->getCode() == $stParam) {
                    global $store_info;
                    $store_info = $store;
                    break;
                }
            }

            if (Stamped_App_ApiStampedClient::isAPIConfigured($store_info) == false)
            {
                Mage::app()->getResponse()->setBody('Please ensure you have configured the API Public Key and Private Key in Settings.');
                return;   
            }else{
                $page = 0;
                Mage::getSingleton('core/session')->unsSyncResult();
                $_productCollection = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addAttributeToSort('created_at', 'DESC')
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('type_id', array('in' => array('simple','configurable')));
                $_productCollection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)->setPageSize(200);
                $pages = $_productCollection->getLastPageNumber();

                do {
                    try {
                        $pro_data = array();
                        $page++;
                        $_productCollection->setCurPage($page)->load();
                        foreach ($_productCollection as $_product){
                           $pro_id = $_product->getId();
                           $pro_name = $_product->getName();
                           $productMediaConfig = Mage::getModel('catalog/product_media_config');
                           $pro_imageUrl = $productMediaConfig->getMediaUrl($_product->getImage());
                           $pro_price = $_product->getPrice();
                           $pro_Url = $_product->getProductUrl();
                           $pro_sku = $_product->getSku();
                           $pro_type = $_product->getTypeId();
                           $data = array(
                                 'productId'          =>   $pro_id,
                                 'productTitle'       =>   $pro_name,
                                 'productImageUrl'    =>   $pro_imageUrl,
                                 'productPrice'       =>   $pro_price,
                                 'productUrl'         =>   $pro_Url,
                                 'productSKU'         =>   $pro_sku,
                                 'productBarcode'     =>   '',
                                 'productType'        =>   $pro_type,
                                 'productBrand'        =>  "Apple",
                                 'productMagentoType' =>   $pro_type
                             );
                            array_push($pro_data,$data);
                        }
                        //echo $jsonData = Mage::helper('core')->jsonEncode($pro_data);  
                        if (count($pro_data) > 0) 
                        {
                           echo $outputLog=Stamped_App_ApiStampedClient::POST_PRODUCTDATA($pro_data, Mage::app()->getStore()); 
                        }
                    } catch (Exception $ex) {
                        Mage::app()->getResponse()->setBody($e->getMessage());
                        return;
                    }

                  $_productCollection->clear();

                } while ($page <= (3000 / 200) && $page < $pages);
                if(Mage::getSingleton('core/session')->getSyncResult() == 1){
                     Mage::app()->getResponse()->setBody(1);
                }
            }
        } 
        catch(Exception $ex) {
            Mage::log('Failed to import history orders. Error: '.$ex);
        }
        return;
    }
}