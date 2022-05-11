<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Address\Collection;
use Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory;
use Retailplace\CustomerAccount\Block\Widget\BusinessName;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Output\ConsoleOutputFactory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AddressCompanyManagement
 */
class AddressCompanyManagement
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory */
    private $quoteAddressCollectionFactory;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Address\Collection */
    private $quoteAddressCollection;

    /** @var \Magento\Quote\Model\ResourceModel\Quote\Address */
    private $addressResourceModel;

    /** @var \Symfony\Component\Console\Helper\ProgressBarFactory */
    private $progressBarFactory;

    /** @var \Symfony\Component\Console\Output\ConsoleOutputFactory */
    private $outputFactory;

    /** @var \Symfony\Component\Console\Helper\ProgressBar */
    private $progressBar;

    /** @var \Symfony\Component\Console\Output\ConsoleOutput */
    private $output;

    /**
     * Class AddressCompanyManagement
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address $addressResourceModel
     * @param \Symfony\Component\Console\Helper\ProgressBarFactory $progressBarFactory
     * @param \Symfony\Component\Console\Output\ConsoleOutputFactory $outputFactory
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CollectionFactory $quoteAddressCollectionFactory,
        Address $addressResourceModel,
        ProgressBarFactory $progressBarFactory,
        ConsoleOutputFactory $outputFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->quoteAddressCollectionFactory = $quoteAddressCollectionFactory;
        $this->addressResourceModel = $addressResourceModel;
        $this->progressBarFactory = $progressBarFactory;
        $this->outputFactory = $outputFactory;
    }

    /**
     * Update Addresses
     *
     * @return int
     */
    public function update(): int
    {
        return $this->updateAddresses();
    }

    /**
     * Get Address Collection with NULL in Company Attribute
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Address\Collection
     */
    private function getAddressCollection(): Collection
    {
        if (!$this->quoteAddressCollection) {
            /** @var \Magento\Quote\Model\ResourceModel\Quote\Address\Collection $quoteAddressCollection */
            $this->quoteAddressCollection = $this->quoteAddressCollectionFactory->create();
            $this->quoteAddressCollection
                ->addFieldToFilter('company', ['null' => true])
                ->addFieldToFilter('customer_id', ['gt' => 0]);
        }

        return $this->quoteAddressCollection;
    }

    /**
     * Get array of Customer Ids
     *
     * @return array
     */
    private function getCustomerIds(): array
    {
        return $this->getAddressCollection()->getColumnValues('customer_id');
    }

    /**
     * Get Company Data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCompaniesMapping(): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(BusinessName::ATTRIBUTE_CODE, 'null', 'neq')
            ->addFilter('entity_id', $this->getCustomerIds(), 'in')
            ->create();

        $customers = $this->customerRepository->getList($searchCriteria);
        $companies = [];
        foreach ($customers->getItems() as $customer) {
            $attribute = $customer->getCustomAttribute(BusinessName::ATTRIBUTE_CODE);
            if ($attribute && $attribute->getValue()) {
                $companies[$customer->getId()] = $attribute->getValue();
            }
        }

        return $companies;
    }

    /**
     * Update Addresses
     *
     * @return int
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateAddresses(): int
    {
        $counter = 0;
        $companies = $this->getCompaniesMapping();
        $this->progressBarStart($this->getAddressCollection()->getSize());
        foreach ($this->getAddressCollection() as $address) {
            if (!$address->getCompany() && !empty($companies[$address->getCustomerId()])) {
                $address->setCompany($companies[$address->getCustomerId()]);
                $this->addressResourceModel->save($address);
            }
            $this->progressBar->advance();
            $counter++;
        }
        $this->progressBarFinish();

        return $counter;
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
}
