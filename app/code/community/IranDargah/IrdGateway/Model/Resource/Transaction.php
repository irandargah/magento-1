<?php

class IranDargah_IrdGateway_Model_Resource_Transaction extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('irdgateway/transaction', 'value_id');
    }

    public function loadByOrderId(IranDargah_IrdGateway_Model_Transaction $transaction, $orderId)
    {
        $adapter = $this->_getReadAdapter();
        $bind = array('order_id' => $orderId);
        $select = $adapter->select()
            ->from($this->getTable('irdgateway/transaction'))
            ->where('order_id = ?', $orderId);

        $transactionId = $adapter->fetchOne($select, $bind);
        if ($transactionId) {
            $this->load($transaction, $transactionId);
        } else {
            $transaction->setData(array());
        }

        return $this;
    }

    public function loadByAuthority(IranDargah_IrdGateway_Model_Transaction $transaction, $authority)
    {
        $adapter = $this->_getReadAdapter();
        $bind = array('transaction_authority' => $authority);
        $select = $adapter->select()
            ->from($this->getTable('irdgateway/transaction'))
            ->where('transaction_authority = ?', $authority);

        $transactionId = $adapter->fetchOne($select, $bind);
        if ($transactionId) {
            $this->load($transaction, $transactionId);
        } else {
            $transaction->setData(array());
        }

        return $this;
    }
}
