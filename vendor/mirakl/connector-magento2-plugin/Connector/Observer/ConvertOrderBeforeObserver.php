<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Connector\Helper\Config as ConfigHelper;

class ConvertOrderBeforeObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @param   ConfigHelper    $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Creates the Mirakl order when Magento order status is one of those defined in configuration
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $flag = $this->configHelper->isAutoScoreOrder();
        $order->setMiraklAutoScore($flag);
    }
}