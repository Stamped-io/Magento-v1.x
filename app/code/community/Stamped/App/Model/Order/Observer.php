<?php

class Stamped_App_Model_Order_Observer
{
	
	public function addReviewRequest($observer)
	{
		try {
			$order_event = $observer->getEvent();
			$orderDetails = $order_event->getOrder();
			$storeId = $orderDetails->getStoreId();
			$orderStatus = Mage::getStoreConfig('stamped_app/stamped_app_settings_group/stamped_order_status_trigger', $orderDetails->getStore());
			if ($orderStatus == null) {
				$orderStatus = array('complete');
			} else {
				$orderStatus = array_map('strtolower', explode(',', $orderStatus));
			}

            if (!Stamped_App_ApiStampedClient::isAPIConfigured($storeId))
            {
             	return $this;
            }
			
			if (!in_array($orderDetails->getStatus(), $orderStatus)) {
				return $this;
			}
			
			$order_data = array();

            $order_data["firstName"] = $orderDetails->getCustomerFirstname();
            $order_data["lastName"] = $orderDetails->getCustomerLastname();

            if (!$orderDetails->getCustomerIsGuest()) {
                $order_data["userReference"] = $orderDetails->getCustomerEmail();
            }

            $order_data["customerId"] = $orderDetails->getCustomerId();
            $order_data["email"] = $orderDetails->getCustomerEmail();
            $order_data['orderNumber'] = $orderDetails->getIncrementId();
            $order_data['orderId'] = $orderDetails->getIncrementId();
            $order_data['orderCurrencyISO'] = $orderDetails->getOrderCurrency()->getCode();
            $order_data["orderTotalPrice"] = $orderDetails->getGrandTotal();
            $order_data["orderSource"] = 'magento';
            $order_data["orderDate"] = $orderDetails->getCreatedAtDate()->toString('yyyy-MM-dd HH:mm:ss');
            $order_data['itemsList'] = Stamped_App_ApiStampedClient::getOrdersProductData($orderDetails);

			$order_data['platform'] = 'magento';

			$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($orderDetails->getCustomerEmail());

			$status = $subscriber->isSubscribed(); 

			if ($status) {
				 // put your logic here...
				$order_data['subscribed'] = true;
			} else {
				$order_data['subscribed'] = false;
			}

			Stamped_App_ApiStampedClient::createReviewsRequest($order_data, $storeId);

			return $this;	

		} catch(Exception $ex) {
			Mage::log('Failed to send mail after purchase. Error: '.$ex);
		}
	}
}
