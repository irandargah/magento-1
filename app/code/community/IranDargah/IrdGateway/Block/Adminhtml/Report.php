<?php

class IranDargah_IrdGateway_Block_Adminhtml_Report extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_blockGroup = 'irdgateway';
        $this->_controller = 'adminhtml_report';
        $this->_headerText = Mage::helper('irdgateway')->__('IranDargah Gateway Report');
        parent::__construct();
        $this->_removeButton('add');
    }

}
