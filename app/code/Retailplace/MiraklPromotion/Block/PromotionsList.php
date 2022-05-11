<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Block;

use Magento\Framework\View\Element\Template;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class PromotionsList
 */
class PromotionsList extends Template
{
    /** @var \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface */
    private $promotionRepository;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[] */
    private $shopPromotions = [];

    /**
     * PromotionsList constructor
     *
     * @param \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface $promotionRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        PromotionRepositoryInterface $promotionRepository,
        TimezoneInterface $timezone,
        Template\Context $context,
        array $data = []
    ) {
        $this->promotionRepository = $promotionRepository;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * Get Shop Promotions
     *
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[]
     */
    public function getPromotionsList(): array
    {
        $shopId = $this->getRequest()->getParam('id');
        if (!isset($this->shopPromotions[$shopId])) {
            $this->shopPromotions[$shopId] = $this->promotionRepository->getActiveByShops([$shopId])->getItems();
        }

        return $this->shopPromotions[$shopId];
    }

    /**
     * Extract Media Url
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterface $promotion
     * @return string
     */
    public function getMediaUrl(PromotionInterface $promotion): string
    {
        $result = '';
        foreach ($promotion->getMedia() as $media) {
            $result = $media->getUrl();
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
