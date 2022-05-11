<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\ViewModel;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Helper\Data as ProductHelper;
use Mirakl\FrontendDemo\Helper\Offer;
use Retailplace\MiraklPromotion\Model\PromotionManagementFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class ProductDetailPromotions
 */
class ProductDetailPromotions implements ArgumentInterface
{
    /** @var \Magento\Catalog\Helper\Data */
    private $productHelper;

    /** @var \Retailplace\MiraklPromotion\Model\PromotionManagementFactory */
    private $promotionManagementFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Mirakl\FrontendDemo\Helper\Offer */
    private $offerHelper;

    /**
     * ProductDetailPromotions Constructor
     *
     * @param \Magento\Catalog\Helper\Data $productHelper
     * @param \Retailplace\MiraklPromotion\Model\PromotionManagementFactory $promotionManagementFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Mirakl\FrontendDemo\Helper\Offer $offerHelper
     */
    public function __construct(
        ProductHelper $productHelper,
        PromotionManagementFactory $promotionManagementFactory,
        TimezoneInterface $timezone,
        Offer $offerHelper
    ) {
        $this->productHelper = $productHelper;
        $this->promotionManagementFactory = $promotionManagementFactory;
        $this->timezone = $timezone;
        $this->offerHelper = $offerHelper;
    }

    /**
     * Get list of Promotions by Product
     *
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[]
     */
    public function getProductPromotions(): array
    {
        $product = $this->productHelper->getProduct();
        $this->offerHelper->getAllOffers($product);
        $promotionManagement = $this->promotionManagementFactory->create();

        return $promotionManagement->getPromotions([$product]);
    }

    /**
     * Hide Promotions Block initially for Configurable Products
     *
     * @return bool
     */
    public function hidePromotions(): bool
    {
        $result = false;
        $product = $this->productHelper->getProduct();
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $result = true;
        }

        return $result;
    }

    /**
     * Format Date
     *
     * @param string $date
     * @return string
     */
    public function getDateFormatted(string $date): string
    {
        return $this->timezone->date($date)->format('M j, Y');
    }
}
