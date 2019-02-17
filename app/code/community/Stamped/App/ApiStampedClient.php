<?php

class Stamped_App_ApiStampedClient
{
	const STAMPED_API_PUBLIC_KEY_CONFIGURATION = 'stamped_app/stamped_app_settings_group/stamped_publickey';
	const STAMPED_API_SECRET_KEY_CONFIGURATION = 'stamped_app/stamped_app_settings_group/stamped_apisecretkey';
	const STAMPED_API_STORE_URL_CONFIGURATION = 'stamped_app/stamped_app_settings_group/stampedapi_storeurl';

	const STAMPED_CORE_SECURED_API_URL_DEVELOPMENT = "http://requestb.in/102buqg1";
	const STAMPED_CORE_UNSECURED_API_URL_DEVELOPMENT = "http://requestb.in/102buqg1";
	
	const STAMPED_CORE_SECURED_API_URL = "https://%s:%s@stamped.io/api/%s";
	
	public static function isAPIConfigured($storeinfo)
	{
		//check if both app_key and secret exist
		if((self::getApiPublicKey($storeinfo) == null) or (self::getApiSecretKey($storeinfo) == null))
		{
			return false;
		}

		return true;
	}

	public static function getOrdersProductData($orderData) 
	{
        Mage::app()->setCurrentStore($orderData->getStoreId());

        $allproducts = $orderData->getAllVisibleItems(); //filter out simple products
		$productsdata_arr = array();
		
		foreach ($allproducts as $product) {
			//use configurable product instead of simple if still needed
            $full_product_info = Mage::getModel('catalog/product')->load($product->getProductId());

            $configurable_products_model = Mage::getModel('catalog/product_type_configurable');
            $allparentIds= $configurable_products_model->getParentIdsByChild($full_product_info->getId());
            if (count($allparentIds) > 0) {
            	$full_product_info = Mage::getModel('catalog/product')->load($allparentIds[0]);
            }

			$products_data = array();

			$products_data['productId'] = $full_product_info->getId();
			$products_data['productDescription'] = Mage::helper('core')->htmlEscape(strip_tags($full_product_info->getDescription()));
			$products_data['productTitle'] = $full_product_info->getName();
			try 
			{
				$products_data['productUrl'] = $full_product_info->getUrlInStore(array('_store' => $orderData->getStoreId()));
				$products_data['productImageUrl'] = $full_product_info->getImageUrl();
			} catch(Exception $e) {}
			
			$products_data['productPrice'] = $product->getPrice();

			$productsdata_arr[] = $products_data;
		}

		return $productsdata_arr;
	}

	public static function API_POST($path, $data, $store, $timeout=30) {
	
        try {
			$encoded_data = json_encode($data);
			$ch = curl_init(self::getApiStoreUrlAuth($store).$path);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($encoded_data),
			));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			$result = curl_exec($ch);
			$httpCodeStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$err = curl_error($ch);
			curl_close($ch);
			if (in_array($httpCodeStatus, array(200, 201))) {
				return json_decode($result, true);
			}
			if (400 === $httpCodeStatus) {
				$result = json_decode($result, true);
			}
			if (401 === $httpCodeStatus) {
				throw new Exception('API Key or API Secret is invalid, please do check. If you need any assistance, please contact us.');
			}
			
        } catch (Exception $ex) {
            Mage::log('Failed execute API Post. Error: '.$ex);

			return;
        }
	}
	
	public static function API_GET2($path, $store, $timeout=30) {
		try {
			$ch = curl_init($path);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000); //timeout in seconds

			$results = curl_exec($ch);
			$httpCodeStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$err = curl_error($ch);
			curl_close($ch);

			if ($err) {
			  return "cURL Error #:" . $err;
			} else {
				if (in_array($httpCodeStatus, array(200, 201))) {
					return $result = json_decode($results, true);
				}
				if (400 === $httpCodeStatus) {
					return $results = json_decode($results, true);
				}
				if (401 === $httpCodeStatus) {
					throw new Exception('API Key or API Secret is invalid, please do check. If you need any assistance, please contact us.');
				}
			}
        } catch (Exception $ex) {
            Mage::log('Failed execute API Get. Error: '.$ex);

			return;
        }
	}

	public static function API_GET($path, $store, $timeout=30) 
	{
		try {
			//  Initiate curl
			$ch = curl_init();
			// Disable SSL verification
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Will return the response, if false it print the response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Set the url
			curl_setopt($ch, CURLOPT_URL,self::getApiStoreUrlAuth($store).$path);
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000); //timeout in seconds
			// Execute
			$results = curl_exec($ch);
			$httpCodeStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			// Closing
			curl_close($ch);

			//$encodedData = json_encode($results);
			//return $encodedData;

			if (in_array($httpCodeStatus, array(200, 201))) {
				return json_decode($results, true);
			}
			if (400 === $httpCodeStatus) {
				$results = json_decode($results, true);
			}
			if (401 === $httpCodeStatus) {
				throw new Exception('API Key or API Secret is invalid, please do check. If you need any assistance, please contact us.');
			}
			
        } catch (Exception $e) {
            Mage::log('Failed execute API Get. Error: '.$e);

			return;
        }
	}

    public static function getApiPublicKey($store)
    {
        return (Mage::getStoreConfig(self::STAMPED_API_PUBLIC_KEY_CONFIGURATION, $store));
    }
	
    public static function getApiSecretKey($store)
    {
        return (Mage::getStoreConfig(self::STAMPED_API_SECRET_KEY_CONFIGURATION, $store));
    }
	
    public static function getStampedApiStoreUrl($store)
    {
		$store_url = (Mage::getStoreConfig(self::STAMPED_API_STORE_URL_CONFIGURATION, $store));
		if (!$store_url){
			$store_url = Mage::app()->getStore($store->getId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
		}

        return $store_url;
    }

	public static function getApiStoreUrlAuth($store)
	{
		$apiPublicKey = self::getApiPublicKey($store);
		$apiSecretKey = self::getApiSecretKey($store);
		$api_store_url = self::getStampedApiStoreUrl($store);

		return sprintf(self::STAMPED_CORE_SECURED_API_URL, $apiPublicKey, $apiSecretKey, $api_store_url); 
	}
	
	public static function getRichSnippetData($productId, $store)
	{
       	return self::API_GET("/richsnippet?productId=".$productId, $store);
	}

	public static function createReviewsRequest($order, $store)
	{
       	return self::API_POST("/survey/reviews", $order, $store);
	}

	public static function createReviewsRequestBulk($orders, $store)
	{
       	return self::API_POST("/survey/reviews/bulk", $orders, $store);
	}
	public static function getWidgetData($productId, $store)
	{
		try {
			$api_key = self::getApiPublicKey($store);
			$store_url = self::getStampedApiStoreUrl($store);
			$path="https://stamped.io/api/widget?productId=".$productId."&apiKey=".$api_key."&storeUrl=".$store_url;
			$result_output = file_get_contents($path);
	
			$result = json_decode($result_output, true);
			if($result['widget'] != '' || $result['product'] !=''){
				return $result;
			}

			Mage::log('Failed execute API Get Widget. Error: '.$ex);

			return;

		}catch (Exception $ex) {
            Mage::log('Failed execute API Get Widget. Error: '.$ex);

			return;
        }
	}
	public static function POST_PRODUCTDATA($productData, $store){
		try {
			$store_url = self::getStampedApiStoreUrl($store);
			$path="https://stamped.io/api/{$store_url}/products/bulk";
			$jsonData = Mage::helper('core')->jsonEncode($productData);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $path);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($jsonData),
			));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			$result = curl_exec($ch);
			$httpCodeStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$err = curl_error($ch);
			curl_close($ch);
			if (in_array($httpCodeStatus, array(200, 201))) {
				$myValue = 1;
				Mage::getSingleton('core/session')->setSyncResult($myValue);
				return $result;
				return json_decode($result, true);
			}
			if (400 === $httpCodeStatus) {
				return $result = json_decode($result, true);
			}
			if (401 === $httpCodeStatus) {
				throw new Exception('API Key or API Secret is invalid, please do check. If you need any assistance, please contact us.');
			}
			
        } catch (Exception $ex) {
            Mage::log('Failed execute API Post. Error: '.$ex);

			return;
        }
	}
}