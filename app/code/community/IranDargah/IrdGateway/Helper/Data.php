<?php

class IranDargah_IrdGateway_Helper_Data extends Mage_Payment_Helper_Data
{

    public function getPendingPaymentStatus()
    {
        if (version_compare(Mage::getVersion(), '1.4.0', '<')) {
            return Mage_Sales_Model_Order::STATE_HOLDED;
        }
        return Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    }

    public function getStatus($code)
    {
        $status = 'Undecided Status';
        if (is_null($code)) {
            return $status;
        }

        switch ($code) {
            case 100:
                $status = $this->__('Successful');
                break;

            case 200:
                $status = $this->__('Pre Request');
                break;

            case -31:
                $status = $this->__('Access Violation');
                break;

            case 403:
                $status = $this->__('Merchant Authentication Failed');
                break;

            case 201:
                $status = $this->__('Sale Is Already Done Successfully');
                break;

            case -20:
                $status = $this->__('Invalid Merchant Order');
                break;

            default:
                $status = $this->__('Undecided Status');

        }
        return $status;
    }

    public function getDomain()
    {
        $domain = $_SERVER['SERVER_NAME'];
        $url = str_replace(array('http://', 'https://', '/'), '', $domain);
        $tmp = explode('.', $url);
        $cnt = count($tmp);

        $last = $tmp[$cnt - 2] . '.' . $tmp[$cnt - 1];

        $exceptions = array(
            'com.au', 'com.br', 'com.bz', 'com.ve', 'com.gp',
            'com.ge', 'com.eg', 'com.es', 'com.ye', 'com.kz',
            'com.cm', 'net.cm', 'com.cy', 'com.co', 'com.km',
            'com.lv', 'com.my', 'com.mt', 'com.pl', 'com.ro',
            'com.sa', 'com.sg', 'com.tr', 'com.ua', 'com.hr',
            'com.ee', 'ltd.uk', 'me.uk', 'net.uk', 'org.uk',
            'plc.uk', 'co.uk', 'co.nz', 'co.za', 'co.il',
            'co.jp', 'ne.jp', 'net.au', 'com.ar', 'co.ir',
        );

        if (in_array($last, $exceptions)) {
            $suffix = $tmp[($cnt - 3)] . '.' . $tmp[($cnt - 2)] . '.' . $tmp[($cnt - 1)];
        } else {
            $suffix = $tmp[($cnt - 2)] . '.' . $tmp[($cnt - 1)];
        }
        return $suffix;
    }

}
