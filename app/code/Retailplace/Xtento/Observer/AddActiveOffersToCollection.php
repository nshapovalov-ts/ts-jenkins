<?php
/**
 * Retailplace_Xtento
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */

namespace Retailplace\Xtento\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AddActiveOffersToCollection implements ObserverInterface
{
    /**
     * @var \Retailplace\MiraklSellerAdditionalField\Helper\Data
     */
    private $helper;

    /**
     * AddActiveOffersToCollection constructor.
     * @param \Retailplace\MiraklSellerAdditionalField\Helper\Data $helper
     */
    public function __construct(\Retailplace\MiraklSellerAdditionalField\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Observer for xtento_productexport_export_before_prepare_collection
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $collection = $event->getCollection();
        $this->helper->addShopIdsFilter($collection);
    }
}
