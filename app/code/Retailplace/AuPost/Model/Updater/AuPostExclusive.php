<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Model\Updater;

use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\AttributesUpdater\Model\Updater\AbstractUpdater;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Zend_Db_ExprFactory;

/**
 * Class AuPostExclusive
 */
class AuPostExclusive extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = ProductAttributesInterface::AU_POST_EXCLUSIVE;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /**
     * AuPostExclusive Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
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
        ShopCollectionFactory $shopCollectionFactory,
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

        $this->shopCollectionFactory = $shopCollectionFactory;
    }

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder->addFilter(OfferInterface::SEGMENT, AuPost::GROUP_CODE, 'finset');
        $searchCriteriaBuilder->addFilter(OfferInterface::SHOP_ID, $this->getShopIds(), 'in');

        return $searchCriteriaBuilder;
    }

    /**
     * Get all Shop IDs with necessary attributes
     *
     * @return int[]
     */
    private function getShopIds(): array
    {
        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $shopCollection */
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter(ShopInterface::AU_POST_SELLER, ['eq' => 1]);

        return $this->convertArrayValuesToInt($shopCollection->getAllIds());
    }
}
