<?php
/**
 * Retailplace_Offerdetail
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Offerdetail\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ConfigProvider for config
 */
class ConfigProvider
{
    /** @var string */
    const IS_LEAD_TIME_DEFAULT_VALUE_ENABLE = 'mirakl_frontend/offer/enable_default_lead_time';
    const LEAD_TIME_DEFAULT_VALUE = 'mirakl_frontend/offer/default_value_lead_time';
    const MIRAKL_SELLER_YOUTUBE_VIDEO_ENABLE = 'mirakl_frontend/seller_youtube_settings/enable';
    const MIRAKL_SELLER_YOUTUBE_VIDEO_LINK = 'mirakl_frontend/seller_youtube_settings/youtube_video_link';
    const MIRAKL_SELLER_YOUTUBE_VIDEO_THUMBNAIL = 'mirakl_frontend/seller_youtube_settings/youtube_thumbnail_link';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        ScopeConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isDefaultValueEnable(): bool
    {
        return $this->config->isSetFlag(self::IS_LEAD_TIME_DEFAULT_VALUE_ENABLE);
    }

    /**
     * @return int
     */
    public function leadTimeDefaultValue(): int
    {
        return (int)$this->config->getValue(self::LEAD_TIME_DEFAULT_VALUE);
    }

    /**
     * @return bool
     */
    public function isYouTubeVideoEnable(): bool
    {
        return $this->config->isSetFlag(self::MIRAKL_SELLER_YOUTUBE_VIDEO_ENABLE);
    }

    /**
     * @return string
     */
    public function getYouTubeVideoLink(): string
    {
        return (string)$this->config->getValue(self::MIRAKL_SELLER_YOUTUBE_VIDEO_LINK);
    }

    /**
     * @return string
     */
    public function getYouTubeVideoThumbnail(): string
    {
        return (string)$this->config->getValue(self::MIRAKL_SELLER_YOUTUBE_VIDEO_THUMBNAIL);
    }
}
