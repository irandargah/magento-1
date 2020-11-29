<?php

class IranDargah_IrdGateway_Adminhtml_IrdGateway_ReportController extends Mage_Adminhtml_Controller_Action
{
	
	/**
     * Grid action
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('irdgateway/adminhtml_report'))
            ->renderLayout();
    }
		
  
    public function exportCsvAction()
    {
        $fileName   = 'irandargah_irdgateway.csv';
        $content    = $this->getLayout()->createBlock('irdgateway/adminhtml_report_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'irandargah_irdgateway.xml';
        $content    = $this->getLayout()->createBlock('irdgateway/adminhtml_report_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }

    public function massSumAction()
    {
        $transactionIds = $this->getRequest()->getParam('transaction');

        if (!is_array($transactionIds)) {
            $this->_getSession()->addError($this->__('Please select transaction(s).'));
        } else {
            if (!empty($transactionIds)) {
                try {
                    $sum = 0;
                    foreach ($transactionIds as $transactionId) {
                        $transaction = Mage::getSingleton('irdgateway/transaction')->load($transactionId);
                        $order = Mage::getSingleton('sales/order')->load($transaction->getOrderId());
                        $sum += $order->getGrandTotal();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total transactions: %d', count($transactionIds))
                    );
                    $this->_getSession()->addSuccess(
                        $this->__('Total transactions amount is: %s', Mage::helper('core')->formatPrice($sum))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/index');
    }
	
	/**
     * Initialize titles, navigation
     */
    protected function _initAction()
    {
        $this->_title($this->__('Reports'))->_title($this->__('Sales'))->_title($this->__('IranDargah Reports'));
        $this->loadLayout()
            ->_setActiveMenu('report/sales')
            ->_addBreadcrumb(Mage::helper('irdgateway')->__('Reports'), Mage::helper('irdgateway')->__('Reports'))
            ->_addBreadcrumb(Mage::helper('irdgateway')->__('Sales'), Mage::helper('irdgateway')->__('Sales'))
            ->_addBreadcrumb(Mage::helper('irdgateway')->__('IranDargah Reports'), Mage::helper('irdgateway')->__('IranDargah Reports'));
        return $this;
    }
	
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('report/salesroot/irdreport');
	}

}
