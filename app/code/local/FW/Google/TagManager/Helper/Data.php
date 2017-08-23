<?php

/**
 * @category    FW
 * @package     FW_Google_TagManager
 * @copyright   Copyright (c) 2014 F+W, Inc. (http://www.fwcommunity.com)
 */
class FW_Google_TagManager_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Config path for using throughout the code
     *
     * @var string $XML_PATH
     */
    const XML_PATH = 'thirdparty/googletagmanager/';

    public function isActive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH . 'active', $store);
    }

    public function getContainerId($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH . 'containerid', $store);
    }

    public function isEnabled($store = null)
    {
        return $this->isActive($store) && $this->getContainerId($store);
    }
}