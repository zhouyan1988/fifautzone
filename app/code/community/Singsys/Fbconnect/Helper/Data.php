<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function redirect404($frontController)
    {
        $frontController->getResponse()
            ->setHeader('HTTP/1.1','404 Not Found');
        $frontController->getResponse()
            ->setHeader('Status','404 File not found');

        $pageId = Mage::getStoreConfig('web/default/cms_no_route');
        if (!Mage::helper('cms/page')->renderPage($frontController, $pageId)) {
            $frontController->_forward('defaultNoRoute');
}
    }
}
