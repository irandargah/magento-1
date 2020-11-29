<?php

class IranDargah_IrdGateway_Model_IranDargah extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'irdgateway';
    protected $_formBlockType = 'irdgateway/payment_checkout_form';
    protected $_infoBlockType = 'irdgateway/payment_info';

    protected $_isGateway = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = false;

    protected $_order;

    protected $_canOrder = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = false;

    protected $_config;

    public function __construct()
    {
        parent::__construct();
        $this->_config = $this->getConfig();
    }

    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('irdgateway/processing/redirect', array('_secure' => true));
    }

    protected function _getResponseUrl()
    {
        return Mage::getUrl('irdgateway/processing/response');
    }

    public function getResponseUrl()
    {
        return $this->_getResponseUrl();
    }

    public function getUrl()
    {
        return $this->_config->getCgiUrl();
    }

    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_config = Mage::getModel('irdgateway/config');
        }
        return $this->_config;
    }

}
