<?php

class IranDargah_IrdGateway_ProcessingController extends Mage_Core_Controller_Front_Action
{

    protected $_successBlockType = 'irdgateway/transaction_success';
    protected $_failureBlockType = 'irdgateway/transaction_failure';
    protected $_cancelBlockType = 'irdgateway/transaction_cancel';

    protected $_order;
    protected $_transaction;
    protected $_paymentInst;
    protected $_orderState;
    protected $_orderStatus;

    protected $_helper;

    public function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('irdgateway');
    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getPendingPaymentStatus()
    {
        return $this->_getHelper()->getPendingPaymentStatus();
    }

    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('irdgateway');
        }
        return $this->_helper;
    }

    protected function _expireAjax()
    {
        if (!$this->_getCheckout()->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    public function redirectAction()
    {
        try {
            $session = $this->_getCheckout();
            $order = Mage::getModel('sales/order');

            $order->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
                return;
            }

            $transactionCheck = Mage::getModel('irdgateway/transaction');
            $orderTransaction = $transactionCheck->loadByOrderId($order->getId());
            if ($orderTransaction->getId() || $order->hasInvoices()) {
                $session->addError($this->_getHelper()->__('This order has payment info'));
                $this->_redirect('checkout/cart');
                return;
            }

            if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    $this->_getPendingPaymentStatus(),
                    $this->_getHelper()->__('Payment was holded to pin payment request from Ird gateway.')
                )->save();
            }

            $transaction = Mage::getModel('irdgateway/transaction');
            $result = $transaction->paymentRequest($order);
            if (is_null($result['authority'])) {
                $result = 'Payment was canceled because couldn\'t connect to IranDargah gateway to get authority key.';
                $this->_authorityFailure($order, $result);
                return;
            } elseif (!$result['authority'] || ($result['status'] <= 0) || ($result['status'] != 200)) {
                $this->_authorityFailure($order, $result);
                return;
            }

            $order->setState(
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                $this->_getPendingPaymentStatus(),
                $this->_getHelper()->__('Customer was redirected to IranDargah .')
            )->save();

            if ($session->getQuoteId() && $session->getLastSuccessQuoteId()) {
                $session->setIrdGwQuoteId($session->getQuoteId());
                $session->setIrdGwSuccessQuoteId($session->getLastSuccessQuoteId());
                $session->setIrdGwRealOrderId($session->getLastRealOrderId());
                $session->setIrdGwAuthority($result['authority']);
                $session->getQuote()->setIsActive(false)->save();
                $session->clear();
            }

            $this->loadLayout();
            $this->renderLayout();
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_catchMessages($e->getMessage());
        } catch (Exception $e) {
            $this->_catchMessages('An error occurred before redirection to IranDargah gateway.', null, $e);
        }
        $this->_redirect('checkout/cart');
    }

    protected function _authorityFailure($order, $result)
    {
        if (is_array($result)) {
            $message = $this->_getHelper()
                ->__('Payment was canceled because couldn\'t get proper authority key from Ird gateway.<li>Status: %s</li>',
                    $this->_getHelper()->getStatus($result['status'])
                );
        } else {
            $message = $result;
        }

        $order->cancel();
        $order->setState(
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            $this->_getPendingPaymentStatus(),
            $this->_getHelper()->__('Payment is pending because of authority failure.')
        );
        $order->addStatusToHistory(
            Mage_Sales_Model_Order::STATE_CANCELED,
            $this->_getHelper()->__('Payment was canceled because of authority failure.')
        );

        $order->save();

        $session = $this->_getCheckout();
        if ($quoteId = $session->getLastQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        $session->addError($message);
        $this->_redirect('checkout/cart');
    }

    public function responseAction()
    {
        // try {
        $response = $this->_checkResponse();

        if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $this->_getNewOrderStatus();
            $this->_order->setState(
                $this->_orderState, $this->_orderStatus, $this->_getHelper()->__('Customer back from IranDargah gateway.'), false
            );
        }

        if ($response['code'] != 200) {
            $this->_processCancel($response);
            return;
        }

        $this->_responseValidation($response);
        // } catch (Mage_Core_Exception $e) {
        //     $this->_catchMessages('Transaction response check: An error occurred in transaction.');
        //     $this->_failureBlock();
        // } catch (Exception $e) {
        //     $this->_catchMessages('Transaction response: An unknown error occurred in transaction.');
        //     $this->_failureBlock();
        // }
    }

    public function successAction()
    {
        try {
            $session = $this->_getCheckout();
            $session->setQuoteId($session->getIrdGwQuoteId(true));
            $session->setLastSuccessQuoteId($session->getIrdGwSuccessQuoteId(true));
            $session->unsetIrdGwQuoteId();
            $session->unsetIrdGwSuccessQuoteId();
            $session->unsetIrdGwRealOrderId();
            $session->unsIrdGwRealOrderId();
            $session->unsetIrdGwAuthority();
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_catchMessages($e->getMessage());
        } catch (Exception $e) {
            $this->_catchMessages(null, null, $e);
        }
        $this->_redirect('checkout/cart');
    }

    public function cancelAction()
    {
        $session = $this->_getCheckout();
        if ($quoteId = $session->getIrdGwQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        $session->unsetIrdGwQuoteId();
        $session->unsetIrdGwSuccessQuoteId();
        $session->unsetIrdGwRealOrderId();
        $session->unsIrdGwRealOrderId();
        $session->unsetIrdGwAuthority();
        $session->addError($this->_getHelper()->__('The order has been canceled.'));
        $this->_redirect('checkout/cart');
    }

    protected function _checkResponse()
    {
        if (!$this->getRequest()->isPost()) {
            Mage::throwException('Wrong request type.');
        }

        $request = $this->getRequest()->getParams();
        if (empty($request)) {
            Mage::throwException('Request doesn\'t contain GET elements.');
        }

        if (!isset($request['authority'])) {
            Mage::throwException('Transaction authority doesn\'t set.');
        }

        if (!isset($request['code'])) {
            Mage::throwException('Transaction result doesn\'t set.');
        }

        $transaction = Mage::getModel('irdgateway/transaction')->loadByAuthority($request['authority']);
        if (!$transaction->getId()) {
            Mage::throwException('No transaction information found for authority: .' . $request['authority']);
        }

        $this->_order = Mage::getModel('sales/order')->load($transaction->getOrderId());
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found');
        }

        $this->_paymentInst = $this->_order->getPayment()->getMethodInstance();

        return $request;
    }

    protected function _responseValidation($response)
    {
        $transaction = Mage::getModel('irdgateway/transaction')->loadByAuthority($response['authority']);

        if (!$transaction->getId()) {
            $error = $this->_getHelper()->__('Transaction for IranDargah payment is not valid.');
            $this->_getCheckout()->addError($error);
            Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Validation: there is not any transaction for this authority key: ' . $response['authority'] . '.');
        }

        $transaction->setTransactionStatus($response['code'])->save();

        if ($response['code'] != 200) {
            $error = $this->_getHelper()->__('Transaction was not successful. Status: %s', $this->_getHelper()->getStatus($response['code']));
            $this->_getCheckout()->addError($error);
            Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Error: transaction with authority ' . $response['authority'] . ' was not successful. Status Code: ' . $response['code']);
        }

        if ($response['code'] == 200) {
            $this->_transactionVerification($response);
        }
    }

    protected function _transactionVerification($response)
    {
        $transaction = Mage::getModel('irdgateway/transaction');

        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');

        $order->loadByIncrementId($session->getIrdGwRealOrderId());

        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
            return;
        }

        $result = $transaction->paymentVerification($response, $order);

        if (!$result) {
            $error = $this->_getHelper()->__('Order was canceled because couldn\'t connect to IranDargah gateway to verify transaction.');
            $this->_getCheckout()->addError($error);
            Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Verification: error occurred on verification SOAP connection for authority: ' . $response['authority'] . '.');
        } elseif ($result['status'] != 100) {
            $error = $this->_getHelper()->__('Error in payment transaction verification. Status: %s', $this->_helper->getStatus($result['status']));
            $this->_getCheckout()->addError($error);
            Mage::throwException('Order #: ' . $this->_order->getRealOrderId() . '. Transaction Verification: payment transaction verification is not valid. Status code: ' . $result['status']);
        }

        if ($result['status'] == 100) {
            $this->_processSale();
        }
    }

    protected function _processSale()
    {
        if ($this->_order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            $this->_getNewOrderStatus();
            $this->_order->setState(
                $this->_orderState, $this->_orderStatus, $this->_getHelper()->__('Customer payment was successful.'), false
            );
        }

        $this->_order->sendNewOrderEmail();
        $this->_order->setEmailSent(true);

        $this->_order->save();

        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_successBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    protected function _processCancel($response)
    {
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            $this->_order->addStatusToHistory(
                Mage_Sales_Model_Order::STATE_CANCELED, $this->_getHelper()->__('Payment was canceled.')
            );
            $this->_order->save();
        }

        $transaction = Mage::getModel('irdgateway/transaction')->load($response['authority'], 'transaction_authority');
        if ($transaction->getId()) {
            $transaction->setTransactionStatus($response['rs'])
                ->save();
        }

        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_cancelBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    protected function _catchMessages($sessionMessage = null, $debugMessage = null, $logE = null)
    {
        if (!is_null($sessionMessage)) {
            $this->_getCheckout()->addError($this->_getHelper()->__($sessionMessage));
        }

        if (!is_null($debugMessage)) {
            $this->_debug($debugMessage);
        }

        if (!is_null($logE)) {
            Mage::logException($logE);
        }
    }

    protected function _failureBlock()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_failureBlockType)
                ->toHtml()
        );
    }

    protected function _getNewOrderStatus()
    {
        $newOrderStatus = $this->_paymentInst->getConfigData('order_status');
        switch ($newOrderStatus) {
            case 'pending':
                $this->_orderState = Mage_Sales_Model_Order::STATE_NEW;
                $this->_orderStatus = 'pending';
                break;
            case 'processing':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'processing';
                break;
            case 'complete':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'complete';
                break;
            case 'closed':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'processing';
                break;
            case 'canceled':
                $this->_orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                $this->_orderStatus = 'processing';
                break;
            case 'holded':
                $this->_orderState = Mage_Sales_Model_Order::STATE_HOLDED;
                $this->_orderStatus = 'holded';
                break;
            default:
                $this->_orderState = Mage_Sales_Model_Order::STATE_NEW;
                $this->_orderStatus = 'pending';
        }
    }

}
