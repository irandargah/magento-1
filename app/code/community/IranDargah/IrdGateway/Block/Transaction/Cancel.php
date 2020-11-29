<?php

class IranDargah_IrdGateway_Block_Transaction_Cancel extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('irandargah/irdgateway/transaction/cancel.phtml');
    }

    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('*/*/cancel', array('_nosid' => true));
    }

}
