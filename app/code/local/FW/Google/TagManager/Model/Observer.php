<?php

/**
 * @category    FW
 * @package     FW_Google_TagManager
 * @copyright   Copyright (c) 2014 F+W, Inc. (http://www.fwcommunity.com)
 */
class FW_Google_TagManager_Model_Observer
{
    /**
     * Array of data for the GTM dataLayer
     * 
     * @var array
     */
    private $dataLayer = array();

    /**
     * Observe the controller action that is being dispatch and set the s values for that action/page
     *
     * @param Varien_Event_Observer $observer
     */
    public function onControllerAction(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('fw_google_tagmanager')->isEnabled()) return;  // Stop execution if module is disabled

        switch ($observer->getControllerAction()->getFullActionName()) {
            case 'cms_index_index':
                $this->dataLayer['pagetype'] = 'home';
                break;
            case 'catalog_category_view':
                $this->dataLayer['pagetype'] = 'category';
                break;
            case 'checkout_onepage_index':
            case 'onestepcheckout_index_index':
            case 'checkout_cart_index':
                $this->dataLayer['pagetype'] = 'cart';
                $quote = Mage::helper('checkout/cart')->getQuote();
                $this->addObjectDataToDataLayer($quote);
                break;
            case 'catalogsearch_result_index':
                $this->dataLayer['pagetype'] = 'searchresults';
                break;
        }
    }

    /**
     * Add Product information to data array
     *
     * @param Varien_Event_Observer $observer
     */
    public function onProductView(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('fw_google_tagmanager')->isEnabled()) return;  // Stop execution if module is disabled

        $this->dataLayer['pagetype'] = 'product';
        $totalValue = 0;
        $product = $observer->getProduct();     // Get the product the user is viewing from the observer
        // If product is grouped, add all associated products to prodid array and determine highest priced sku
        if ($product->getTypeId() == 'grouped') {
            $associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
            foreach ($associatedProducts as $aProduct) {
                if ($aProduct->isSaleable()) {
                    $this->dataLayer['prodid'][] = $aProduct->getSku();   //Add prodid to array
                    $totalValue = max($totalValue, $aProduct->getFinalPrice());
                }
            }
        } //If product isn't grouped, add basic data to ecommArray
        else {
            $this->dataLayer['prodid'][] = $product->getSku();
            $totalValue = $product->getFinalPrice();
        }
        if ($totalValue) {
            $totalValue = number_format($totalValue, 2, '.', '');
            $this->dataLayer['base_subtotal'] = $totalValue;
            $this->dataLayer['base_grand_total'] = $totalValue;
        }
    }


    /**
     * Add Order information to data array
     *
     * @param Varien_Event_Observer $observer
     */
    public function onOrderSuccessPageView(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('fw_google_tagmanager')->isEnabled()) return;  // Stop execution if module is disabled

        $this->dataLayer['pagetype'] = 'purchase';
        $orderIds = $observer->getEvent()->getOrderIds();       // Get the orderIds from the observer
        if (!empty($orderIds) && is_array($orderIds)) {
            foreach ($orderIds as $oid) {
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getSingleton('sales/order');                 // Load Order Singleton
                if ($order->getId() != $oid) $order->reset()->load($oid);   // Make sure Order matches Order Id
                $this->addObjectDataToDataLayer($order);
            }
        }
    }

    /**
     * Add amounts to the data layer from quote or order objects
     *
     * @param $object
     */
    private function addObjectDataToDataLayer($object)
    {
        $amounts = array(
            'base_subtotal',
            'base_discount_amount',
            'base_shipping_amount',
            'base_tax_amount',
            'base_grand_total'
        );
        foreach ($amounts as $amount) {
            if ($amt = $object->getData($amount)) {
                $this->dataLayer[$amount] = number_format($amt, 2, '.', '');
            }
        }
        $objectItems = $object->getAllItems();    // Get all items in the order
        foreach ($objectItems as $item) $this->dataLayer['prodid'][] = $item->getSku();
    }

    /**
     * Observe the controller action after blocks are generated
     *
     * @param Varien_Event_Observer $observer
     */
    public function onControllerActionBlocksAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('fw_google_tagmanager')->isEnabled()) return;  // Stop execution if module is disabled

        $layout = $observer->getLayout();                        // Get the Layout

        $afterBodyStart = $layout->getBlock('after_body_start');    // Get after_body_start
        if (empty($afterBodyStart)) return;

        $tagManagerBlock = $layout->createBlock('fw_google_tagmanager/gtm', 'fw_google_tagmanager_gtm');
        if (empty($tagManagerBlock)) return;

        $afterBodyStart->append($tagManagerBlock);
    }

    /**
     * Add information into Google tagmanager block to render on all pages
     *
     * @param Varien_Event_Observer $observer
     */
    public function onGoogleTagManagerToHtml(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('fw_google_tagmanager')->isEnabled()) return;  // Stop execution if module is disabled

        if (isset($this->dataLayer['prodid']) && count($this->dataLayer['prodid']) == 1) {
            $this->dataLayer['prodid'] = reset($this->dataLayer['prodid']);
        }
        $observer->getBlock()->setDataLayer($this->dataLayer);
    }
}
