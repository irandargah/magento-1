<?php

class IranDargah_IrdGateway_Block_Payment_Info extends Mage_Payment_Block_Info
{

    protected $_helper;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('irandargah/irdgateway/payment/info.phtml');
    }

    public function getPaymentInfo($orderId)
    {
        return Mage::getModel('irdgateway/transaction')->loadByOrderId($orderId);
    }

    public function getPaymentDescription($orderId)
    {
        $paymentInfo = $this->getPaymentInfo($orderId);
        $description = '';
        $transactionAuthoirty = $paymentInfo->getTransactionAuthority();
        $transactionStatus = $paymentInfo->getTransactionStatus();
        $verificationStatus = $paymentInfo->getTransactionVerificationStatus();
        $reversalStatus = $paymentInfo->getTransactionReversalStatus();
        $notSuccessCondition = intval($transactionStatus) !== 200 || is_null($transactionStatus)
        || intval($verificationStatus) !== 100 || is_null($verificationStatus)
        || !is_null($reversalStatus);

        if ($transactionAuthoirty == null) {
            $description = 'Payment has not been processed yet.';
        } elseif ($transactionStatus != 200) {
            $description = 'Payment was canceled because of authority failure.';
        } elseif ($verificationStatus !== 100) {
            $description = $this->__('Payment verification was not complete.<br />Need to check manually on  reports.');
        } elseif ($notSuccessCondition) {
            $description = 'Payment was not successfull.';
        } else {
            $description = 'Payment was successfull.';
        }

        $description .= $this->__('<br />Transaction status: %s (code: %s)', $this->getMageHelper()->getStatus($transactionStatus), $transactionStatus);
        $description .= $this->__('<br />Verification status: %s (code: %s)', $this->getMageHelper()->getStatus($verificationStatus), $verificationStatus);

        return $description;
    }

    public function toPdf()
    {
        $this->setTemplate('irandargah/irdgateway/payment/pdf/info.phtml');
        return $this->toHtml();
    }

    public function getMageHelper()
    {
        if (!$this->_helper) {
            $this->_helper = Mage::helper('irdgateway');
        }
        return $this->_helper;
    }

}
