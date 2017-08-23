<?php

/**
 * @category    FW
 * @package     FW_Google_TagManager
 * @copyright   Copyright (c) 2014 F+W, Inc. (http://www.fwcommunity.com)
 */
class FW_Google_TagManager_Block_Gtm extends Mage_Core_Block_Text
{
    /**
     * @var array
     */
    private $dataLayer = array();

    /**
     * Render the Google tagmanager tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        /** @var FW_Google_TagManager_Helper_Data $helper */
        $helper = Mage::helper('fw_google_tagmanager');
        if (!$helper->isEnabled()) return;  // Return nothing if module is not enabled

        Mage::dispatchEvent('fw_google_tagmanager_to_html', array('block' => $this));

        $containerId = $helper->getContainerId();
        return <<< JS

<!-- Google Tag Manager -->
<script>var dataLayer = [{$this->dataLayer}];</script>
<noscript><iframe src="//www.googletagmanager.com/ns.html?id={$containerId}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$containerId}');</script>
<!-- End Google Tag Manager -->

JS;
    }

    /**
     * @param $dataLayer
     */
    public function setDataLayer($dataLayer)
    {
        $this->dataLayer = json_encode($dataLayer);
    }
}
