<?php

//require_once("java/Java.inc");
ob_start();
ini_set("display_errors", "1");
error_reporting(E_ALL);
require_once "e24PaymentPipe.inc.php" ;

/*
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Craig Christenson
 * @package    Tco (2Checkout.com)
 * @copyright  Copyright (c) 2010 Craig Christenson
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Shrishti_Knet_RedirectController extends Mage_Core_Controller_Front_Action {

    const XML_PATH_EMAIL_RECIPIENT  = 'payment/knet/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'payment/knet/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'payment/knet/email_template';
    const XML_PATH_ENABLED          = 'payment/knet/enabled';
    
    public function getCheckout() {
    	return Mage::getSingleton('checkout/session');
    }

    /**
     * Order instance
     */
    protected $_order;
    protected $_failureBlockType = 'knet/failure';
	
    public function checkoutAction()
    {
        $session = Mage::getSingleton('checkout/session');
	 $quote = Mage::getSingleton('checkout/session')->getQuoteId(); 
		/*$order = $this->getOrder();
		
	 $total = $order->getBaseGrandTotal();*/
	 
	 $totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount(); //total items in cart
	 $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals(); //Total object
	 $subtotal = round($totals["subtotal"]->getValue()); //Subtotal value
	 $grandtotal = $totals["grand_total"]->getValue(); //Grandtotal value

	 $total = (float)str_replace('/','',$_GET['total']);
	
	 /*$order = Mage::getModel('sales/order');
	 $order->loadByIncrementId($session->getLastRealOrderId());
	 echo $order->getCustomerEmail(); exit;*/

	 $knet_action = 1;
	 $knet_currency = 414;
	 $knet_language = "ENG";
	 $knet_responseURL = Mage::getUrl('knet/redirect/handler', array('_secure' => true));
	 $knet_errorURL = Mage::getUrl('knet/redirect/error', array('_secure' => true));
         
         //$knet_responseURL = "https://www.we-sell-them.com/index.php/knet/redirect/handler";
	 //$knet_errorURL = "https://www.we-sell-them.com/index.php/knet/redirect/error";
	  

	 /*$knet_resourcePath = $_POST['resource'];
	 $knet_alias = $_POST['alias'];*/
      
	 $track_id = time();

	 // Initialize e24PaymentPipe
	 $pipe = new e24PaymentPipe;
	 $pipe->setAction($knet_action);
	 $pipe->setCurrency($knet_currency);
	 $pipe->setLanguage($knet_language);
	 $pipe->setResponseURL($knet_responseURL);
	 $pipe->setErrorURL($knet_errorURL);
	 $pipe->setAmt($total);
         $path = str_replace('app/code/local/Shrishti/Knet/controllers','media/resource/default/',dirname(__FILE__));
	 $pipe->setResourcePath($path);
	 $pipe->setAlias(Mage::getStoreConfig('payment/knet/alias'));

	 $pipe->setUdf1($session->getLastRealOrderId());
	 /*$pipe->setUdf2($cart);*/

	 $pipe->setTrackId($track_id);

	 /*$pipe->setUdf3($module);*/
	 
	 if ($pipe->performPaymentInitialization() != $pipe->SUCCESS) {
	     // error in payment initialization
	     header("Location: $knet_errorURL");
	 }

	 // Store order in database before sending user to knet payment gateway

	 $payment_id = $pipe->getPaymentId();
	 $payment_url = $pipe->getPaymentPage();

	 $payment_url = $payment_url . "?PaymentID=" . $payment_id;
         //$url = "http://www.google.com/"; 

	 $resource = Mage::getSingleton('core/resource');
    	 $writeconnection = $resource->getConnection('core_write');
	 //$sql = "insert into knet (payment_id) values ('$payment_id')";
	 $sql = "INSERT INTO knet (payment_id,amount,date,track_id,udf1) VALUES ('".$payment_id."','".number_format($total,3)."',now(),'".$track_id."','".$session->getLastRealOrderId()."')";
	 $writeconnection->query($sql);
    

        $this->getResponse()->setRedirect($payment_url);
	 return;
    }
	
	public function payAction()
	{
		$session = Mage::getSingleton('checkout/session');
		echo '<pre>';
		print_r($session);
		exit;
	}
	
	public function handlerAction()
	{
		$session = Mage::getSingleton('checkout/session');
		
		/**********  Data from knet *****************/

		$pid = $_REQUEST['paymentid'];
		$tid = $_REQUEST['tranid'];
		$result = $_REQUEST['result'];
		$auth = $_REQUEST['auth'];
		$trackid = $_REQUEST['trackid'];
		$ref = $_REQUEST['ref'];
		$orderId = $_REQUEST['udf1'];

		/**********  Data from knet *****************/

		$resource = Mage::getSingleton('core/resource');
    	 	$writeconnection = $resource->getConnection('core_write');
	 	//$sql = "insert into knet (payment_id) values ('$payment_id')";
	 	$sql = "Update knet set transaction_id='".$tid."',result='".$result."',auth='".$auth."',reference_id='".$ref."' where payment_id='".$pid."'";
		$writeconnection->query($sql);
		
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($orderId);

		if($result == 'CAPTURED') {
			$order->sendNewOrderEmail();
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		}
		/*if($result == 'CANCELED') {
			$order->sendNewOrderEmail();
			$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		}*/


		$readConnection = $resource->getConnection('core_read');
		$query = "SELECT amount FROM knet where payment_id='".$pid."'";
     
    		/**
     		* Execute the query and store the results in $results
     		*/
    		$amount = $readConnection->fetchOne($query);

		   $mailTemplate  = Mage::getModel('core/email_template')
		    ->loadDefault('knet')
	    		->getProcessedTemplate(array(
	        'id' => $pid,
	        'tid' => $tid,
	        'result'=> $result,
	        'auth'=>$auth,
	        'trackid'=>$trackid,
	        'ref'=>$ref,
	        'orderId'=>$orderId,
		 'logo'=>Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB,array('_secure'=>true)).'media/knet.jpg'	        
	    ));



		$body = $mailTemplate;
		
		$mail = Mage::getModel('core/email');
		$mail->setToName($order->getCustomerName());
		$mail->setToEmail($order->getCustomerEmail());
		//$mail->setBccEmail('krishnakumar@shrishtionline.com');
		$mail->setBody($body);
		$mail->setSubject('Knet Payment');
		$mail->setFromEmail(Mage::getStoreConfig(self:: XML_PATH_EMAIL_RECIPIENT));
		$mail->setFromName(Mage::getStoreConfig(self:: XML_PATH_EMAIL_SENDER));
		$mail->setType('Html');// YOu can use Html or text as Mail format
		$mail->send();

		

		echo 'REDIRECT='.Mage::getUrl('knet/redirect/auth/pid/'.$pid, array('_secure' => true));exit;
		//print_r($session);
		
	}


	public function errorAction()
	{
		/*$session = Mage::getSingleton('checkout/session');
		$session->setQuoteId($session->getQuoteId());
		Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		$order->sendNewOrderEmail();
		$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();*/
		
		/*$session = Mage::getSingleton('checkout/session');
		echo '<pre>';
		
		print_r($session->getQuoteId());
		echo '<br/>';
		print_r($session);
		exit;*/
		$this->loadLayout();
		$this->renderLayout();
		return;

	}

	public function authAction() {
		$session = Mage::getSingleton('checkout/session');
		/*$totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount(); //total items in cart
		$totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals(); //Total object
		$subtotal = round($totals["subtotal"]->getValue()); //Subtotal value
		$grandtotal = $totals["grand_total"]->getValue(); //Grandtotal value
		if(isset($totals['discount']) && $totals['discount']->getValue()) {
			$discount = round($totals['discount']->getValue()); //Discount value if applied
		} else {
			$discount = '';
		}
		
		if(isset($totals['tax']) && $totals['tax']->getValue()) {
			$tax = round($totals['tax']->getValue()); //Tax value if present
		} else {
			$tax = '';
		}
		echo $grandtotal;
		echo '<br/>'.  Mage::getStoreConfig('payment/knet/alias'); */

		/**
     * Get the resource model
     */
    $resource = Mage::getSingleton('core/resource');
     
    /**
     * Retrieve the read connection
     */
    $readConnection = $resource->getConnection('core_read');
     
    $query = "SELECT * FROM knet where payment_id='".$this->_request->getParam('pid')."'";
     
    /**
     * Execute the query and store the results in $results
     */
    $results = $readConnection->fetchAll($query);


		$this->loadLayout();
		$block = $this->getLayout()->getBlock('knet_failure');
   		$block->setData('pid',$this->_request->getParam('pid'));
	
		$block->setData('res',$results);
 
    		$this->renderLayout();
		return;
	}

 	public function getOrder ()
    {
		
		if ($this->_order == null) {
            $session = Mage::getSingleton('checkout/session');
            $this->_order = Mage::getModel('sales/order');
            $this->_order->loadByIncrementId($session->getLastRealOrderId());
        }
        return $this->_order;
    }
    /*protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    public function indexAction() {
        $this->getResponse()
                ->setHeader('Content-type', 'text/html; charset=utf8')
                ->setBody($this->getLayout()
                ->createBlock('knet/redirect')
                ->toHtml());

    }

    public function successAction() {
            $post = $this->getRequest()->getPost();

       $insMessage = $this->getRequest()->getPost();
        foreach ($_REQUEST as $k => $v) {
            $v = htmlspecialchars($v);
            $v = stripslashes($v);

            $post[$k] = $v;
        }

            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($post['cart_order_id']);
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success');

	    $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());

       	    $hashSecretWord = Mage::getStoreConfig('payment/tco/secret_word');
       	    $hashSid = Mage::getStoreConfig('payment/tco/sid');
			if (Mage::getStoreConfig('payment/tco/demo') == '1') {
			$hashOrder = '1';
			}
			else {
       	    			$hashOrder = $post['order_number'];
			}
				$hashTotal = $post['total'];

        	$StringToHash = strtoupper(md5($hashSecretWord . $hashSid . $hashOrder . $hashTotal));

            if ($StringToHash == $post['key']) {
                $this->_redirect('checkout/onepage/success');
            	$order->sendNewOrderEmail();
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
			}
			else {
	       	 		$this->_redirect('checkout/onepage/success');

			}

    }*/

}

?>
