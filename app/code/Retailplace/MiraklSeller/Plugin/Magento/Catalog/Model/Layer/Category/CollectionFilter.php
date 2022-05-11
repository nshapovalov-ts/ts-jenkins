<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin\Magento\Catalog\Model\Layer\Category;

use Magento\Framework\App\Request\Http;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Search\Model\Query;
use Retailplace\Search\Model\SearchFilter;
use Magento\Search\Model\QueryFactory;

/**
 * Class CollectionFilter
 */
class CollectionFilter
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Visibility
     */

    protected $productVisibility;
    /**
     * @var SearchFilter
     */
    private $searchFilter;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @param Http $request
     * @param Visibility $productVisibility
     * @param SearchFilter $searchFilter
     * @param QueryFactory $queryFactory
     */
    public function __construct(
        Http $request,
        Visibility $productVisibility,
        SearchFilter $searchFilter,
        QueryFactory $queryFactory
    ) {
        $this->request = $request;
        $this->productVisibility = $productVisibility;
        $this->searchFilter = $searchFilter;
        $this->queryFactory = $queryFactory;
    }

    /**
     * @param \Magento\Catalog\Model\Layer\Category\CollectionFilter $subject
     * @param \Closure $proceed
     * @param Collection $collection
     * @param Category $category
     *
     * @return void
     */
    public function aroundFilter(
        \Magento\Catalog\Model\Layer\Category\CollectionFilter $subject,
        \Closure $proceed,
        Collection $collection,
        Category $category
    ) {
        $collection->setFlag('has_skip_add_price_data', true); // \Retailplace\MiraklFrontendDemo\Plugin\Block\Product\ProductCollectionPlugin::aroundAddPriceData

        $sellerView = $this->request->getParam('seller_view');

        /** @var Query $query */
        $query = $this->queryFactory->get();
        if (!$query->isQueryTextShort()) {
            $collection->addSearchFilter($query->getQueryText());
        }

        if (!$sellerView) {
            $proceed($collection, $category);
            return;
        }

        $this->searchFilter->setSellerView(true);

        $collection
            ->addAttributeToSelect(['small_image', 'mirakl_shop_ids'])
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
    }
}
