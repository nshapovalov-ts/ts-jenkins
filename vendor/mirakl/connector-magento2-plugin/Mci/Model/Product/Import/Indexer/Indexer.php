<?php
namespace Mirakl\Mci\Model\Product\Import\Indexer;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Indexer\Product\Category as CategoryIndexer;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavIndexer;
use Magento\Catalog\Model\Indexer\Product\Flat\Processor as ProductFlatIndexer;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexer;
use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor as ProductRuleIndexer;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor as FulltextIndexer;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Registry;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Mirakl\Mci\Helper\Config as MciConfigHelper;

class Indexer
{
    /**
     * @var MciConfigHelper
     */
    protected $mciConfigHelper;

    /**
     * @var ProductHelper
     */
    protected $_catalogProduct;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CollectionFactory
     */
    protected $indexerCollectionFactory;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var array
     */
    protected $managedIndexers = [
        PriceIndexer::INDEXER_ID,
        CategoryIndexer::INDEXER_ID,
        EavIndexer::INDEXER_ID,
        ProductFlatIndexer::INDEXER_ID,
        StockIndexer::INDEXER_ID,
        ProductRuleIndexer::INDEXER_ID,
        FulltextIndexer::INDEXER_ID
    ];

    /**
     * @var array
     */
    public $productIdsForIndexers = [];

    /**
     * @param   MciConfigHelper         $mciConfigHelper
     * @param   ProductHelper           $catalogProduct
     * @param   IndexerRegistry         $indexerRegistry
     * @param   Registry                $registry
     * @param   CollectionFactory       $indexerCollectionFactory
     * @param   EventManagerInterface   $eventManager
     */
    public function __construct(
        MciConfigHelper $mciConfigHelper,
        ProductHelper $catalogProduct,
        IndexerRegistry $indexerRegistry,
        Registry $registry,
        CollectionFactory $indexerCollectionFactory,
        EventManagerInterface $eventManager
    ) {
        $this->mciConfigHelper          = $mciConfigHelper;
        $this->_catalogProduct          = $catalogProduct;
        $this->indexerRegistry          = $indexerRegistry;
        $this->registry                 = $registry;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->_eventManager            = $eventManager;
    }

    /**
     * Init available indexers
     */
    public function initIndexers()
    {
        if ($this->shouldIndex()) {
            return;
        }

        foreach ($this->managedIndexers as $indexerId) {
            $this->addIndexer($indexerId);
        }

        // Allow to add other indexers
        $this->_eventManager->dispatch('mirakl_product_import_indexer_init', [
            'indexer' => $this
        ]);

        $this->registry->register('mirakl_import_no_indexer', true, true);
    }

    /**
     * @param   string  $indexerId
     */
    public function addIndexer($indexerId)
    {
        if ($this->isEnabled($indexerId)) {
            return;
        }

        try {
            // Test if indexer exists
            $indexer = $this->indexerRegistry->get($indexerId);
            if ($indexer && !$indexer->isScheduled()) {
                $this->productIdsForIndexers[$indexerId] = [];
                if (!$this->shouldReindex()) {
                    $indexer->invalidate();
                }
            }
        } catch (\Exception $e) {
            // We must continue
        }
    }

    /**
     * @param   string  $indexerId
     * @return  bool
     */
    public function isEnabled($indexerId)
    {
        return isset($this->productIdsForIndexers[$indexerId]);
    }

    /**
     * @param   Product $product
     */
    public function setIdsToIndex(Product $product)
    {
        if (!$this->shouldReindex()) {
            return;
        }

        if ($this->isEnabled(PriceIndexer::INDEXER_ID)
            && ($product->isObjectNew() || $this->_catalogProduct->isDataForPriceIndexerWasChanged($product))
        ) {
            $this->productIdsForIndexers[PriceIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(EavIndexer::INDEXER_ID)
            && ($product->isObjectNew() || $product->isDataChanged())
        ) {
            $this->productIdsForIndexers[EavIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(CategoryIndexer::INDEXER_ID)
            && ($this->_catalogProduct->isDataForProductCategoryIndexerWasChanged($product) || $product->isDeleted())
        ) {
            $this->productIdsForIndexers[CategoryIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(ProductFlatIndexer::INDEXER_ID)) {
            $this->productIdsForIndexers[ProductFlatIndexer::INDEXER_ID][] = $product->getId();
        }

        //if ($this->isEnabled(StockIndexer::INDEXER_ID)) {
            // Marketplace stock do not change when importing product
        //}

        if ($this->isEnabled(ProductRuleIndexer::INDEXER_ID)) {
            $this->productIdsForIndexers[ProductRuleIndexer::INDEXER_ID][] = $product->getId();
        }

        if ($this->isEnabled(FulltextIndexer::INDEXER_ID)) {
            $this->productIdsForIndexers[FulltextIndexer::INDEXER_ID][] = $product->getId();
        }

        // Allow to add other indexers
        $this->_eventManager->dispatch('mirakl_product_import_indexer_add_id', [
            'indexer' => $this,
            'product' => $product,
        ]);
    }

    /**
     * @return  array
     */
    public function getIdsToIndex()
    {
        return $this->productIdsForIndexers;
    }

    /**
     * @return  bool
     */
    public function shouldIndex()
    {
        if ($this->mciConfigHelper->isEnabledIndexingImport()) {
            return true;
        }

        return false;
    }

    /**
     * @return  bool
     */
    public function shouldReindex()
    {
        return $this->mciConfigHelper->isEnabledIndexingImportAfter()
            && !$this->mciConfigHelper->isEnabledIndexingImport();
    }

    /**
     * @throws  \Exception
     */
    public function reindex()
    {
        if (!$this->shouldReindex()) {
            return;
        }

        $this->registry->unregister('mirakl_import_no_indexer');

        foreach ($this->productIdsForIndexers as $indexerId => $productIds) {
            try {
                $idx = $this->indexerRegistry->get($indexerId);

                // We retest to be sure
                if (!$idx->isWorking() && !empty($productIds)) {
                    $idx->reindexList($productIds);
                }
            } catch (\Exception $e) {
                // We must continue
            }
        }
    }
}