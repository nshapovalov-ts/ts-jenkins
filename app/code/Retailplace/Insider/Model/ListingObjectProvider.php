<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Retailplace\Insider\Api\InsiderObjectProviderInterface;
use Magento\Framework\App\RequestInterface;
use Retailplace\Insider\Helper\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Block\Product\ListProduct;

/**
 * ListingObjectProvider class
 */
class ListingObjectProvider implements InsiderObjectProviderInterface
{
    /** @var Product */
    private $productHelper;

    /** @var RequestInterface */
    private $request;

    /** @var array */
    private $items = [];

    /** @var array */
    private $unique = [];

    /** @var ListProduct */
    private $listProduct;

    /**
     * ListingObjectProvider constructor
     *
     * @param RequestInterface $request
     * @param Product $productHelper
     * @param ListProduct $productList
     */
    public function __construct(
        RequestInterface $request,
        Product $productHelper,
        ListProduct $productList
    ) {
        $this->request = $request;
        $this->productHelper = $productHelper;
        $this->listProduct = $productList;
    }

    /**
     * Get config
     *
     * @return array|\array[][]
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $config = [];
        $products = $this->listProduct->getLoadedProductCollection();
        $products->setFlag('has_append_offers', true);
        if ($this->isPageAllowed()) {
            foreach ($products as $product) {
                if ($this->checkUnique($product->getId())) {
                    $this->items[] = $this->productHelper->getProductData($product);
                }
            }
            $config = ['listing' => ['items' => $this->items]];
        }

        return $config;
    }

    /**
     * Check unique
     *
     * @param int|string $id
     * @return bool
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    private function checkUnique($id): bool
    {
        $result = false;
        if (!in_array($id, $this->unique)) {
            $this->unique[] = $id;
            $result = true;
        }

        return $result;
    }

    /**
     * Check page
     *
     * @return bool
     */
    private function isPageAllowed(): bool
    {
        $visible = false;
        if ($this->request->getFullActionName() === 'catalogsearch_result_index' ||
            $this->request->getFullActionName() === 'catalog_category_view') {
            $visible = true;
        }

        if ($this->request->getParam('seller_view')) {
            $visible = false;
        }

        return $visible;
    }
}
