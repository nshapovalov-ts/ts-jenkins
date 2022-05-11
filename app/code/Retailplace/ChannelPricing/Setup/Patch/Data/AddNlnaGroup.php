<?php
/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Setup\Patch\Data;

use Exception;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\Nlna;

/**
 * Class AddNlnaGroup
 */
class AddNlnaGroup implements DataPatchInterface
{
    /** @var GroupInterfaceFactory */
    private $customerGroupFactory;

    /** @var GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var LoggerInterface */
    private $logger;

    /**
     * AddRetailersGroup constructor.
     *
     * @param GroupInterfaceFactory $customerGroupFactory
     * @param GroupRepositoryInterface $customerGroupRepository
     * @param LoggerInterface $logger
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
        /** @var GroupInterface $customerGroup */
        $customerGroup = $this->customerGroupFactory->create();
        $customerGroup->setCode(Nlna::GROUP_CODE);

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
