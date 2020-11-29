<?php

class IranDargah_IrdGateway_Block_Transaction_Failure extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('irandargah/irdgateway/transaction/failure.phtml');
    }

    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('checkout/cart', array('_nosid' => true));
    }

}
