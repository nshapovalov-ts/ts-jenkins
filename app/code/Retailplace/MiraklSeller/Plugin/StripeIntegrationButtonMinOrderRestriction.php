<?php
namespace Retailplace\MiraklSeller\Plugin;

use Magento\Checkout\Helper\Cart;
use Magento\Checkout\Model\GuestPaymentInformationManagement as CheckoutGuestPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklSeller\Helper\Data;
use StripeIntegration\Payments\Block\Button;

/**
 * Class StripeIntegrationButtonMinOrderRestriction
 */
class StripeIntegrationButtonMinOrderRestriction
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var Cart
     */
    private $cartHelper;
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * PaymentInformationManagement constructor.
     * @param CartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param Cart $cartHelper
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        LoggerInterface $logger,
        Data $helper,
        Cart $cartHelper,
        ManagerInterface $messageManager
    ) {
        $this->helper = $helper;
        $this->cartHelper = $cartHelper;
        $this->messageManager = $messageManager;
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
    }

    /**
     * @param Button $subject
     * @param $result
     * @return false
     */
    public function afterIsEnabled(
        Button $subject,
        $result
    ) {
        $isMinOrderAmountExist = $this->helper->isMinOrderAmountSellerExist();
        if ($isMinOrderAmountExist) {
            return false;
        }
        return $result;
    }
}
