<?php
namespace Mirakl\Connector\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Connector\Helper\Offer\Stock as OfferStockHelper;
use Mirakl\Process\Model\Process;

class UpdateStockObserver implements ObserverInterface
{
    /**
     * @var OfferStockHelper
     */
    private $helper;

    /**
     * @param   OfferStockHelper  $offerStockHelper
     */
    public function __construct(OfferStockHelper $offerStockHelper)
    {
        $this->helper = $offerStockHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($product = $observer->getEvent()->getProduct()) {
            /** @var Product $product */
            $this->helper->updateOutOfStock([$product->getSku()]);
            $this->helper->updateInStock([$product->getSku()]);
        } elseif ($process = $observer->getEvent()->getProcess()) {
            /** @var Process $process */
            $skus = $observer->getEvent()->getSkus();

            if (empty($skus)) {
                return;
            }

            $process->output(__('Updating products stock...'), true);

            $this->helper->updateOutOfStock($skus);
            $this->helper->updateInStock($skus);

            $process->output(__('Done!'));
        }
    }
}