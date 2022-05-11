<?php
namespace Mirakl\Connector\Observer;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\CatalogInventory\Observer\ProductQty;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SubtractQuoteInventoryObserver implements ObserverInterface
{
    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var ProductQty
     */
    protected $productQty;

    /**
     * @var ItemsForReindex
     */
    protected $itemsForReindex;

    /**
     * @param   StockManagementInterface    $stockManagement
     * @param   ProductQty                  $productQty
     * @param   ItemsForReindex             $itemsForReindex
     */
    public function __construct(
        StockManagementInterface $stockManagement,
        ProductQty $productQty,
        ItemsForReindex $itemsForReindex
    ) {
        $this->stockManagement = $stockManagement;
        $this->productQty = $productQty;
        $this->itemsForReindex = $itemsForReindex;
    }

    /**
     * This observer will override the default one:
     * @see \Magento\CatalogInventory\Observer\SubtractQuoteInventoryObserver::execute()
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        // If already processed, quit
        if ($quote->getInventoryProcessed()) {
            return $this;
        }

        $quoteItems = $quote->getAllItems();
        $itemsForReindex = [];
        $deletedItemIds = [];

        // Loop on quote items and remove Mirakl offers
        foreach ($quoteItems as $i => $quoteItem) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            if ($quoteItem->getMiraklOfferId() || in_array($quoteItem->getParentItemId(), $deletedItemIds)) {
                $deletedItemIds[] = $quoteItem->getId();
                unset($quoteItems[$i]);
            }
        }

        if (count($quoteItems)) {
            $items = $this->productQty->getProductQty($quoteItems);

            // Remember items
            $itemsForReindex = $this->stockManagement->registerProductsSale(
                $items,
                $quote->getStore()->getWebsiteId()
            );
        }

        $this->itemsForReindex->setItems($itemsForReindex);

        $quote->setInventoryProcessed(true);

        return $this;
    }
}
