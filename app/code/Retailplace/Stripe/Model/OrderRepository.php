<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Model;

use Magento\Sales\Model\OrderRepository as ModelOrderRepository;
use Magento\Sales\Model\ResourceModel\Metadata;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Tax\Api\OrderTaxManagementInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Payment\Api\Data\PaymentAdditionalInfoInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

/**
 * Class OrderRepository
 */
class OrderRepository extends ModelOrderRepository
{
    /**
     * @var SearchResultFactory
     */
    protected $searchResultFactory = null;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * Constructor
     *
     * @param Metadata $metadata
     * @param SearchResultFactory $searchResultFactory
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @param OrderExtensionFactory|null $orderExtensionFactory
     * @param OrderTaxManagementInterface|null $orderTaxManagement
     * @param PaymentAdditionalInfoInterfaceFactory|null $paymentAdditionalInfoFactory
     * @param JsonSerializer|null $serializer
     * @param JoinProcessorInterface|null $extensionAttributesJoinProcessor
     */
    public function __construct(
        Metadata $metadata,
        SearchResultFactory $searchResultFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor = null,
        OrderExtensionFactory $orderExtensionFactory = null,
        OrderTaxManagementInterface $orderTaxManagement = null,
        PaymentAdditionalInfoInterfaceFactory $paymentAdditionalInfoFactory = null,
        JsonSerializer $serializer = null
    ) {
        parent::__construct(
            $metadata,
            $searchResultFactory,
            $collectionProcessor,
            $orderExtensionFactory,
            $orderTaxManagement,
            $paymentAdditionalInfoFactory,
            $serializer,
            $extensionAttributesJoinProcessor
        );

        $this->searchResultFactory = $searchResultFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * Find entities by criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var OrderSearchResultInterface $searchResult */
        $searchResult = $this->searchResultFactory->create();
        $this->extensionAttributesJoinProcessor->process($searchResult);
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
