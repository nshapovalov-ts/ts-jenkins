<?php declare(strict_types=1);
/**
 * Validate minimum order amount in sales_order before place an order.
 */

namespace Retailplace\MiraklSeller\Observer;

use Exception;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Retailplace\MiraklSeller\Helper\Data;

class MinOrderRestrict implements ObserverInterface
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var Retailplace\MiraklSeller\Helper\Data
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
        Data $helper,
        Cart $cartHelper,
        ManagerInterface $messageManager
    ) {
        $this->helper = $helper;
        $this->cartHelper = $cartHelper;
        $this->messageManager = $messageManager;
        $this->cartManagement = $cartManagement;
    }
    /**
     * @param Observer $observer
     * @return $this;
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var $quote \Magento\Quote\Model\Quote $quote */
        $quote = $event->getQuote();

        $isMinOrderAmountExist = $this->helper->isMinOrderAmountSellerExist(true, $quote);
        if ($isMinOrderAmountExist) {
            $this->messageManager->addErrorMessage(strip_tags($isMinOrderAmountExist));
            throw new LocalizedException(__(strip_tags($isMinOrderAmountExist)));
        }

        return $this;
    }
}
