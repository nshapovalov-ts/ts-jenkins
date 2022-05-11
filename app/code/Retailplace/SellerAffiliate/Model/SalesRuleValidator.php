<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use Exception;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\RulesApplier;
use Psr\Log\LoggerInterface;

class SalesRuleValidator extends \Magento\SalesRule\Model\Validator
{
    /** @var RuleGenerator */
    private $ruleGenerator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param Utility $utility
     * @param RulesApplier $rulesApplier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param Validator\Pool $validators
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param RuleGenerator $ruleGenerator
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\SalesRule\Model\Utility $utility,
        \Magento\SalesRule\Model\RulesApplier $rulesApplier,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\SalesRule\Model\Validator\Pool $validators,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        RuleGenerator $ruleGenerator,
        LoggerInterface $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $collectionFactory,
            $catalogData,
            $utility,
            $rulesApplier,
            $priceCurrency,
            $validators,
            $messageManager,
            $resource,
            $resourceCollection
        );
        $this->ruleGenerator = $ruleGenerator;
        $this->logger = $logger;
    }

    /**
     * Get rules collection for current object state
     *
     * @param Address|null $address
     * @return \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected function _getRules(Address $address = null)
    {
        $addressId = $this->getAddressId($address);
        $key = $this->getWebsiteId() . '_'
            . $this->getCustomerGroupId() . '_'
            . $this->getCouponCode() . '_'
            . $addressId;
        if (!isset($this->_rules[$key])) {
            $rules = parent::_getRules($address);

            $generatedRules = $this->ruleGenerator->generateRules($address->getQuote());

            foreach ($generatedRules as $generatedRule) {
                try {
                    $rules->addItem($generatedRule);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $this->_rules[$key];
    }
}
