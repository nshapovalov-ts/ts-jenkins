<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\Updater;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\AttributesUpdater\Model\Updater\AbstractUpdater;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Retailplace\MiraklPromotion\Api\Data\ProductAttributesInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory as PromotionLinkCollectionFactory;
use Zend_Db_ExprFactory;

/**
 * Class SellerSpecials
 */
class SellerSpecials extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = ProductAttributesInterface::SELLER_SPECIALS;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory */
    private $dateTimeFactory;

    /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory */
    private $promotionLinkCollectionFactory;

    /**
     * SellerSpecials Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     * @param \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory $promotionLinkCollectionFactory
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        DateTimeFactory $dateTimeFactory,
        PromotionLinkCollectionFactory $promotionLinkCollectionFactory,
        Zend_Db_ExprFactory $exprFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $productLinkResourceModel,
            $resourceConnection,
            $attributeRepository,
            $searchCriteriaBuilderFactory,
            $offerRepository,
            $exprFactory,
            $scopeConfig,
            $logger
        );

        $this->dateTimeFactory = $dateTimeFactory;
        $this->promotionLinkCollectionFactory = $promotionLinkCollectionFactory;
    }

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $offerIds = $this->getOffersWithPromotions();
        $searchCriteriaBuilder->addFilter(OfferInterface::OFFER_ENTITY_ID, $offerIds, 'in');

        return $searchCriteriaBuilder;
    }

    /**
     * Get Offers Ids with Active Promotions
     *
     * @return int[]
     */
    private function getOffersWithPromotions(): array
    {
        /** @var \Magento\Framework\Stdlib\DateTime\DateTime $now */
        $now = $this->dateTimeFactory->create();

        $promotionLinkCollection = $this->promotionLinkCollectionFactory->create();
        $promotionLinkCollection->joinOffers();
        $promotionLinkCollection->joinPromotions();
        $promotionLinkCollection->addFieldToFilter(PromotionInterface::STATE, PromotionInterface::STATE_ACTIVE);
        $promotionLinkCollection->addFieldToFilter(PromotionInterface::START_DATE, ['lteq' => $now->gmtDate()]);
        $promotionLinkCollection->addFieldToFilter(
            PromotionInterface::END_DATE,
            [
                ['gteq' => $now->gmtDate()],
                ['null' => true]
            ]
        );

        $offerIds = [];
        foreach ($promotionLinkCollection->getItems() as $item) {
            $offerIds[] = $item->getData(OfferInterface::OFFER_ENTITY_ID);
        }

        return $offerIds;
    }
}
