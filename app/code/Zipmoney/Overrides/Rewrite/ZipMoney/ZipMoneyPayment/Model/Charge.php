<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Zipmoney\Overrides\Rewrite\ZipMoney\ZipMoneyPayment\Model;
use \Magento\Checkout\Model\Type\Onepage;
use \Magento\Customer\Api\Data\GroupInterface;
use \Magento\Sales\Model\Order;
use \ZipMoney\ZipMoneyPayment\Model\Config;
use \ZipMoney\ZipMoneyPayment\Model\Checkout\AbstractCheckout;


class Charge extends \ZipMoney\ZipMoneyPayment\Model\Charge
{
	 /**
	   * @var \Magento\Quote\Api\CartManagementInterface
	   */
	  protected $_quoteManagement; 

	  /**
	   * @var \Magento\Customer\Api\AccountManagementInterface
	   */
	  protected $_accountManagement;

	  /**
	   * @var \Magento\Framework\Message\ManagerInterface
	   */
	  protected $_messageManager;

	  /**
	   * @var \Magento\Customer\Model\Url
	   */
	  protected $_customerUrl;

	  /**
	   * @var \Magento\Customer\Api\CustomerRepositoryInterface
	   */
	  protected $_customerRepository;

	  /**
	   * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
	   */
	  protected $_orderSender;

	  /**
	   * @var \Magento\Sales\Api\OrderRepositoryInterface
	   */
	  protected $_orderRepository;

	  /**
	   * @var \Magento\Sales\Api\OrderPaymentRepositoryInterface
	   */
	  protected $_orderPaymentRepository;

	  /**
	   * @var \Magento\Framework\DataObject\Copy
	   */
	  protected $_objectCopyService;

	  /**
	   * @var \Magento\Framework\Api\DataObjectHelper
	   */
	  protected $_dataObjectHelper;

	  /**
	   * Set quote and config instances
	   *
	   * @param array $params
	   */
	   public function __construct(    
	    \Magento\Customer\Model\Session $customerSession,
	    \Magento\Checkout\Model\Session $checkoutSession,
	    \Magento\Customer\Model\CustomerFactory $customerFactory,
	    \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,    
	    \Magento\Quote\Api\CartManagementInterface $cartManagement,
	    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
	    \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
	    \Magento\Customer\Api\AccountManagementInterface $accountManagement,
	    \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
	    \Magento\Framework\Message\ManagerInterface $messageManager,
	    \Magento\Customer\Model\Url $customerUrl,      
	    \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,        
	    \Magento\Framework\DataObject\Copy $objectCopyService,        
	    \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
	    \ZipMoney\ZipMoneyPayment\Helper\Payload $payloadHelper,
	    \ZipMoney\ZipMoneyPayment\Helper\Logger $logger,
	    \ZipMoney\ZipMoneyPayment\Helper\Data $helper,
	    \ZipMoney\ZipMoneyPayment\Model\Config $config,
	    \zipMoney\Api\ChargesApi $chargesApi,
	    array $data = []
	  )
	  { 
	    $this->_quoteManagement = $cartManagement;
	    $this->_accountManagement = $accountManagement;
	    $this->_messageManager = $messageManager;
	    $this->_customerRepository = $customerRepository;
	    $this->_customerUrl = $customerUrl;
	    $this->_orderSender = $orderSender;
	    $this->_orderRepository = $orderRepository;
	    $this->_orderPaymentRepository = $orderPaymentRepository;        
	    $this->_objectCopyService = $objectCopyService;
	    $this->_dataObjectHelper = $dataObjectHelper;
	    $this->_api = $chargesApi;

	    parent::__construct( $customerSession, $checkoutSession, $customerFactory, $quoteRepository, $cartManagement,$orderRepository,$orderPaymentRepository,$accountManagement,$customerRepository,$messageManager,$customerUrl, $orderSender, $objectCopyService,$dataObjectHelper,$payloadHelper, $logger, $helper, $config, $chargesApi,$data);

	    if (isset($data['order'])) {
	      if($data['order'] instanceof \Magento\Quote\Model\Order){
	        $this->setQuote($data['order']);
	      } else {      
	        throw new \Magento\Framework\Exception\LocalizedException(__('Order instance is required.'));
	      }
	    }
	  }
	/**
	   * Charges the customer against the order
	   *
	   * @return \zipMoney\Model\Charge 
	   * @throws \Magento\Framework\Exception\LocalizedException
	   */
	  public function charge()
	  {
	    if (!$this->_order || !$this->_order->getId()) {
	      throw new \Magento\Framework\Exception\LocalizedException(__('The order does not exist.'));
	    }

	    $payload = $this->_payloadHelper->getChargePayload($this->_order);

	    $this->_logger->debug("Charge Payload:- ".$this->_helper->json_encode($payload));

	    try {
	      $charge = $this->getApi()
	                     ->chargesCreate($payload, $this->genIdempotencyKey());

	      $this->_logger->debug("Charge Response:- ".$this->_helper->json_encode($charge));

	      if(isset($charge->error)){      
	        throw new \Magento\Framework\Exception\LocalizedException(__('Could not create the charge'));
	      }

	      if(!$charge->getState() || !$charge->getId()){
	        throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Charge'));
	      }

	      $this->_logger->debug($this->_helper->__("Charge State:- %s",$charge->getState()));

	      if($charge->getId()){
	      $additionalPaymentInfo = $this->_order->getPayment()->getAdditionalInformation();
	      $additionalPaymentInfo['receipt_number'] = $charge->getReceiptNumber();
	      $additionalPaymentInfo['zipmoney_charge_id'] = $charge->getId();
	      $payment =  $this->_order->getPayment()
	                     ->setAdditionalInformation($additionalPaymentInfo);
	        $this->_orderPaymentRepository->save($payment);
	      }

	      $this->_chargeResponse($charge,false);

	    } catch(\zipMoney\ApiException $e){
	      list($apiError, $message, $logMessage) = $this->_helper->handleException($e);  

	      // Cancel the order
	      $this->_helper->cancelOrder($this->_order,$apiError);
	      throw new \Magento\Framework\Exception\LocalizedException(__($message));
	    } 
	    return $charge;
	  }
	  /**
	   * Handles the charge response and captures/authorises the charge based on state
	   *
	   * @param \zipMoney\Model\Charge $charge
	   * @param bool $isAuthAndCapture
	   * @return \zipMoney\Model\Charge 
	   */
	  protected function _chargeResponse($charge, $isAuthAndCapture)
	  {
	    switch ($charge->getState()) {
	      case 'captured':
	        /*
	         * Capture Payment
	         */
	        $this->_capture($charge->getId(), $isAuthAndCapture);

	        break;
	      case 'authorised':
	        /*
	         * Authorise Payment
	         */
	        $this->_authorise($charge->getId());

	        break;
	      default:
	        # code...
	        break;
	    }

	    return $charge;
	  }
	  /**
   * Captures the charge
   *
   * @param string $txnId
   * @param bool $isAuthAndCapture
   * @throws \Magento\Framework\Exception\LocalizedException
   */
  protected function _capture($txnId, $isAuthAndCapture = false)
  {
    /* If the capture has a corresponding authorisation before
     * authorise -> capture
     */
    if($isAuthAndCapture){

      // Check if order has valid state and status
      $orderStatus = $this->_order->getStatus();
      $orderState = $this->_order->getState();

      if (($orderState != Order::STATE_PROCESSING && $orderState != Order::STATE_PENDING_PAYMENT) ||
          ($orderStatus != self::STATUS_MAGENTO_AUTHORIZED)) {
        throw new \Magento\Framework\Exception\LocalizedException(__('Invalid order state or status.'));
      }

    } else {
      // Check if order has valid state and status
      $this->_verifyOrderState();
    }

    // Check if the transaction exists
    $this->_checkTransactionExists($txnId);

    $payment = $this->_order->getPayment();

    $parentTxnId = null;

    /* If the capture has a corresponding authorisation before
     * authorise -> capture
     */
    if($isAuthAndCapture){

      $authorizationTransaction = $payment->getAuthorizationTransaction();

      if (!$authorizationTransaction || !$authorizationTransaction->getId()) {
        throw new \Magento\Framework\Exception\LocalizedException(__('Cannot find payment authorization transaction.'));
      }

      if ($authorizationTransaction->getTxnType() != \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH) {
        throw new \Magento\Framework\Exception\LocalizedException(__('Incorrect payment transaction type.'));
      }
      $parentTxnId = $authorizationTransaction->getTxnId();
    }
 	$this->_order->setSubtotalInvoiced(0);
    $this->_order->setBaseSubtotalInvoiced(0);
    $this->_order->setTaxInvoiced(0);
    $this->_order->setBaseTaxInvoiced(0);
	foreach ($this->_order->getAllItems() as $item) {
		if($item->getLockedDoInvoice()){
			$item->setData('locked_do_invoice',null);
			$item->setData('locked_do_ship',null);
		}
    }
    if (!$this->_order->canInvoice()) {
      throw new \Magento\Framework\Exception\LocalizedException(__('Cannot create invoice for the order.'));
    }

    $amount = $this->_order->getBaseTotalDue();

    if($parentTxnId) {
      $payment->setParentTransactionId($parentTxnId);
      $payment->setShouldCloseParentTransaction(true);
    }

    // Capture
    $payment->setTransactionId($txnId)
            ->setPreparedMessage('')
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification($amount);

    $this->_logger->info($this->_helper->__("Payment Captured $amount"));

    $this->_orderRepository->save($this->_order);           

    // Invoice
    $invoice = $payment->getCreatedInvoice();
    
    if ($invoice) { 
      if ($this->_order->getCanSendNewEmailFlag()) {
        try {
          //$this->_orderSender->send($this->_order);
        } catch (\Exception $e) {
          $this->_logger->critical($e);
        }
      }   

      $this->_order->addStatusHistoryComment($this->_helper->__('Notified customer about invoice #%s.', $invoice->getIncrementId()))
                   ->setIsCustomerNotified(true);
      
      $this->_orderRepository->save($this->_order);                    
    }
  }
  /**
   * Authorises the charge
   *
   */
  protected function _authorise($txnId)
  {
    // Check if order has valid state
    $this->_verifyOrderState();
    // Check if the transaction exists
    $this->_checkTransactionExists($txnId);

    $amount  = $this->_order->getBaseTotalDue();

    $payment = $this->_order->getPayment();

    // Authorise the payment
    $payment->setTransactionId($txnId)
            ->setIsTransactionClosed(0)
            ->registerAuthorizationNotification($amount);

    $this->_logger->info($this->_helper->__("Payment Authorised"));

    $this->_order->setStatus(self::STATUS_MAGENTO_AUTHORIZED);
              
    $this->_orderRepository->save($this->_order);           

    if ($this->_order->getCanSendNewEmailFlag()) {
      try {
        //$this->_orderSender->send($this->_order);
      } catch (\Exception $e) {
        $this->_logger->critical($e);
      }
    }   
  }
	  /**
	   * Make sure addresses will be saved without validation errors
	   *
	   * @throws \Magento\Framework\Exception\LocalizedException
	   */
	  protected function _verifyOrderState()
	  {
	    $currentState = $this->_order->getState();

	    if ($currentState != Order::STATE_NEW) {
	      throw new \Magento\Framework\Exception\LocalizedException(__('Invalid order state.'));
	    }
	  }
	/**
	   * Checks if transaction exists 
	   *
	   * @throws \Magento\Framework\Exception\LocalizedException
	   */
	  protected function _checkTransactionExists($txnId)
	  {
	    $payment = $this->_order->getPayment();

	    if ($payment && $payment->getId()) {
	      $transaction = $payment->getTransaction($txnId);
	      if ($transaction && $transaction->getId()) {
	        throw new \Magento\Framework\Exception\LocalizedException(__('The payment transaction already exists.'));
	      }
	    }
	  }
}

