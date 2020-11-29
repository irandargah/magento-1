<?php

class IranDargah_IrdGateway_Model_Transaction extends Mage_Core_Model_Abstract
{
    const SOAP_CLIENT_URL = 'https://www.dargaah.com/wsdl';

    protected $_config;

    protected function _construct()
    {
        $this->_init('irdgateway/transaction');
        $this->_config = Mage::getModel('irdgateway/config');
    }

    public function loadByOrderId($orderId)
    {
        $this->_getResource()->loadByOrderId($this, $orderId);
        return $this;
    }

    public function loadByAuthority($authority)
    {
        $this->_getResource()->loadByAuthority($this, $authority);
        return $this;
    }

    protected function _connect()
    {
        try {
            $client = new SoapClient(self::SOAP_CLIENT_URL);

            if (!$client) {
                Mage::log($error, null, 'irandargah_' . Mage::getModel('irdgateway/ird')->getCode() . '_payment_ird.log', true);
                Mage::throwException($error);
                return false;
            }

            return $client;
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage(), null, 'irandargah_' . Mage::getModel('irdgateway/ird')->getCode() . '_payment_ird.log', true);
            return false;
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'irandargah_' . Mage::getModel('irdgateway/ird')->getCode() . '_payment_ird.log', true);
            Mage::logException($e);
            return false;
        }
    }

    public function paymentRequest($order)
    {
        $grandTotal = $order->getBaseGrandTotal();
        if ($this->_config->isToomanCurrency()) {
            $grandTotal *= 10;
        }

        $pin = $this->_config->getMerchantCode();
        $amount = (int) number_format($grandTotal, 0, '', '');
        $orderId = (int) $order->getIncrementId();
        $callbackUrl = Mage::getModel('irdgateway/irandargah')->getResponseUrl();

        $client = $this->_connect();
        if (!$client) {
            return false;
        }

        $args = array(
            array(
                'merchantID' => $pin,
                'amount' => $amount,
                'orderId' => $orderId,
                'callbackURL' => $callbackUrl,
            ),
        );
        $res = $client->__soapCall('IRDPayment', $args);
        $result = array(
            'authority' => $res->authority,
            'status' => $res->status,
            'message' => $res->message,
        );

        $this->setOrderId($order->getId())
            ->setOrderRealId($order->getIncrementId())
            ->setTransactionAuthority($result['authority'])
            ->setTransactionStatus($result['status'])
            ->save();

        return $result;
    }

    public function paymentVerification($response, $order)
    {
        $pin = $this->_config->getMerchantCode();

        $client = $this->_connect();
        if (!$client) {
            return false;
        }

        $this->loadByAuthority($response['authority']);

        if (!$this->getId()) {
            return false;
        }

        $grandTotal = $order->getBaseGrandTotal();
        if ($this->_config->isToomanCurrency()) {
            $grandTotal *= 10;
        }
        $amount = (int) number_format($grandTotal, 0, '', '');

        $args = array(
            array(
                'merchantID' => $pin,
                'authority' => $response['authority'],
                'amount' => (int) $amount,
            ),
        );
        $res = $client->__soapCall('IRDVerification', $args);
        $result = array(
            'status' => $res->status,
            'message' => $res->message,
            'refId' => $res->refId,
        );

        $this->setTransactionVerificationStatus($result['status'])
            ->setTransactionReferenceId($result['refId'])
            ->save();

        return $result;
    }

}
