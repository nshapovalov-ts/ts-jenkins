<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin\Magento\Catalog\Model\Layer\Search;

use Magento\Framework\App\Request\Http;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Visibility;
use Retailplace\Search\Model\SearchFilter;

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
     * @param Http $request
     * @param Visibility $productVisibility
     * @param SearchFilter $searchFilter
     */
    public function __construct(
        Http $request,
        Visibility $productVisibility,
        SearchFilter $searchFilter
    ) {
        $this->request = $request;
        $this->productVisibility = $productVisibility;
        $this->searchFilter = $searchFilter;
    }

    /**
     * @param \Magento\Catalog\Model\Layer\Search\CollectionFilter $subject
     * @param \Closure $proceed
     * @param Collection $collection
     * @param Category $category
     *
     * @return void
     */
    public function aroundFilter(
        \Magento\Catalog\Model\Layer\Search\CollectionFilter $subject,
        \Closure $proceed,
        Collection $collection,
        Category $category
    ) {
        $collection->setFlag('has_skip_add_price_data', true); // \Retailplace\MiraklFrontendDemo\Plugin\Block\Product\ProductCollectionPlugin::aroundAddPriceData

        $sellerView = $this->request->getParam('seller_view');
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
