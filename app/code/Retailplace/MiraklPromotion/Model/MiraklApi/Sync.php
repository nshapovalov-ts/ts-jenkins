<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\MiraklApi;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Helper\ClientHelper\MMP;
use Mirakl\Api\Model\Client\ClientManager;
use Mirakl\Api\Model\Log\LoggerManager;
use Mirakl\Api\Model\Log\RequestLogValidator;
use Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionCollection;
use Mirakl\MMP\FrontOperator\Request\Promotion\GetPromotionsRequestFactory;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Retailplace\MiraklPromotion\Model\Promotion as PromotionModel;

/**
 * Class Sync
 */
class Sync extends MMP
{
    /** @var int */
    public const MAX_PROMOTIONS_TO_IMPORT = 10000;

    /** @var \Mirakl\MMP\FrontOperator\Request\Promotion\GetPromotionsRequestFactory */
    private $getPromotionsRequestFactory;

    /** @var \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface */
    private $promotionRepository;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /**
     * Sync Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mirakl\Api\Model\Client\ClientManager $clientManager
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Mirakl\Api\Model\Log\LoggerManager $loggerManager
     * @param \Mirakl\Api\Model\Log\RequestLogValidator $requestLogValidator
     * @param \Mirakl\MMP\FrontOperator\Request\Promotion\GetPromotionsRequestFactory $getPromotionsRequestFactory
     * @param \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface $promotionRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        ClientManager $clientManager,
        CacheInterface $cache,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator,
        GetPromotionsRequestFactory $getPromotionsRequestFactory,
        PromotionRepositoryInterface $promotionRepository,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct(
            $context,
            $clientManager,
            $cache,
            $loggerManager,
            $requestLogValidator
        );

        $this->getPromotionsRequestFactory = $getPromotionsRequestFactory;
        $this->promotionRepository = $promotionRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get Promotions List from Mirakl and add to Magento
     */
    public function updatePromotions()
    {
        /** @var \Mirakl\MMP\FrontOperator\Request\Promotion\GetPromotionsRequest $request */
        $request = $this->getPromotionsRequestFactory->create();
        $request->setMax(self::MAX_PROMOTIONS_TO_IMPORT);

        $this->_eventManager->dispatch('mirakl_api_get_promotions_before', [
            'request' => $request
        ]);

        /** @var \Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionCollection $promotionCollection */
        $promotionCollection = $this->send($request);
        $this->processPromotions($promotionCollection);
    }

    /**
     * Process Promotions Collection
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionCollection $promotionCollection
     */
    private function processPromotions(PromotionCollection $promotionCollection)
    {
        $insertData = [];
        $activePromotions = [];

        /** @var \Mirakl\MMP\FrontOperator\Domain\Promotion\Promotion $promotion */
        foreach ($promotionCollection->getItems() as $promotion) {
            $promotion = $this->promotionRepository->convertMiraklPromotion($promotion);
            $activePromotions[] = $promotion->getPromotionUniqueId();
            $insertData[] = [
                'shop_id' => $promotion->getShopId(),
                'internal_id' => $promotion->getInternalId(),
                'promotion_unique_id' => $promotion->getPromotionUniqueId(),
                'state' => $promotion->getState(),
                'type' => $promotion->getType(),
                'date_created' => $promotion->getDateCreated(),
                'start_date' => $promotion->getStartDate(),
                'end_date' => $promotion->getEndDate(),
                'internal_description' => $promotion->getInternalDescription(),
                'percentage_off' => $promotion->getPercentageOff(),
                'amount_off' => $promotion->getAmountOff(),
                'free_items_quantity' => $promotion->getFreeItemsQuantity(),
                'reward_on_purchased_items' => $promotion->getRewardOnPurchasedItems(),
                'public_descriptions' => $promotion->getData(PromotionInterface::PUBLIC_DESCRIPTIONS),
                'reward_offer_ids' => $promotion->getData(PromotionInterface::REWARD_OFFER_IDS),
                'trigger_offer_ids' => $promotion->getData(PromotionInterface::TRIGGER_OFFER_IDS),
                'media' => $promotion->getData(PromotionInterface::MEDIA)
            ];
        }

        $this->disableUnusedPromotions($activePromotions);

        if (count($insertData)) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $this->resourceConnection->getTableName(PromotionModel::TABLE_NAME),
                $insertData
            );
        }
    }

    /**
     * Disable unused Promotions
     *
     * @param string[] $activePromotions
     */
    private function disableUnusedPromotions(array $activePromotions)
    {
        if (count($activePromotions)) {
            $this->resourceConnection->getConnection()->update(
                PromotionModel::TABLE_NAME,
                [PromotionInterface::STATE => PromotionInterface::STATE_EXPIRED],
                [PromotionInterface::PROMOTION_UNIQUE_ID . ' NOT IN (?)' => $activePromotions]
            );
        }
    }
}
