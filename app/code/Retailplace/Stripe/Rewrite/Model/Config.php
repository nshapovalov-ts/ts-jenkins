<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model;

use StripeIntegration\Payments\Helper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use StripeIntegration\Payments\Model\Config as ModelConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Locale\Resolver;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use StripeIntegration\Payments\Model\ResourceModel\StripeCustomer\Collection;
use StripeIntegration\Payments\Helper\SetupIntentFactory;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Data\Customer as DataCustomer;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Config
 */
class Config extends ModelConfig
{
    public static $moduleName = "Magento2";
    public static $moduleVersion = "2.5.7";
    public static $minStripePHPVersion = "7.61.0";
    public static $moduleUrl = "https://stripe.com/docs/plugins/magento";
    public static $partnerId = "pp_partner_Fs67gT2M6v3mH7";
    const STRIPE_API = "2020-03-02";
    public $isInitialized = false;
    public $isSubscriptionsEnabled = null;
    public static $stripeClient = null;
    public const DATE_FORMAT = "Y-m-d";

    /**
     * @var bool
     */
    private $isInvoicingEnabled;
    private $isPaymentCardVerificationEnabled;

    /**
     * @var int
     */
    private $isInvoicingDaysDue;
    private $isInvoicingMaxGrandTotal;

    /**
     * @var TimezoneInterface
     */
    private $timezone;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Helper\Generic $helper
     * @param EncryptorInterface $encryptor
     * @param Resolver $localeResolver
     * @param ResourceConfig $resourceConfig
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param Collection $stripeCustomerCollection
     * @param SetupIntentFactory $setupIntentFactory
     * @param TimezoneInterface $timezone
     * @param TaxConfig $taxConfig
     * @param SerializerInterface $serializer
     * @param DateTime $dateTime
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Helper\Generic $helper,
        EncryptorInterface $encryptor,
        Resolver $localeResolver,
        ResourceConfig $resourceConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        Collection $stripeCustomerCollection,
        SetupIntentFactory $setupIntentFactory,
        TimezoneInterface $timezone,
        TaxConfig $taxConfig,
        SerializerInterface $serializer,
        DateTime $dateTime
    ) {
        $this->timezone = $timezone;
        $this->serializer = $serializer;
        $this->dateTime = $dateTime;
        parent::__construct(
            $scopeConfig,
            $helper,
            $encryptor,
            $localeResolver,
            $resourceConfig,
            $logger,
            $storeManager,
            $stripeCustomerCollection,
            $setupIntentFactory,
            $taxConfig
        );
    }

    /**
     * Is Invoicing Enabled
     *
     * @param null|int|string $storeId
     * @return bool
     */
    public function isInvoicingEnabled($storeId = null): bool
    {
        if ($this->isInvoicingEnabled !== null) {
            return $this->isInvoicingEnabled;
        }

        $this->isInvoicingEnabled =
            ((bool) $this->getConfigData('active', 'invoice', $storeId)) && $this->initStripe();
        return $this->isInvoicingEnabled;
    }

    /**
     * Get Invoicing Days Due
     *
     * @param null|int|string $storeId
     * @return int
     */
    public function getInvoicingDaysDue($storeId = null): int
    {
        if ($this->isInvoicingDaysDue !== null) {
            return $this->isInvoicingDaysDue;
        }

        return $this->isInvoicingDaysDue = (int) $this->getConfigData('days_due', 'invoice', $storeId);
    }

    /**
     * Get Invoicing Max Grand Total
     *
     * @param null|int|string $storeId
     * @return float
     */
    public function getInvoicingMaxGrandTotal($storeId = null): float
    {
        if ($this->isInvoicingMaxGrandTotal !== null) {
            return $this->isInvoicingMaxGrandTotal;
        }

        return $this->isInvoicingMaxGrandTotal = (float) $this->getConfigData('default_max_credit_limit', 'invoice', $storeId);
    }

    /**
     * Calculate payment date
     *
     * @param string|null $dateFormat
     * @param string|null $date
     * @return string
     */
    public function getPaymentDate($dateFormat = null, $date = null): string
    {
        if ($date) {
            $date = $this->dateTime->formatDate($date);
            $result = $this->timezone->date($date);
        } else {
            $result = $this->timezone->date()->modify(
                sprintf("+%s days", $this->getInvoicingDaysDue())
            );
        }

        return $result->format($dateFormat ?? self::DATE_FORMAT);
    }

    /**
     * Is Payment Card Verification Enabled
     *
     * @param null|int|string $storeId
     * @return bool
     */
    public function isPaymentCardVerificationEnabled($storeId = null): bool
    {
        if ($this->isPaymentCardVerificationEnabled !== null) {
            return $this->isPaymentCardVerificationEnabled;
        }

        $this->isPaymentCardVerificationEnabled =
            (bool) $this->getConfigData('payment_card_verification', 'invoice', $storeId);
        return $this->isPaymentCardVerificationEnabled;
    }

    /**
     * get Payment Card Verification Description
     *
     * @param null|int|string $storeId
     * @return string
     */
    public function getPaymentCardVerificationDescription($storeId = null): string
    {
        $description = $this->getConfigData('payment_card_verification_description', 'invoice', $storeId);
        return !empty($description) ? $description : "";
    }

    /**
     * get Payment Card Verification Suffix
     *
     * @param null|int|string $storeId
     * @return string
     */
    public function getPaymentCardVerificationSuffix($storeId = null): string
    {
        $description = $this->getConfigData('payment_card_verification_suffix', 'invoice', $storeId);
        return !empty($description) ? $description : "";
    }

    /**
     * Disable credit card method if net30 is available
     *
     * @param null|int|string $storeId
     * @return bool
     */
    public function isDisableCC($storeId = null): bool
    {
        return (bool) $this->getConfigData('disable_cc', 'invoice', $storeId);
    }

    /**
     * Get Frontend Description For CC If Disable Net30 V1
     *
     * @param null|int|string $storeId
     * @return string
     */
    public function getFrontendDescriptionIfDisableN30V1($storeId = null): string
    {
        $description = $this->getConfigData('frontend_description_disable_net30_v1', 'invoice', $storeId);
        return !empty($description) ? (string) $description : "";
    }

    /**
     * Get Frontend Description For CC If Disable Net30 V2
     *
     * @param null|int|string $storeId
     * @return string
     */
    public function getFrontendDescriptionIfDisableN30V2($storeId = null): string
    {
        $description = $this->getConfigData('frontend_description_disable_net30_v2', 'invoice', $storeId);
        return !empty($description) ? (string) $description : "";
    }

    /**
     * Get Max Credit Limit
     *
     * @param null|int|string $storeId
     * @return array
     */
    public function getMaxCreditLimit($storeId = null): array
    {
        $limit = $this->getConfigData('max_credit_limit', 'invoice', $storeId);

        if (!empty($limit)) {
            $limit = $this->serializer->unserialize($limit);
        }
        return !empty($limit) ? $limit : [];
    }

    /**
     * Get Customer Credit Limit
     *
     * @param Customer|DataCustomer $customer
     * @param null|int|string $storeId
     * @return float
     */
    public function getCustomerCreditLimit($customer, $storeId = null): float
    {
        if ($customer instanceof DataCustomer) {
            if ($customer->getCustomAttribute('max_credit_limit')) {
                $value = (float) $customer->getCustomAttribute('max_credit_limit')->getValue();
                if (!empty($value) && $value > 0) {
                    return $value;
                }
            }
        } else {
            if ($customer->getMaxCreditLimit()) {
                $value = (float) $customer->getMaxCreditLimit();
                if (!empty($value) && $value > 0) {
                    return $value;
                }
            }
        }

        $creditLimits = $this->getMaxCreditLimit($storeId);
        $customerGroupId = $customer->getGroupId();

        foreach ($creditLimits as $creditLimit) {
            if (!empty($creditLimit['id']) && $customerGroupId == $creditLimit['id']) {
                $limit = $creditLimit['value'];
                break;
            }
        }

        if (empty($limit)) {
            $limit = $this->getInvoicingMaxGrandTotal($storeId);
        }

        if (empty($limit)) {
            $limit = 0;
        }

        return (float) $limit;
    }

    /**
     * Get Tax Rate Id
     *
     * @param null $storeId
     * @return string
     */
    public function getTaxRateId($storeId = null)
    {
        $taxRateId = $this->getConfigData('tax_rate_id', 'invoice', $storeId);
        return $taxRateId ? (string) $taxRateId : "";
    }

    /**
     * Get Days Count For Check Credit Card
     *
     * @param null $storeId
     * @return int
     */
    public function getDaysCountForCheckCreditCard($storeId = null): int
    {
        $days = $this->getConfigData('check_for_n_days', 'invoice', $storeId);
        return $days ? (int) $days : 0;
    }

    /**
     * Get Fail Message For Duplicate Credit Card
     *
     * @param null $storeId
     * @return string
     */
    public function getFailMessageForDuplicateCreditCard($storeId = null): string
    {
        $message = $this->getConfigData('fail_message_for_duplicate_credit_card', 'invoice', $storeId);
        return $message ? (string)$message : "";
    }
}
