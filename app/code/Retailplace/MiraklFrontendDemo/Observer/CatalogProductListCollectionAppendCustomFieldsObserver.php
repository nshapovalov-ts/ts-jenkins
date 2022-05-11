<?php declare(strict_types=1);
/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\MiraklFrontendDemo\Observer;

use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Zend_Db_Expr;
use Mirakl\Connector\Helper\Offer as ConnectorOfferHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Class CatalogProductListCollectionAppendCustomFieldsObserver
 */
class CatalogProductListCollectionAppendCustomFieldsObserver implements ObserverInterface
{
    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var ShopCollectionFactory
     */
    protected $shopCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConnectorOfferHelper
     */
    private $connectorOfferHelper;

    /**
     * @param OfferHelper $offerHelper
     * @param ShopCollectionFactory $shopCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ConnectorOfferHelper $connectorOfferHelper
     */
    public function __construct(
        OfferHelper $offerHelper,
        ShopCollectionFactory $shopCollectionFactory,
        StoreManagerInterface $storeManager,
        ConnectorOfferHelper $connectorOfferHelper
    ) {
        $this->offerHelper = $offerHelper;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->storeManager = $storeManager;
        $this->connectorOfferHelper = $connectorOfferHelper;
    }

    /**
     * Append Custom Fields
     *
     * @param EventObserver $observer
     * @return CatalogProductListCollectionAppendCustomFieldsObserver
     */
    public function execute(EventObserver $observer): CatalogProductListCollectionAppendCustomFieldsObserver
    {
        /** @var  Collection $productCollection */
        $productCollection = $observer->getEvent()->getCollection();

        //disable adding stock information via plugin "add_stock_information"
        //vendor/magento/module-catalog-inventory/etc/frontend/di.xml:12
        $productCollection->setFlag('has_stock_status_filter', true);
        $productCollection->setFlag('has_append_offers', true);
        $productCollection->setFlag('has_skip_saleable_check', true);
        $productCollection->getSelect()->columns([
            'is_salable' => new Zend_Db_Expr(1)
        ]);

        return $this;
    }
}
