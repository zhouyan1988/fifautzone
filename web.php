<?php
 
 
// ini_set('max_execution_time',30); 
// libxml_disable_entity_loader(false);
// $opts = array(
    // 'ssl'   => array(
            // 'verify_peer'          => false
        // ),
    // 'https' => array(
            // 'curl_verify_ssl_peer'  => false,
            // 'curl_verify_ssl_host'  => false
     // )
// );
// $streamContext = stream_context_create($opts);
// $client = new SoapClient("https://www.newxshop.com/api/soap/?wsdl",
  // array(
      // 'stream_context'    => $streamContext
  // ));
 

 
 
 /*echo date("YmdHis"); die();
 
 $str="12345,";
 
echo rtrim($str, ","); die();*/
 

     /* $apiUrl = 'http://localhost/api/rest';  
       $consumerKey = '81012e37f0220d87d71c32fc740914b2';  
       $consumerSecret = '3cd8328cf8d525ec321470ad218b4cac';  
       $authType = OAUTH_AUTH_TYPE_AUTHORIZATION;  
       $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, $authType);  
       $oauthClient->enableDebug();  
 $oauthClient->setMethod("GET");  
       $oauthClient->setToken("b74a9f5e939361d791dc32790fa909bf", "c496ae05b2f6b0c01f479bc0795bd70");  
       $resourceUrl = "$apiUrl/orders";  
       $oauthClient->fetch($resourceUrl,null,OAUTH_HTTP_METHOD_GET);  
       $orderList = json_decode($oauthClient->getLastResponse());  
var_dump($orderList);  

die();*/

// header("Content-type:text/html;charset=utf-8");
 

$client = new SoapClient('https://www.newxshop.com/api/soap/index?wsdl');

$sessionId = $client->login('panda', 'panda1234');

 echo $sessionId; die();

// $client = new SoapClient('http://localhost/api/soap/?wsdl');
 
 // $sessionId = $client->login('webservice', 'webservice');

  // echo $sessionId; die();

$sessionId="0bd01e215f71ad75a989c151b212aa1f";
 
//$filter[]=array('em_hot'=>1);

//$filters[]=array("category_id"=>1);

// $filter[]=array('em_featured'=>1,'em_hot'=>2);

//$result = $client->call($sessionId, 'catalog_product.activity',15443);

// $product=array(array('product_id' => 15412,'qty' => 1,'options' => array('3102' => '12957')));

// $product=array(array('product_id' => 1,'qty' => 1,'options' => array('2' => '5')));

// $params = array(57,$product,1);

// $params=array(57,1);

// $params=array(1087,8000);

// $product=array(array('product_id' => 14756,'qty' => 1,'options' => array('2812' => '11789')));
 
// $arrAddresses = array(
	// array(
		// "mode" => "shipping",
		// "firstname" => "testFirstname",
		// "lastname" => "testLastname",
		// "company" => "testCompany",
		// "street" => "testStreet",
		// "city" => "testCity",
		// "region" => "testRegion",
		// "postcode" => "testPostcode",
		// "country_id" => "id",
		// "telephone" => "0123456789",
		// "fax" => "0123456789",
		// "is_default_shipping" => 0,
		// "is_default_billing" => 0
	// ),
	// array(
		// "mode" => "billing",
				// "firstname" => "testFirstname",
		// "lastname" => "testLastname",
		// "company" => "testCompany",
		// "street" => "testStreet",
		// "city" => "testCity",
		// "region" => "testRegion",
		// "postcode" => "testPostcode",
		// "country_id" => "id",
		// "telephone" => "0123456789",
		// "fax" => "0123456789",
		// "is_default_shipping" => 0,
		// "is_default_billing" => 0
	// )
// );

 

	  $arrAddresses = array(
	array(
          "mode" => "shipping",
            "firstname" => "bddbn",
            "lastname" => "bddbn",
            "company" => "",
            "street" => array("whshsh","bbbb"),
            "city" => "jaosn",
            "region" => "hsjsjs",
            "region_id" => "222",
            "postcode" => "12345",
            "country_id" => "US",
            "telephone" => "16767",
            "fax" => "",
            "is_default_shipping" => 0,
            "is_default_billing" => 0
	),
		array(
		"mode" => "billing",
            "firstname" => "bddbn",
            "lastname" => "bddbn",
            "company" => "",
            "street" => array("whshsh","bbbb"),
            "city" => "jaosn",
            "region" => "hsjsjs",
            "region_id" => "222",
            "postcode" => "12345",
            "country_id" => "US",
            "telephone" => "16767",
            "fax" =>"",
            "is_default_shipping" => 0,
            "is_default_billing" => 0
	)
 
);

header("Content-type:text/html;charset=utf-8");

// $params=array(3353,array("method_code"=>"storepickup","detail"=>"3","date"=>"2016-12-21","time"=>"13.30-14.00","firstname"=>"tmsa","telephone"=>"13510000000"));

 


// $params=array(2138,array("method"=>"ccsave","cc_owner"=>"tmsss","cc_ss_issue"=>"m801128556983001_3481E2FF-5804-8461-40B1-BB1C8458793E","cc_type"=>"VI","cc_number"=>"4019830666650867","cc_exp_month"=>"12","cc_exp_year"=>"2020","cc_cid"=>"222"));

// $params=array(2138,array("method_code"=>"delivery","detail"=>'111'));

// $params=array(1789,array("method_code"=>"delivery","detail"=>110));

// $params=array(93,array("product"));

// $product=array(array('product_id' => 1,'qty' => 1,'options' => array('2' => '5')));

// $params=array(array("customer_id"=>153),1,10,"US");

// $product=array(array('product_id' => 14756,'qty' => 1,'options' => array('2812' => '11789')));

// $params=array(2255,$product,1);


 
// $params=array(3343,1);

$params=array(382,3397,"US");

// $params='[3353,{"method_code":"checkmo","detail":""}]';

$params=json_encode($params);

// echo $params; die();

// $params='[3343,{"method_code":"storepickup","detail":"3","date":"2016-12-21","time":"13.30-14.00","firstname":"tmsa","telephone":"13510000000"}]';

// $params='[{"customer_id":153},1,10,"US"]';

// $params='[15797,0,"US","KES"]';

// $params = '"553,{},"","","desc",1,10,0,"US","KES""';

$sign = strtoupper(md5('args=' . $params . '&token=CYE334F697B64817B9352BA669B3E2110A1D790DE6G2888E82E2C542317129B2C4123C5941A2461289769AA95B7D7ZZZ'));

 
$request = $params . '@@@@@@' . $sign;

 


$result = $client->call($sessionId,'cart_shipping.methodList',$request);


$result=json_decode($result,true);

echo "<pre>";
print_r($result); die();

 // GRANT ALL PRIVILEGES ON *.* TO 'root'@'112.26.67.228' IDENTIFIED BY 'linyun9188'

$result = $client->call($session, 'catalog_product.info',$params);

 
//$result = $client->call($sessionId,'catalog_category.tree',1);

echo "<pre>";
print_r($result); die();
 

// If you don't need the session anymore
//$client->endSession($session);

//$result1=json_encode($result1);

 


 



//$result1 = $client->catalogProductInfo($sessionId,"7");

//$result2 = $client->call($sessionId,'catalog_category.assignedProducts',"3");

echo "<pre>";

print_r($result1);

die();


//print_r($result2);

//$result2 = $client->call($sessionId,'catalog_product.info',"14616");


 
die();

//$result = $client->call($session,'catalog_product.info',"14617");
 



$result = $client->call($session,'catalog_product.info',"14617");

//$result = $client->call($session,'customer.create',array(array('email' => 'yaundanns@test.com', 'firstname' => 'yaun', 'lastname' => 'danns', 'password' => 'password', 'website_id' => 1, 'store_id' => 1, 'group_id' => 1)));

//$result = $client->call($session, 'customer.info', '7');

//$result = $client->call($session, 'customer.list');

 //$result = $client->call($session, 'order.list');

/*$filters = array( array('sku' =>"fifa16-xbox360-1000") );

 
$result = $client->call($session, 'catalog_product.list',$filters);*/

//$result = $client->call($session,'customer.update', array('customerId' => 7, 'customerData' => array('firstname' => 'kobe', 'lastname' => 'brant','password'=>'123456')));


echo "<pre>";
print_r($result); 

$client->endSession($session);

die();



?>