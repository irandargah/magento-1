<?php

class IranDargah_IrdGateway_Block_Payment_Checkout_Redirect extends Mage_Core_Block_Template
{

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getOrder()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }

    public function getFormAction()
    {
        $url = $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
        $url = $url . '?au=' . $this->_getCheckout()->getIrdGwAuthority();
        return $url;
    }

    public function getAuthority()
    {
        return $this->_getCheckout()->getIrdGwAuthority();
    }

}
