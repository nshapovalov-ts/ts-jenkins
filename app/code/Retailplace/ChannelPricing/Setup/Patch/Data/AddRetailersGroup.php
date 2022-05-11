<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Setup\Patch\Data;

use Exception;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\Retailers;

/**
 * Class AddRetailersGroup
 */
class AddRetailersGroup implements DataPatchInterface
{
    /** @var \Magento\Customer\Api\Data\GroupInterfaceFactory */
    private $customerGroupFactory;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AddRetailersGroup constructor.
     *
     * @param \Magento\Customer\Api\Data\GroupInterfaceFactory $customerGroupFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        GroupInterfaceFactory $customerGroupFactory,
        GroupRepositoryInterface $customerGroupRepository,
        LoggerInterface $logger
    ) {
        $this->customerGroupFactory = $customerGroupFactory;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->addGroup();
    }

    /**
     * Add new Customer Group
     */
    private function addGroup()
    {
        /** @var \Magento\Customer\Api\Data\GroupInterface $customerGroup */
        $customerGroup = $this->customerGroupFactory->create();
        $customerGroup->setCode(Retailers::GROUP_CODE);

        try {
            $this->customerGroupRepository->save($customerGroup);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
