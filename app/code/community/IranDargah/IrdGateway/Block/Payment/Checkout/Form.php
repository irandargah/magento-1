<?php

class IranDargah_IrdGateway_Block_Payment_Checkout_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('irandargah/irdgateway/payment/checkout/form.phtml');
    }

    public function getPaymentImageSrc()
    {
        return $this->getSkinUrl('irandargah/images/irdgateway.png');
    }

}
