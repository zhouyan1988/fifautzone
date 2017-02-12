<?php
/**
 * @category    Development
 * @package     Singsys_Fbconnect
 * @version     1.0.0
 * @copyright   Copyright (c) 2015 SingsysShop (https://www.singsys.com)
 * @author	Singsys
 */

class Singsys_Fbconnect_Block_Facebook_Account extends Mage_Core_Block_Template
{
    protected $client = null;
    protected $userInfo = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('singsys_fbconnect/facebook_client');
        if(!($this->client->isEnabled())) {
            return;
        }

        $this->userInfo = Mage::registry('singsys_fbconnect_facebook_userinfo');

        $this->setTemplate('singsys/fbconnect/facebook/account.phtml');
    }

    protected function _hasUserInfo()		/* return user's Information status */
    {
        return (bool) $this->userInfo;
    }

    protected function _getFacebookId()		/* return user facebook Id */
    {
        return $this->userInfo->id;
    }

    protected function _getStatus()			/* return user Status */			
    {
        if(!empty($this->userInfo->link)) {
            $link = '<a href="'.$this->userInfo->link.'" target="_blank">'.
                    $this->htmlEscape($this->userInfo->name).'</a>';
        } else {
            $link = $this->userInfo->name;
        }

        return $link;
    }

    protected function _getEmail()			/* return user Email */
    {
        return $this->userInfo->email;
    }

    protected function _getPicture()		/* return user profile Picture */
    {
        if(!empty($this->userInfo->picture)) {
            return Mage::helper('singsys_fbconnect/facebook')
                    ->getProperDimensionsPictureUrl($this->userInfo->id,
                            $this->userInfo->picture->data->url);
        }

        return null;
    }

    protected function _getName()			/* return user full Name*/
    {
        return $this->userInfo->name;
    }

    protected function _getGender()			/* return specified Gender of user*/
    {
        if(!empty($this->userInfo->gender)) {
            return ucfirst($this->userInfo->gender);
        }

        return null;
    }

    protected function _getBirthday()		/* return user Birthday */
    {
        if(!empty($this->userInfo->birthday)) {
            $birthday = date('F j, Y', strtotime($this->userInfo->birthday));
            return $birthday;
        }

        return null;
    }

}
