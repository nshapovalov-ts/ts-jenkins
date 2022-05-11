<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Setup\Patch\Data;

use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Retailplace\EmailConfirmation\Model\Validator;

/**
 * Class AddValidateFrequencyAttributes
 */
class AddValidateFrequencyAttributes implements DataPatchInterface
{
    /** @var \Magento\Customer\Setup\CustomerSetupFactory */
    private $customerSetupFactory;

    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        WriterInterface $configWriter
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
    }

    /**
     * Add Attributes
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->addAttribute(
            Customer::ENTITY,
            Validator::EMAIL_VALIDATE_FAILURES_NUM,
            [
                'type' => 'static',
                'label' => 'Email OTP Code Failures Number',
                'input' => 'hidden',
                'required' => false,
                'sort_order' => 200,
                'visible' => false,
                'system' => true,
            ]
        );

        $customerSetup->addAttribute(
            Customer::ENTITY,
            Validator::EMAIL_VALIDATE_LOCK_EXPIRES,
            [
                'type' => 'static',
                'label' => 'Email OTP Code Lock Expires',
                'input' => 'date',
                'required' => false,
                'sort_order' => 210,
                'visible' => false,
                'system' => true,
            ]
        );

        $customerSetup->addAttribute(
            Customer::ENTITY,
            Validator::CUSTOMER_CONFIRMATION_ALT,
            [
                'type' => 'static',
                'label' => 'Email OTP Digital Code',
                'input' => 'hidden',
                'required' => false,
                'sort_order' => 220,
                'visible' => false,
                'system' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ]
        );

        $this->configWriter->save(AccountConfirmation::XML_PATH_IS_CONFIRM, 1);
    }

    /**
     * Get Dependencies
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get Aliases
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
