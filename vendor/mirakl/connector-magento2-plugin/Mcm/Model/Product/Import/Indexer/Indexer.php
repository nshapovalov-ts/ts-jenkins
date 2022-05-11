<?php
namespace Mirakl\Mcm\Model\Product\Import\Indexer;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Registry;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Mirakl\Mci\Helper\Config as MciConfigHelper;
use Mirakl\Mcm\Helper\Config as McmConfigHelper;
use Mirakl\Mci\Model\Product\Import\Indexer\Indexer as MciIndexer;

class Indexer extends MciIndexer
{
    /**
     * @var McmConfigHelper
     */
    protected $mcmConfigHelper;

    /**
     * @var array
     */
    public $productIdsForIndexers = [];

    /**
     * @param   MciConfigHelper         $mciConfigHelper
     * @param   McmConfigHelper         $mcmConfigHelper
     * @param   ProductHelper           $catalogProduct
     * @param   IndexerRegistry         $indexerRegistry
     * @param   Registry                $registry
     * @param   CollectionFactory       $indexerCollectionFactory
     * @param   EventManagerInterface   $eventManager
     */
    public function __construct(
        MciConfigHelper $mciConfigHelper,
        McmConfigHelper $mcmConfigHelper,
        ProductHelper $catalogProduct,
        IndexerRegistry $indexerRegistry,
        Registry $registry,
        CollectionFactory $indexerCollectionFactory,
        EventManagerInterface $eventManager
    ) {
        parent::__construct(
            $mciConfigHelper,
            $catalogProduct,
            $indexerRegistry,
            $registry,
            $indexerCollectionFactory,
            $eventManager
        );

        $this->mcmConfigHelper = $mcmConfigHelper;
    }

    /**
     * @return  bool
     */
    public function shouldIndex()
    {
        if ($this->mcmConfigHelper->isEnabledIndexingImport()) {
            return true;
        }

        return false;
    }

    /**
     * @return  bool
     */
    public function shouldReindex()
    {
        return $this->mcmConfigHelper->isEnabledIndexingImportAfter()
            && !$this->mcmConfigHelper->isEnabledIndexingImport();
    }
}
