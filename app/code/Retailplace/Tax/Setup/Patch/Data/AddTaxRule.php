<?php
/**
 * Retailplace_Tax
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Tax\Setup\Patch\Data;

use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class AddTaxRule
 */
class AddTaxRule implements DataPatchInterface
{
    /**
     * @type int
     */
    const DEFAULT_CUSTOMER_TAX_CLASS_ID = 3;

    /**
     * @type int
     */
    const DEFAULT_PRODUCT_TAX_CLASS_ID = 2;

    /**
     * @type string
     */
    const DEFAULT_TAX_CODE = 'GST';

    /**
     * @type int
     */
    const DEFAULT_TAX_RATE = 10;

    /**
     * @type string
     */
    const DEFAULT_TAX_COUNTRY = 'AU';

    /**
     * @var TaxRuleRepositoryInterface
     */
    private $taxRuleRepository;

    /**
     * @var TaxRuleInterfaceFactory
     */
    private $taxRuleFactory;

    /**
     * @var TaxRateInterfaceFactory
     */
    private $taxRateFactory;

    /**
     * @var TaxRateRepositoryInterface
     */
    private $taxRateRepository;

    /**
     * @param TaxRuleRepositoryInterface $taxRuleRepository
     * @param TaxRuleInterfaceFactory $taxRuleFactory
     * @param TaxRateInterfaceFactory $taxRateFactory
     * @param TaxRateRepositoryInterface $taxRateRepository
     */
    public function __construct(
        TaxRuleRepositoryInterface $taxRuleRepository,
        TaxRuleInterfaceFactory $taxRuleFactory,
        TaxRateInterfaceFactory $taxRateFactory,
        TaxRateRepositoryInterface $taxRateRepository
    ) {
        $this->taxRuleRepository = $taxRuleRepository;
        $this->taxRuleFactory = $taxRuleFactory;
        $this->taxRateFactory = $taxRateFactory;
        $this->taxRateRepository = $taxRateRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {

        //Add Tax Rate
        $taxRate = $this->taxRateFactory->create();
        $taxRate->setCode(self::DEFAULT_TAX_CODE)
            ->setRate(self::DEFAULT_TAX_RATE)
            ->setTaxCountryId(self::DEFAULT_TAX_COUNTRY)
            ->setTaxPostcode('*');

        $taxRateData = $this->taxRateRepository->save($taxRate);

        //Add Tax Rule for Tax Rate
        $taxRuleDataObject = $this->taxRuleFactory->create();
        $taxRuleDataObject->setCode(self::DEFAULT_TAX_CODE)
            ->setTaxRateIds([$taxRateData->getId()])
            ->setCustomerTaxClassIds([self::DEFAULT_CUSTOMER_TAX_CLASS_ID])
            ->setProductTaxClassIds([self::DEFAULT_PRODUCT_TAX_CLASS_ID])
            ->setPriority(0)
            ->setPosition(0);

        $this->taxRuleRepository->save($taxRuleDataObject);
    }
}
