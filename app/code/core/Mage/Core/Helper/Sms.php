<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * JavaScript helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Helper_Sms extends Mage_Core_Helper_Abstract
{
 
    const APIKEY = 'c0d4bfc5818082eb094d4bd3726ce01f';

 
    public function sendMessage($mobile,$text){
    
	header("Content-Type:text/html;charset=utf-8");
	
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	$data=array('text'=>$text,'apikey'=>self::APIKEY,'mobile'=>$mobile);
    
 
	$json_data = $this->send($ch,$data);
	
    echo "<pre>";
	print_r($json_data); die();
		
		
	}
	
	
	private function get_user($ch,$apikey){
    curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/user/get.json');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('apikey' => $apikey)));
    return curl_exec($ch);
}
    private function send($ch,$data){
    curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return curl_exec($ch);
}
    private function tpl_send($ch,$data){
    curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/tpl_single_send.json');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return curl_exec($ch);
    }
    private function voice_send($ch,$data){
    curl_setopt ($ch, CURLOPT_URL, 'http://voice.yunpian.com/v2/voice/send.json');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return curl_exec($ch);
    }
 
     
}
