<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin\Magento\Catalog\Model\Layer;

use Magento\Framework\Stdlib\DateTime;
use Retailplace\Search\Model\SearchFilter;
use Retailplace\MiraklSeller\Helper\Seller;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Retailplace\MiraklSeller\Controller\Index\Index;
use Amasty\Shopby\Model\Layer\Filter\IsNew as IsNewFilter;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Request\Http;
use Retailplace\AuPost\Api\Data\AttributesInterface;
use Retailplace\MiraklPromotion\Api\Data\ProductAttributesInterface;
use Magento\Framework\Registry;
use Amasty\Shopby\Plugin\Elasticsearch\Model\Adapter\DataMapper\IsNew;
use Retailplace\CatalogSort\Api\Data\ProductSortScoreAttributesInterface;
use Retailplace\MiraklSeller\Helper\Data as SellerBlockHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Session;

/**
 * Class Category
 */
class Category
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var SellerBlockHelper
     */
    private $sellerBlockHelper;

    /**
     * @var SearchFilter
     */
    private $searchFilter;

    /**
     * Layer constructor.
     *
     * @var ProductCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Http $request
     * @param TimezoneInterface $timeZone
     * @param SellerBlockHelper $sellerBlockHelper
     * @param SearchFilter $searchFilter
     * @param Registry $coreRegistry
     * @param ProductCollectionFactory $collectionFactory
     * @param Session $customerSession
     */
    public function __construct(
        Http $request,
        Registry $coreRegistry,
        ProductCollectionFactory $collectionFactory,
        Session $customerSession,
        TimezoneInterface $timeZone,
        SellerBlockHelper $sellerBlockHelper,
        SearchFilter $searchFilter
    ) {
        $this->request = $request;
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
        $this->timeZone = $timeZone;
        $this->sellerBlockHelper = $sellerBlockHelper;
        $this->searchFilter = $searchFilter;
    }

    /**
     * afterPrepareProductCollection method
     *
     * @param \Magento\Catalog\Model\Layer\Category $subject
     * @param \Magento\Catalog\Model\Layer\Category $result
     * @param Collection $collection
     *
     * @return \Magento\Catalog\Model\Layer\Category
     */
    public function afterPrepareProductCollection(
        \Magento\Catalog\Model\Layer\Category $subject,
        \Magento\Catalog\Model\Layer\Category $result,
        Collection $collection
    ): \Magento\Catalog\Model\Layer\Category {
        $routeName = $this->request->getRouteName();

        //prepare product collection for page
        switch ($routeName) {
            case "marketplace":
                $result->getCurrentCategory()->setData(
                    'default_sort_by',
                    ProductSortScoreAttributesInterface::ATTRIBUTE_CODE
                );

                $this->marketplacePrepareProductCollection($collection);
                $this->coreRegistry->register('current_category', $result->getCurrentCategory());
                break;
            case "sale":
                $collection->addFieldToFilter('am_on_sale', 1);
                break;
            case "madeinau":
                $collection->addFieldToFilter('made_in_au', 1);
                break;
            case "clearance":
                $result->getCurrentCategory()->setData('default_sort_by', 'newly_added');
                $collection->addFieldToFilter('clearance', 1);
                $this->coreRegistry->register('current_category', $result->getCurrentCategory());
                break;
            case "au_post":
                $collection->addFieldToFilter(AttributesInterface::PRODUCT_AU_POST, 1);
                break;
            case "boutique":
                $collection->addFieldToFilter('boutique', 1);
                break;
            case "seller-specials":
                $collection->addFieldToFilter(ProductAttributesInterface::SELLER_SPECIALS, 1);
                break;
            case Index::NEW_SUPPLIERS_PAGE:
                $this->addNewSellersFilter($collection);
                break;
            case Index::NEW_PRODUCTS_PAGE:
                $this->addNewProductsFilter($collection);
                $this->coreRegistry->register('current_category', $result->getCurrentCategory());
                $result->getCurrentCategory()->setData('default_sort_by', 'name');
                break;
            case "reorder":
                $collection->addFieldToFilter('ids', $this->getProductIdsFromReorder());
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * Marketplace Prepare Product Collection
     *
     * @param $collection
     * @return void
     */
    private function marketplacePrepareProductCollection($collection)
    {
        $sellerId = $this->request->getParam('id');

        if ($sellerId) {
            $connection = $collection->getConnection();

            $subSelect = $connection->select()->from(['ms' => 'mirakl_shop'], ['eav_option_id'])->where('id = ?', $sellerId)->limit(1);
            $shopId = $connection->fetchOne($subSelect);

            if (!empty($shopId)) {
                $collection->addFieldToFilter('mirakl_shop_ids', $shopId);
                $collection->setFlag('has_shop_ids_filter', true);
            }
        }
    }

    /**
     * Add new products filter
     *
     * @param Collection $collection
     *
     * @return void
     */
    private function addNewProductsFilter(Collection $collection): void
    {
        $collection->addFieldToFilter(IsNew::FIELD_NAME, IsNewFilter::FILTER_NEW);
    }

    /**
     * Add filtering by sellers recently joined
     *
     * @param Collection $collection
     *
     * @return void
     */
    private function addNewSellersFilter(Collection $collection): void
    {
        $subSelect = $collection->getConnection()
            ->select()
            ->from(['ms' => 'mirakl_shop'], [Seller::EAV_OPTION_ID])
            ->where('date_created >= ?', $this->sellerBlockHelper->getIsNewFromDate());
        $shopIds = $collection->getConnection()->fetchAssoc($subSelect);
        $shopIds = array_keys($shopIds) ?? [];
        $this->searchFilter->setNewSellerViewIds($shopIds);
        $collection->addFieldToFilter('mirakl_shop_ids', ['in' => $shopIds]);
        $collection->setFlag('has_shop_ids_filter', true);
    }

    /**
     * @return array
     */
    private function getProductIdsFromReorder(): array
    {
        $productCollection = $this->collectionFactory->create();
        $productCollection->getSelect()->joinInner(
            'sales_order_item',
            'sales_order_item.product_id = e.entity_id',
            'order_id'
        );
        $productCollection->getSelect()->joinInner(
            'sales_order',
            'sales_order.entity_id = sales_order_item.order_id',
            ['created_at', 'customer_id']
        );
        $productCollection->addFilter('customer_id', $this->customerSession->getCustomerId());
        $productCollection->getSelect()->where('parent_item_id is null');
        $productCollection->getSelect()->order('sales_order.created_at desc');
        $productCollection->getSelect()->group('e.entity_id');
        $items = array_keys($productCollection->getItems());
        return empty($items) ? [0] : $items;
    }
}
