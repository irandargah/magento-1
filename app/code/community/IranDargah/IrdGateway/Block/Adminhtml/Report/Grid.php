<?php

class IranDargah_IrdGateway_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('reportGrid');
        $this->setDefaultSort('value_id');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('irdgateway/transaction_collection');
        $collection->getSelect()->joinInner(
            array('sop' => $collection->getTable('sales/order')),
            'main_table.order_id = sop.entity_id',
            array('created_at', 'grand_total')
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('value_id', array(
            'header' => Mage::helper('irdgateway')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'value_id',
            'type' => 'number',
        ));

        $this->addColumn('order_id', array(
            'header' => Mage::helper('irdgateway')->__('Order #'),
            'align' => 'right',
            'index' => 'order_id',
            'type' => 'number',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('irdgateway')->__('Order Date'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('irdgateway')->__('Grand Total'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'currency_code' => Mage::app()->getStore()->getCurrentCurrencyCode(),
        ));

        $this->addColumn('transaction_reference_id', array(
            'header' => Mage::helper('irdgateway')->__('Reference Id'),
            'type' => 'text',
            'align' => 'left',
            'index' => 'transaction_reference_id',
        ));

        $this->addColumn('transaction_status', array(
            'header' => Mage::helper('irdgateway')->__('Transaction Status'),
            'index' => 'transaction_status',
            'type' => 'options',
            'options' => Mage::getSingleton('irdgateway/config')->getStatus(),
        ));

        $this->addColumn('transaction_verification_status', array(
            'header' => Mage::helper('irdgateway')->__('Verification Status'),
            'index' => 'transaction_verification_status',
            'type' => 'options',
            'options' => Mage::getSingleton('irdgateway/config')->getStatus(),
        ));

        // $this->addColumn('transaction_reversal_status', array(
        //     'header' => Mage::helper('irdgateway')->__('Reversal Status'),
        //     'index' => 'transaction_reversal_status',
        //     'type' => 'options',
        //     'options' => Mage::getSingleton('irdgateway/config')->getStatus(),
        // ));

        //$this->addExportType('*/*/exportCsv', $helper->__('CSV'));
        //$this->addExportType('*/*/exportXml', $helper->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('transaction');

        $this->getMassactionBlock()->addItem('sum', array(
            'label' => Mage::helper('irdgateway')->__('Show Sum'),
            'url' => $this->getUrl('*/*/massSum'),
        ));

        return $this;
    }

}
