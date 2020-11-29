<?php

$installer = $this;

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('irdgateway/transaction'))
    ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Entity Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
    ), 'Order ID')
    ->addColumn('transaction_authority', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'NULLABLE' => true,
    ), 'Transaction Authority')
    ->addColumn('transaction_status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'NULLABLE' => true,
    ), 'Transaction Status Code')
    ->addColumn('transaction_verification_status', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'NULLABLE' => true,
    ), 'Transaction Verification, Status Code')
    ->addColumn('transaction_reference_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'NULLABLE' => true,
        'unique' => true,
    ), 'Transaction Reference Id')
    ->addIndex(
        $installer->getIdxName('irdgateway/transaction',
            array('transaction_authority'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('transaction_authority'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex($installer->getIdxName('irdgateway/transaction', array('order_id')),
        array('order_id'))
    ->addForeignKey($installer->getFkName('irdgateway/transaction', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Ird Gateway Payment Inforamtion');
$installer->getConnection()->createTable($table);

$installer->endSetup();
