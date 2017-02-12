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
 * @package     Mage_Cms
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Cms index controller
 *
 * @category   Mage
 * @package    Mage_Cms
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Ly_Seller_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Renders CMS Home page
     *
     * @param string $coreRoute
     */
    public function indexAction($coreRoute = null)
    {

	
	$data=Mage::getConfig();
   
   

$array=$data->getNode("frontend/events");
   


echo "<pre>";
   

print_r($array->asArray()); 


die();
	
 
	
 
   /*   $collection=Mage::getModel("customer/customer")->load(9);
	
echo "<pre>";
print_r($collection->getData()); die(); 
  
    $product = Mage::getModel('catalog/product')->load(1);
	
	$custom=$product->getData("custom");
    
	$customdata=explode(',',$custom);
	
    $result=array();
    foreach($customdata as $val){
		$v=explode(':',$val);
		$result[$v[0]]=$v[1];
	}
	
	echo "<pre>";
	print_r($result); die();
	
 
    $subCategories = explode(',', $loadCategory->getChildren());
 
 
    foreach ( $subCategories as $subCategoryId )
    {
        $cat = Mage::getModel('catalog/category')->load($subCategoryId);
 
        if($cat->getIsActive())
        {
            echo '<a href="'.$cat->getURL().'">'.$cat->getName().'</a>';
        }
    }*/
	   
	  //  Mage::helper("core/sms")->sendmessage('15921697994','【云片网】您的验证码是3765');
 
 
        $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_HOME_PAGE);
        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
            $this->_forward('defaultIndex');
        }
    }
	
	
 
	
 
}
