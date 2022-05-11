<?php
namespace Retailplace\MiraklSeller\Observer;

use Magento\Checkout\Helper\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Retailplace\MiraklSeller\Helper\Data;

/**
 * Class RestrictWebsite
 */
class RestrictWebsite implements ObserverInterface
{

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
     * RestrictWebsite constructor.
     * @param Data $helper
     * @param Cart $cartHelper
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Data $helper,
        Cart $cartHelper,
        ManagerInterface $messageManager
    ) {
        $this->helper = $helper;
        $this->cartHelper = $cartHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $isMinOrderAmountExist = $this->helper->isMinOrderAmountSellerExist(true);
        if ($isMinOrderAmountExist) {
            $this->messageManager->addNotice($isMinOrderAmountExist);
            $redirectUrl = $this->cartHelper->getCartUrl();
            $observer->getControllerAction()->getResponse()->setRedirect($redirectUrl);
        }
        return $this;
    }
}
