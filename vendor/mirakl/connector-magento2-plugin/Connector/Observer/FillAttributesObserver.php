<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Connector\Helper\Offer\Catalog as OfferCatalogHelper;
use Mirakl\Process\Model\Process;

class FillAttributesObserver implements ObserverInterface
{
    /**
     * @var OfferCatalogHelper
     */
    private $helper;

    /**
     * @param   OfferCatalogHelper  $offerCatalogHelper
     */
    public function __construct(OfferCatalogHelper $offerCatalogHelper)
    {
        $this->helper = $offerCatalogHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $skus = $observer->getEvent()->getSkus();

        if (!empty($skus)) {
            /** @var Process $process */
            $process = $observer->getEvent()->getProcess();

            $process->output(__('Updating Mirakl shops and offer states attributes for products...'), true);
            $this->helper->updateAttributes($skus);
            $process->output(__('Done!'));
        }
    }
}