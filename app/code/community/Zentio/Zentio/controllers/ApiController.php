<?php

class Zentio_Zentio_ApiController extends Mage_Core_Controller_Front_Action
{
    public function customersAction()
    {
        if ( ! $this->checkAuth()) return $this;

        $email = $this->getRequest()->getParam('email', null);

        if ( ! $email) {
            return $this->sendJsonResponse(array('messsage' => 'You must provide the email query parameter'), 400);
        }

        // load customer object for email
        $customer = Mage::helper('zentio')->loadCustomer($email);

        // Get a list of all orders for the given email address
        // This is used to determine if a missing customer is a guest or if they really aren't a customer at all
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_email', array('eq' => array($email)));

        $orderCollection->setOrder('increment_id','DESC')->getSelect()->limit(5);

        $lastOrder = array();
        $lastOrders = array();
        if ($orderCollection->getSize()) {
            $lastOrder = Mage::helper('zentio')->getOrderDetail($orderCollection->getFirstItem());
            $lastOrders = Mage::helper('zentio')->getLastOrders($orderCollection);
        }

        if ($customer && $customer->getId()) {
            $data = array(
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getTelephone() ? $customer->getTelephone() : $customer->getDefaultBillingAddress()->getTelephone(),
            );

            /*if ($billing = $customer->getDefaultBillingAddress()) {
                $data['address'] = $billing->format('text');
            }*/

            $data['custom_attributes'] = $lastOrder + $lastOrders;

            return $this->sendJsonResponse($data);
        }

        return $this->sendJsonResponse(array('message' => 'Customer does not exist'), 404);
    }

    protected function sendJsonResponse(array $content, $statusCode = 200)
    {
        $this->getResponse()
            ->setBody(json_encode($content))
            ->setHttpResponseCode($statusCode)
            ->setHeader('Content-type', 'application/json', true);

        return $this;
    }

    protected function setCustomAttribute(array &$data, $field, $value, $label = null)
    {
        $label = $label ? $label : ucfirst($field);
        $data['custom_attributes'][$field] = array('label' => $label, 'value' => $value);

        return $data;
    }

    protected function checkAuth()
    {
        $configs = $this->getAuthConfigs();

        if ( ! $configs['enabled']) {
            $message = 'API access is disabled';
            Mage::log($messsage, null, 'zentio.log');

            $this->sendJsonResponse(array('message' => $message), 403);
            return false;
        }

        if ( ! $configs['username'] ||  !$configs['password']) {
            $message = "API access details incomplete. username and/or password hasn't been set correctly. (in Magento ZentIO settings page)";
            Mage::log($messsage, null, 'zentio.log');

            $this->sendJsonResponse(array('message' => $message), 403);
            return false;
        }

        $paramUsername = $this->getRequest()->getServer('PHP_AUTH_USER', null);
        $paramPassword = $this->getRequest()->getServer('PHP_AUTH_PW', null);

        if ( ! $paramUsername) {
            $message = 'Requires authentication';
            Mage::log($messsage, null, 'zentio.log');

            $this->sendJsonResponse(array('message' => $message), 401);
            return false;
        }

        if ($configs['username'] !== $paramUsername ||
            $configs['password'] !== $paramPassword) {
            $message = 'Authorization failed!';
            Mage::log($messsage, null, 'zentio.log');

            $this->sendJsonResponse(array('message' => $message), 403); // send forbidden if credentials are incorrect
            return false;
        }

        return true;
    }

    protected function getAuthConfigs()
    {
        $basic_auth_realm = 'Zentio Realm';
        $api_enabled = Mage::getStoreConfig('zentio/api/enabled', null);
        $basic_auth_username = Mage::getStoreConfig('zentio/api/username', null);
        $basic_auth_password = Mage::getStoreConfig('zentio/api/password', null);

        return array(
            'enabled' => (int) $api_enabled,
            'realm' => $basic_auth_realm,
            'username' => $basic_auth_username,
            'password' => $basic_auth_password,
        );
    }
}
