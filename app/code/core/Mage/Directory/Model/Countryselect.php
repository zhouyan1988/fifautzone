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
 * @package     Mage_Directory
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Country model
 *
 * @method Mage_Directory_Model_Resource_Country _getResource()
 * @method Mage_Directory_Model_Resource_Country getResource()
 * @method string getCountryId()
 * @method Mage_Directory_Model_Country setCountryId(string $value)
 * @method string getIso2Code()
 * @method Mage_Directory_Model_Country setIso2Code(string $value)
 * @method string getIso3Code()
 * @method Mage_Directory_Model_Country setIso3Code(string $value)
 *
 * @category    Mage
 * @package     Mage_Directory
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Directory_Model_Countryselect extends Mage_Eav_Model_Entity_Attribute_Source_Table
{

    public function getAllOptions()
    {
		  $data=Mage::getModel("directory/country")->getCollection();
  
 $countrydata=array();
   foreach($data as $country){
	   
	   $res["label"]=$country->getCountryId();
	   
	   $res["value"]=$country->getCountryId();
	   
	   $countrydata[]=$res;
   }
		 
 return $countrydata;

 }

}