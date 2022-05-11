<?php
namespace Mirakl\Connector\Observer;

use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavIndexer;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\ActionFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Mirakl\Process\Model\Process;

class OfferImportAfterRefreshIndexObserver implements ObserverInterface
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var ActionFactory
     */
    private $actionFactory;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @param   IndexerRegistry         $indexerRegistry
     * @param   ActionFactory           $actionFactory
     * @param   ProductResourceFactory  $productResourceFactory
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ActionFactory $actionFactory,
        ProductResourceFactory $productResourceFactory
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->actionFactory   = $actionFactory;
        $this->productResource = $productResourceFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $skus = $observer->getEvent()->getSkus();
        if (empty($skus)) {
            return;
        }

        $productIds = $this->productResource->getProductsIdsBySkus($skus);
        if (empty($productIds)) {
            return;
        }

        /** @var Process $process */
        $process = $observer->getEvent()->getProcess();

        $process->output(__('Updating index for products...'), true);
        $indexers = [
            StockIndexer::INDEXER_ID,
            PriceIndexer::INDEXER_ID,   // Must be done after StockIndexer
            EavIndexer::INDEXER_ID,
        ];

        foreach ($indexers as $indexerId) {
            try {
                $idx = $this->indexerRegistry->get($indexerId);

                // We retest to be sure
                if (!$idx->isWorking() && !$idx->isScheduled()) {
                    $this->actionFactory->create($idx->getActionClass())
                        ->executeList($productIds);
                }

            } catch (\Exception $e) {
                // We must continue
            }
        }

        $process->output(__('Done!'));
    }
}