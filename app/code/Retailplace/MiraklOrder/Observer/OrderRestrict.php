<?php declare(strict_types=1);

namespace Retailplace\MiraklOrder\Observer;

use Exception;
use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeError;
use Psr\Log\LoggerInterface;

/**
 * Class OrderRestrict
 * @package Retailplace\MiraklOrder\Observer
 */
class OrderRestrict implements ObserverInterface
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var Cart
     */
    private $cartHelper;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var QuoteHelper
     */
    private $quoteHelper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * PaymentInformationManagement constructor.
     * @param CartManagementInterface $cartManagement
     * @param Cart $cartHelper
     * @param ManagerInterface $messageManager
     * @param Session $session
     * @param LoggerInterface $logger
     * @param QuoteHelper $quoteHelper
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        Cart $cartHelper,
        ManagerInterface $messageManager,
        Session $session,
        LoggerInterface $logger,
        QuoteHelper $quoteHelper
    ) {
        $this->cartHelper = $cartHelper;
        $this->messageManager = $messageManager;
        $this->cartManagement = $cartManagement;
        $this->session = $session;
        $this->logger = $logger;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @param Observer $observer
     * @return $this;
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var $quote Quote $quote */
        $quote = $event->getQuote();

        if (!$quote) {
            $quote = $this->session->getQuote();
        }

        $shippingFees = $this->quoteHelper->getShippingFees($quote);

        if ($shippingFees && $shippingFees->getErrors()) {
            $errors = [];
            /** @var ShippingFeeError $error */
            foreach ($shippingFees->getErrors() as $error) {
                $errors[] = __($error->getErrorCode());
            }

            if (!empty($errors)) {
                $this->logger->critical("Create Order Before: errors (" . implode(', ', $errors) . ")");
                $textMessage = __("No shipping information available.Please enter postcode in Australia, contact our support team for further assistance.");
                $this->messageManager->addErrorMessage($textMessage);
                throw new LocalizedException($textMessage);
            }
        }

        return $this;
    }
}
