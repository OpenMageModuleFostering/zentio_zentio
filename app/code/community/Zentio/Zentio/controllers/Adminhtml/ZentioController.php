<?php

class Zentio_Zentio_Adminhtml_ZentioController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Generates a new API Token
     */
    function generateAction()
    {
        try {
            Mage::helper('zentio')->setApiToken();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('zentio')->__('Successfully generated a new API token'));
        } catch(Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getCode() . ': ' . $e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit/section/zentio');
    }
    
}
