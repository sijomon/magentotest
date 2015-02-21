<?php

class Shrishti_Knet_Block_Failure extends Mage_Core_Block_Template
{
	protected $_data;
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('knet/failure.phtml');
	$_data = Mage::registry('data');
    }

    /**
     * Get continue shopping url
    */
    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('checkout/cart', array('_nosid' => true));
    } 
}

?>
