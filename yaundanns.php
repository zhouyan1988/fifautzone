<?php

 
require_once 'app/Mage.php';

Mage::app("default");   

 

$data=Mage::getConfig()->getNode("websites")->asArray();

echo "<pre>";
print_r($data); die();

Mage::app('default');//初始化程序，设置当前店铺   

$store = Mage::app()->getStore('default');
//通过电子邮件获取用户，当然也可以不获取，创建guest订单
$customer = Mage::getModel('customer/customer');
$customer->setStore($store);
$customer->loadByEmail('540175715@qq.com');   //初始化Quote，Magento的订单是通过Quote来转化过去的


$quote = Mage::getModel('sales/quote');
$quote->setStore($store);
$quote->assignCustomer($customer);//如果有用户则执行这个
$product1 = Mage::getModel('catalog/product')->load(1);
$buyInfo1 = array('qty' => 1);   

$product2 = Mage::getModel('catalog/product')->load(2);
$buyInfo2 = array('qty' => 3);
//添加商品到Quote
$quote->addProduct($product1, new Varien_Object($buyInfo1));
$quote->addProduct($product2, new Varien_Object($buyInfo2));
//设置账单和收货品地址
$billingAddress = $quote->getBillingAddress()->addData($customer->getPrimaryBillingAddress());
$shippingAddress = $quote->getShippingAddress()->addData($customer->getPrimaryShippingAddress());
//设置配送和支付方式
$shippingAddress->setCollectShippingRates(true)->collectShippingRates() ->setShippingMethod('flatrate_flatrate') ->setPaymentMethod('checkmo');  
$quote->getPayment()->importData(array('method' => 'checkmo'));
//Quote计算运费
$quote->collectTotals()->save();
//将Quote转化为订单
$service = Mage::getModel('sales/service_quote', $quote);
$service->submitAll();
$order = $service->getOrder();
$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
$invoice->register();

$transaction = Mage::getModel('core/resource_transaction') ->addObject($invoice) ->addObject($invoice->getOrder());   


$transaction->save();
  
  
  ?>