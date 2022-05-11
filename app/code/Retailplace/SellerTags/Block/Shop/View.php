<?php

/**
 * Retailplace_SellerTags
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerTags\Block\Shop;

use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\FrontendDemo\Block\Shop\View as MiraklShopView;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Retailplace\MiraklShop\Model\Synchronizer\ShopUpdater;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Retailplace\Offerdetail\Model\ConfigProvider;

/**
 * Class View implements logic for Block view
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class View extends MiraklShopView
{
    /** @var string */
    const PLACEHOLDER_YOUTUBE_ID = '{{YOUTUBE_ID}}';

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * View Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TimezoneInterface $timezone,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        DateTime $dateTime,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);

        $this->timezone = $timezone;
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
        $this->dateTime = $dateTime;
        $this->configProvider = $configProvider;
    }

    /**
     * Get Tags Content
     *
     * @return string
     */
    public function getTagsBlockContent(): string
    {
        $result = [];
        $shopInfo = $this->getShop()->getAdditionalInfo();
        $additionalData = $shopInfo->getData('additional_field_values') ?? [];
        foreach ($additionalData as $attribute) {
            if ($attribute['code'] == ShopUpdater::CUSTOM_TAGS && is_array($attribute['value'])) {
                if (in_array(ShopUpdater::OPEN_DURING_XMAS_VALUE, $attribute['value'])) {
                    $result[] = __('Accepting orders throughout the holiday period');
                }
                if (in_array(ShopUpdater::SLOWER_THAN_AVERAGE_VALUE, $attribute['value'])) {
                    $result[] = __('Slower than average dispatch times may apply');
                }
            }

            if ($attribute['code'] == ShopUpdater::LASTDATE) {
                $date = $this->timezone->date($attribute['value'])->format('d M');
                $result[] = __('Accepting orders until: %1', $date);
            }
        }

        return implode(', ', $result);
    }

    /**
     * Check is shop closed
     *
     * @return bool
     */
    public function isShopHolidayClosed($shop)
    {
        $result = false;
        $now = $this->dateTime->gmtDate();
        if ($now > $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_FROM)
            && $now < $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_TO)
        ) {
            $result = true;
        }

        return $result;
    }

    /**
     * Get Attribute Label
     *
     * @param \Mirakl\Core\Model\Shop $shop
     * @return string
     */
    public function getClosedShopLabel($shop): string
    {
        $result = '';
        $closedToDate = $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_TO);
        if ($closedToDate) {
            $label = $this->attributesVisibilityManagement
                ->getAttributeLabelByCode(SellerTagsAttributes::PRODUCT_CLOSED_TO);
            $date = $this->timezone->date(
                strtotime($closedToDate)
            )->format('d/m');
            $result = sprintf($label . ' %s', $date);
        }

        return $result;
    }

    /**
     * Get Max Lead Time To Ship
     *
     * @param \Mirakl\Core\Model\Shop $shop
     * @return string
     */
    public function getLeadTimeToShipLabel(\Mirakl\Core\Model\Shop $shop): string
    {
        $maxLeadTimeToShip = (int) $shop->getData(SellerTagsAttributes::SHOP_LEADTIME_TO_SHIP);
        if (!$maxLeadTimeToShip) {
            $maxLeadTimeToShip = $this->configProvider->leadTimeDefaultValue();
        }

        return __('shipped in %1 %2 ', $maxLeadTimeToShip, ($maxLeadTimeToShip === 1 ? 'day' : 'days'))->render();
    }

    /**
     * @param \Mirakl\Core\Model\Shop $shop
     * @return bool
     */
    public function isYouTubeEnable(\Mirakl\Core\Model\Shop $shop): bool
    {
        return $this->configProvider->isYouTubeVideoEnable() && $shop->getData(SellerTagsAttributes::VIDEO_ID);
    }

    /**
     * @param \Mirakl\Core\Model\Shop $shop
     * @return void
     */
    public function getYoutubeVideoLink(\Mirakl\Core\Model\Shop $shop):string
    {
        return strtr(
            $this->configProvider->getYouTubeVideoLink(),
            [
                self::PLACEHOLDER_YOUTUBE_ID  => $shop->getData(SellerTagsAttributes::VIDEO_ID),
            ]
        );
    }

    /**
     * @param \Mirakl\Core\Model\Shop $shop
     * @return void
     */
    public function getYoutubeThumbnailLink(\Mirakl\Core\Model\Shop $shop):string
    {
        return strtr(
            $this->configProvider->getYouTubeVideoThumbnail(),
            [
                self::PLACEHOLDER_YOUTUBE_ID  => $shop->getData(SellerTagsAttributes::VIDEO_ID),
            ]
        );
    }
}
