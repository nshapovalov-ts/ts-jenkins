<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\Retailers;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Output\ConsoleOutputFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Retailplace\CustomerAccount\Block\Widget\MyNetwork;
use Retailplace\CustomerAccount\Model\Config\Source\Customer\MyNetwork as MyNetworkSourceOption;

/**
 * Class CustomerGroupMapper
 */
class CustomerGroupMapper
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Symfony\Component\Console\Helper\ProgressBarFactory */
    private $progressBarFactory;

    /** @var \Symfony\Component\Console\Helper\ProgressBar */
    private $progressBar;

    /** @var \Symfony\Component\Console\Output\ConsoleOutputFactory */
    private $outputFactory;

    /** @var \Symfony\Component\Console\Output\ConsoleOutput */
    private $output;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Retailplace\ChannelPricing\Api\GroupProcessorInterface[] */
    private $groupProcessorsList;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * CustomerGroupMapper constructor.
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Symfony\Component\Console\Helper\ProgressBarFactory $progressBarFactory
     * @param \Symfony\Component\Console\Output\ConsoleOutputFactory $outputFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $groupSettersList
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ProgressBarFactory $progressBarFactory,
        ConsoleOutputFactory $outputFactory,
        ScopeConfigInterface $scopeConfig,
        array $groupSettersList,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->progressBarFactory = $progressBarFactory;
        $this->outputFactory = $outputFactory;
        $this->scopeConfig = $scopeConfig;
        $this->groupProcessorsList = $groupSettersList;
        $this->logger = $logger;
    }

    /**
     * Get all Customers by Mapped Attribute and Update Customer Groups.
     *
     * @param string[]|null $groups
     * @return int
     */
    public function applyGroupToCustomers(?array $groups = [Retailers::GROUP_CODE]): int
    {
        $totalCount = 0;

        foreach ($this->getGroupProcessors() as $groupProcessor) {
            if (in_array($groupProcessor->getGroupCode(), $groups)) {
                $this->getOutput()->writeln(__('Start Updating for Group %1', $groupProcessor->getGroupCode()));
                $count = 0;

                $customersList = $groupProcessor->getCustomersList();
                if ($customersList && count($customersList)) {
                    $count = count($customersList);
                    $this->progressBarStart($count);
                    foreach ($customersList as $customer) {
                        $customer->setGroupId($groupProcessor->getGroupId());
                        try {
                            $this->customerRepository->save($customer);
                        } catch (Exception $e) {
                            $this->logger->error($e->getMessage());
                        }
                        $this->progressBar->advance();
                    }
                    $this->progressBarFinish();
                }

                $totalCount += $count;
            }
        }

        return $totalCount;
    }

    /**
     * Get Group Id for Customer depends on Mapped Customer Attribute.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return int|null
     */
    public function getGroupIdByCustomer(CustomerInterface $customer): ?int
    {
        $mappedGroups = [];
        $groupId = null;

        $this->loadCustomerAttributes($customer);

        /** @var \Retailplace\ChannelPricing\Api\GroupProcessorInterface $groupProcessor */
        foreach ($this->getGroupProcessors() as $groupProcessor) {
            $mappedGroups[] = $groupProcessor->getGroupId();
            $newGroup = $groupProcessor->getGroupIdByCustomer($customer);
            if ($newGroup) {
                $groupId = $newGroup;
            }
        }
        if (!$groupId) {
            $currentGroupId = $this->getCustomerCurrentGroup((int) $customer->getId());
            if (in_array($currentGroupId, $mappedGroups)) {
                $groupId = $this->getDefaultCustomerGroup();
            } else {
                $groupId = $currentGroupId;
            }
        }
        return $groupId;
    }

    /**
     * Load all Customer attributes
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    private function loadCustomerAttributes(CustomerInterface $customer)
    {
        $currentCustomer = null;
        try {
            $currentCustomer = $this->customerRepository->getById($customer->getId());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        if ($currentCustomer) {
            foreach ($currentCustomer->getCustomAttributes() as $attribute) {
                if (!$customer->getCustomAttribute($attribute->getAttributeCode())) {
                    $customer->setCustomAttribute($attribute->getAttributeCode(), $attribute->getValue());
                }
            }
        }
    }

    /**
     * Get Default Customer Group Id from the Configuration
     *
     * @return int
     */
    private function getDefaultCustomerGroup(): int
    {
        return (int) $this->scopeConfig->getValue(GroupManagement::XML_PATH_DEFAULT_ID);
    }

    /**
     * Reload Customer from Repository and get Group
     *
     * @param int $customerId
     * @return int|null
     */
    private function getCustomerCurrentGroup(int $customerId): ?int
    {
        $groupId = null;
        try {
            $currentCustomer = $this->customerRepository->getById($customerId);
            $groupId = (int) $currentCustomer->getGroupId();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $groupId;
    }

    /**
     * Get Console Output Object.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    private function getOutput(): OutputInterface
    {
        if (!$this->output) {
            /** @var \Symfony\Component\Console\Output\OutputInterface $output */
            $this->output = $this->outputFactory->create();
        }

        return $this->output;
    }

    /**
     * Start Progress Bar in Console.
     *
     * @param int $count
     */
    private function progressBarStart(int $count)
    {
        /** @var \Symfony\Component\Console\Helper\ProgressBar $progressBar */
        $this->progressBar = $this->progressBarFactory->create(['output' => $this->getOutput(), 'max' => $count]);
    }

    /**
     * Finish Progress Bar.
     */
    private function progressBarFinish()
    {
        $this->progressBar->finish();
        $this->output->write(PHP_EOL);
    }

    /**
     * Get Group Processors list
     *
     * @return \Retailplace\ChannelPricing\Api\GroupProcessorInterface[]
     */
    private function getGroupProcessors(): array
    {
        return $this->groupProcessorsList;
    }
}
