<?php
class Vfeelit_RestConnect_IndexController extends Mage_Core_Controller_Front_Action {
	public function indexAction() {
		//Basic parameters that need to be provided for oAuth authentication
		//on Magento
        $params = array(
            'siteUrl' => 'http://localhost/oauth',
            'requestTokenUrl' => 'http://localhost/oauth/initiate',
            'accessTokenUrl' => 'http://localhost/oauth/token',
            'authorizeUrl' => 'http://localhost/admin/oAuth_authorize',//This URL is used only if we authenticate as Admin user type
            'consumerKey' => '81012e37f0220d87d71c32fc740914b2',//Consumer key registered in server administration
            'consumerSecret' => '3cd8328cf8d525ec321470ad218b4cac',//Consumer secret registered in server administration
            'callbackUrl' => 'http://localhost/restconnect/index/callback',//Url of callback action below
        );
		$oAuthClient = Mage::getModel('restconnect/oauth_client');
		$oAuthClient->reset();
		$oAuthClient->init($params);
		$oAuthClient->authenticate();
		return;
	}
	public function callbackAction() {
	
		$oAuthClient = Mage::getModel('restconnect/oauth_client');
		$params = $oAuthClient->getConfigFromSession();
		$oAuthClient->init($params);
		$state = $oAuthClient->authenticate();
		
		
		if ($state == Vfeelit_RestConnect_Model_OAuth_Client::OAUTH_STATE_ACCESS_TOKEN) {
			$acessToken = $oAuthClient->getAuthorizedToken();
		}
		print_r($acessToken); die();
 
	
		$restClient = $acessToken->getHttpClient($params);
		// Set REST resource URL
		$restClient->setUri('http://localhost/api/rest/orders');
		// In Magento it is neccesary to set json or xml headers in order to work
		$restClient->setHeaders('Accept', 'application/json');
		// Get method
		$restClient->setMethod(Zend_Http_Client::GET);
		//Make REST request
		$response = $restClient->request();
		// Here we can see that response body contains json list of products
		Zend_Debug::dump($response);
		return;
	}
}