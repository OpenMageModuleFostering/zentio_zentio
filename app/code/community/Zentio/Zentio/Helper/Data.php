<?php

class Zentio_Zentio_Helper_Data extends Mage_Core_Helper_Abstract
{
    function loadCustomer($email, $website = null)
    {
        $customer = null;

        if (Mage::getModel('customer/customer')->getSharingConfig()->isWebsiteScope()) {
            // Customer email address can be used in multiple websites so we need to
            // explicitly scope it
            if ($website) {
                // We've been given a specific website, so try that
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId($website)
                    ->loadByEmail($email);
            } else {
                // No particular website, so load all customers with the given email and then return a single object
                $customers = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addFieldToFilter('email', array('eq' => array($email)));
                
                if ($customers->getSize()) {
                    $id = $customers->getLastItem()->getId();
                    $customer = Mage::getModel('customer/customer')->load($id);
                }
            }

        } else {
            // Customer email is global, so no scoping issues
            $customer = Mage::getModel('customer/customer')->loadByEmail($email);
        }

        return $customer;
    }

    function getOrderDetail($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        $orderInfo = array(
            'title' => array("label"=>"Last Order Detail", "value"=>$order->getId()),
            'status' => array("label"=>"Status", "value"=>$order->getStatus()),
            'created' => array("label"=>"Created", "value"=>$order->getCreatedAt()),
            'updated' => array("label"=>"Updated", "value"=>$order->getUpdatedAt()),
            'total' => array("label"=>"Total", "value"=>$order->getGrandTotal() . " " . $order->getOrderCurrencyCode()),
            'admin_url' => array("label"=>"More", "value"=>$urlModel->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()))),
        );

        $orderInfo['items']['label'] = "Items";
        $orderInfo['items']['value']['header'] = array("SKU", "Product Name", "Price");
        foreach($order->getItemsCollection(array(), true) as $item) {
            $orderInfo['items']['value']['items'][] = array(
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => $item->getPrice()
            );
        }

        return $orderInfo;
    } 

    function getLastOrders($orders) {

        $lastOrders=array();
        $lastOrders['label'] = "Last Orders";
        $lastOrders['value']['header'] = array("Id", "Status", "Created At", "Total", "More");
        $lastOrders['value']['items'] = array();

        foreach ($orders as $order) {
           $lastOrders['value']['items'][] = $this->getLastOrdersRow($order);
        }

        return array("last_orders"=>$lastOrders);
    }

    function getLastOrdersRow($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        $orderInfo = array(
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'created' => $order->getCreatedAt(),
            'total' => $order->getGrandTotal(),
            'admin_url' => $urlModel->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
        );

        return $orderInfo;
    } 

    function getApiToken($generate = true)
    {
        // Grab any existing token from the admin scope
        $token = Mage::getStoreConfig('zentio/api/token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setApiToken();
        }

        return $token;
    }

    function setApiToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }
        Mage::getModel('core/config')->saveConfig('zentio/api/token', $token, 'default');

        return $token;
    }
}
