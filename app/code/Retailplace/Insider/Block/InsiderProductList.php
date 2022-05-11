<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Block;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Reports\Block\Product\Viewed as ReportProductViewed;
use Magento\Review\Model\Review;
use Mirakl\Connector\Model\Offer;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Retailplace\CustomerAccount\Helper\ApprovalContext;
use Retailplace\MiraklPromotion\Model\PromotionManagement;
use Magento\Directory\Model\CurrencyFactory;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Retailplace\MiraklFrontendDemo\Helper\Data as MiraklHelper;
use Magento\Catalog\Helper\Output as OutputHelper;
use Retailplace\Recentview\Block\ListingTabsRecentlyViewed;
use Sm\Market\Helper\Data as SmHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class InsiderProductList
 */
class InsiderProductList extends ListingTabsRecentlyViewed
{
    /** @var Session */
    private $customerSession;

    /** @var CurrencyFactory */
    private $currencyFactory;

    /** @var OfferHelper */
    private $offerHelper;

    /** @var MiraklHelper */
    private $miraklHelper;

    /** @var ApprovalContext */
    private $approvalHelper;

    /** @var OutputHelper */
    private $outputHelper;

    /** @var SmHelper */
    private $smHelper;

    /** @var Json */
    private $serializer;

    /**
     * InsiderProductList constructor
     *
     * @param Json $serializer
     * @param SmHelper $smHelper
     * @param OutputHelper $outputHelper
     * @param ApprovalContext $approvalHelper
     * @param MiraklHelper $miraklHelper
     * @param CurrencyFactory $currencyFactory
     * @param OfferHelper $offerHelper
     * @param ObjectManagerInterface $objectManager
     * @param ResourceConnection $resource
     * @param Visibility $catalogProductVisibility
     * @param Review $review
     * @param Context $context
     * @param Json $jsonSerializer
     * @param ReportProductViewed $reportProductViewed
     * @param Session $customerSession
     * @param EncoderInterface $urlEncoder
     * @param RedirectInterface $redirect
     * @param AttributesVisibilityManagement $attributesVisibilityManagement
     * @param PromotionManagement $promotionManagement
     * @param TimezoneInterface $timezone
     * @param array $data
     * @param array|null $attr
     * @throws NoSuchEntityException
     */
    public function __construct(
        Json $serializer,
        SmHelper $smHelper,
        OutputHelper $outputHelper,
        ApprovalContext $approvalHelper,
        MiraklHelper $miraklHelper,
        CurrencyFactory $currencyFactory,
        OfferHelper $offerHelper,
        ObjectManagerInterface $objectManager,
        ResourceConnection $resource,
        Visibility $catalogProductVisibility,
        Review $review,
        Context $context,
        SerializerJson $jsonSerializer,
        ReportProductViewed $reportProductViewed,
        Session $customerSession,
        EncoderInterface $urlEncoder,
        RedirectInterface $redirect,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        PromotionManagement $promotionManagement,
        TimezoneInterface $timezone,
        array $data = [],
        array $attr = null
    ) {
        $this->serializer = $serializer;
        $this->smHelper = $smHelper;
        $this->outputHelper = $outputHelper;
        $this->approvalHelper = $approvalHelper;
        $this->customerSession = $customerSession;
        $this->currencyFactory = $currencyFactory;
        $this->offerHelper = $offerHelper;
        $this->miraklHelper = $miraklHelper;

        parent::__construct(
            $objectManager,
            $resource,
            $catalogProductVisibility,
            $review,
            $context,
            $jsonSerializer,
            $reportProductViewed,
            $customerSession,
            $urlEncoder,
            $redirect,
            $attributesVisibilityManagement,
            $promotionManagement,
            $timezone,
            $data,
            $attr
        );
    }

    /**
     * Check customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get media url
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMediaUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get currency symbol
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrencySymbol(): string
    {
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        $currency = $this->currencyFactory->create()->load($currencyCode);

        return $currency->getCurrencySymbol();
    }

    /**
     * Get best offer
     *
     * @param Product $product
     * @return Offer|null
     */
    public function getBestOffer(Product $product): ?Offer
    {
        return $this->offerHelper->getBestOffer($product);
    }

    /**
     * Get minimum qty html
     *
     * @param Product $product
     * @param string $minimum
     * @return string
     */
    public function getMinimumQtyHtml(Product $product, string $minimum): string
    {
        return $this->miraklHelper->getMinimumQtyHtml($product, $minimum);
    }

    /**
     * Check is approval
     *
     * @return mixed|null
     */
    public function checkIsApproval()
    {
        return $this->approvalHelper->checkIsApproval();
    }

    /**
     * @param Product $product
     * @param string $attributeHtml
     * @param string $attributeName
     * @return string
     * @throws LocalizedException
     */
    public function productAttribute(Product $product, string $attributeHtml, string $attributeName): string
    {
        return $this->outputHelper->productAttribute($product, $attributeHtml, $attributeName);
    }

    /**
     * Get advanced config
     *
     * @param string $name
     * @return mixed
     */
    public function getAdvanced(string $name)
    {
        return $this->smHelper->getAdvanced($name);
    }

    /**
     * Json encode
     *
     * @param array $data
     * @return bool|string
     */
    public function jsonSerialize(array $data)
    {
        return $this->serializer->serialize($data);
    }
}
