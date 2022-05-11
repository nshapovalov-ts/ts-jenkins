<?php
namespace Retailplace\MiraklSeller\Observer;

use Magento\Checkout\Helper\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Retailplace\MiraklSeller\Helper\Data;
use WeltPixel\GoogleTagManager\lib\Google\Exception;

/**
 * Class RestrictWebsite
 * @package Retailplace\MiraklSeller\Model
 */
class ZipRestrictWebsite implements ObserverInterface
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
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $isMinOrderAmountExist = $this->helper->isMinOrderAmountSellerExist(true);
        if ($isMinOrderAmountExist) {
            $this->messageManager->addNotice($isMinOrderAmountExist);
            throw new LocalizedException(__(strip_tags($isMinOrderAmountExist)));
        }
        return $this;
    }
}
