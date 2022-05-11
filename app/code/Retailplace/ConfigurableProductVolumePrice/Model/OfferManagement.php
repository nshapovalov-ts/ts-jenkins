<?php

/**
 * Retailplace_ConfigurableProductVolumePrice
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ConfigurableProductVolumePrice\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Api\LinkManagementInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Mirakl\FrontendDemo\Helper\Offer;
use Psr\Log\LoggerInterface;

/**
 * Class OfferManagement
 */
class OfferManagement
{
    /** @var \Mirakl\FrontendDemo\Helper\Offer */
    private $offerHelper;

    /** @var \Magento\ConfigurableProduct\Api\LinkManagementInterface */
    private $linkManagement;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * OfferManagement constructor.
     *
     * @param \Mirakl\FrontendDemo\Helper\Offer $offerHelper
     * @param \Magento\ConfigurableProduct\Api\LinkManagementInterface $linkManagement
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Offer $offerHelper,
        LinkManagementInterface $linkManagement,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->offerHelper = $offerHelper;
        $this->linkManagement = $linkManagement;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $product
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getSimpleProductsList(?ProductInterface $product): array
    {
        //Possibly product is not loaded on this step.
        if ($product->getTypeId() === null) {
            try {
                $product = $this->productRepository->getById($product->getId());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $product = null;
            }
        }

        $productList = [];
        if ($product) {
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $productList = $this->linkManagement->getChildren($product->getSku());
            } else {
                $productList = [$product];
            }
        }

        return $productList;
    }

    /**
     * Get Product Offer Data
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $parentProduct
     * @return \Mirakl\Connector\Model\Offer[]
     */
    public function getProductPriceRanges(?ProductInterface $parentProduct): array
    {
        $offersList = [];
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($this->getSimpleProductsList($parentProduct) as $product) {
            $offer = $this->offerHelper->getBestOffer($product);
            if ($offer) {
                $offersList[$product->getSku()] = [
                    'offer' => $offer,
                    'price_ranges' => $offer->getPriceRanges(),
                    'discount_ranges' => $offer->isDiscountPriceValid() ? $offer->getDiscount()->getRanges() : []
                ];
            }
        }

        return $offersList;
    }
}
