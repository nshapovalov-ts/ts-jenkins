<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Exception;
use Magento\Config\Model\Config\Source\Nooptreq;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * Class EnableCompanyField
 */
class EnableCompanyField implements DataPatchInterface
{
    /** @var string */
    public const XML_PATH_CUSTOMER_ADDRESS_COMPANY_SHOW = 'customer/address/company_show';

    /** @var int */
    public const CUSTOMER_ADDRESS_ENTITY_TYPE_ID = 2;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * EnableCompanyField Constructor
     *
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ConfigWriter $configWriter,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->configWriter = $configWriter;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Run code inside patch
     */
    public function apply()
    {
        try {
            $this->enableCompanyField();
            $this->updateNameAttributes();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * Enable Company Field for Customer Address
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function enableCompanyField()
    {
        $this->configWriter->save(self::XML_PATH_CUSTOMER_ADDRESS_COMPANY_SHOW, Nooptreq::VALUE_OPTIONAL);

        $company = $this->attributeRepository->get(
            self::CUSTOMER_ADDRESS_ENTITY_TYPE_ID, AddressInterface::COMPANY
        );
        $company->setIsVisible(1);
        $this->attributeRepository->save($company);
    }

    /**
     * Change Attribute Labels
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function updateNameAttributes()
    {
        $firstname = $this->attributeRepository->get(
            self::CUSTOMER_ADDRESS_ENTITY_TYPE_ID, AddressInterface::FIRSTNAME
        );
        $firstname->setDefaultFrontendLabel('Contact Person First Name');
        $this->attributeRepository->save($firstname);

        $lastname = $this->attributeRepository->get(
            self::CUSTOMER_ADDRESS_ENTITY_TYPE_ID, AddressInterface::LASTNAME
        );
        $lastname->setDefaultFrontendLabel('Contact Person Last Name');
        $this->attributeRepository->save($lastname);
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
