<?php

class IranDargah_IrdGateway_Model_Config
{
    protected $_cgiUrl = 'https://www.dargaah.com/ird/startpay/';

    const PATH_NAMESPACE = 'payment';
    const EXTENSION_NAMESPACE = 'irdgateway';

    const EXTENSION_NAME = 'IranDargah Online Payment';
    const EXTENSION_VERSION = '2.0.0';
    const EXTENSION_EDITION = 'Advanced';

    public static function getNamespace()
    {
        return self::PATH_NAMESPACE . '/' . self::EXTENSION_NAMESPACE . '/';
    }

    public function getExtensionName()
    {
        return self::EXTENSION_NAME;
    }

    public function getExtensionVersion()
    {
        return self::EXTENSION_VERSION;
    }

    public function getExtensionEdition()
    {
        return self::EXTENSION_EDITION;
    }

    public function getCgiUrl()
    {
        return $this->_cgiUrl;
    }

    public function getMerchantCode()
    {
        return Mage::getStoreConfig(self::getNamespace() . 'merchant_code');
    }

    public function isToomanCurrency()
    {
        return Mage::getStoreConfigFlag(self::getNamespace() . 'tooman_currency');
    }

    public function getStatus()
    {
        $helper = Mage::helper('irdgateway');
        return array(
            100 => $helper->getStatus(100),
            200 => $helper->getStatus(200),
            403 => $helper->getStatus(403),
            31 => $helper->getStatus(-31),
            201 => $helper->getStatus(201),
            20 => $helper->getStatus(-20),
        );
    }

}
