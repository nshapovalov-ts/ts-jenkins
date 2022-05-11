<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Plugin;

use Retailplace\Stripe\Rewrite\Model\Method\Invoice;
use StripeIntegration\Payments\Model\Ui\ConfigProvider as StripeConfigProvider;
use Retailplace\Stripe\Rewrite\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Retailplace\MobileVerification\Model\Verification;

/**
 * Class ConfigProvider
 */
class ConfigProvider
{
    /** @var string */
    public const FRONTEND_TITLE_XML_PATH = 'payment/stripe_payments_invoice/frontend_title';
    public const FRONTEND_DESCRIPTION_XML_PATH = 'payment/stripe_payments_invoice/frontend_description';
    public const FRONTEND_TERMS_AND_CONDITIONS_XML_PATH = 'payment/stripe_payments_invoice/terms_and_conditions';
    public const DATE_FORMAT = "d F, Y";

    /** @var Config */
    private $paymentConfig;

    /** @var ScopeConfigInterface */
    private $scopeConfig;
    /**
     * @var Verification
     */
    private $verification;

    /**
     * ConfigProvider constructor.
     *
     * @param Config $paymentConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param Verification $verification
     */
    public function __construct(
        Config $paymentConfig,
        ScopeConfigInterface $scopeConfig,
        Verification $verification
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfig = $scopeConfig;
        $this->verification = $verification;
    }

    /**
     * Plugin method
     *
     * @param StripeConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(
        StripeConfigProvider $subject,
        $result
    ): array {
        $title = $this->scopeConfig->getValue(self::FRONTEND_TITLE_XML_PATH);
        $description = $this->scopeConfig->getValue(self::FRONTEND_DESCRIPTION_XML_PATH);
        $result['payment'][Invoice::METHOD_CODE]['frontend_title'] = $this->updateText($title);
        $result['payment'][Invoice::METHOD_CODE]['frontend_description'] = $this->updateText($description);
        $result['payment'][Invoice::METHOD_CODE]['days_due'] = $this->paymentConfig->getInvoicingDaysDue();
        $result['payment'][Invoice::METHOD_CODE]['terms_and_conditions'] =
            $this->scopeConfig->getValue(self::FRONTEND_TERMS_AND_CONDITIONS_XML_PATH);
        $result['payment']['stripe_payments']['cc_description_v1'] =
            $this->updateText($this->paymentConfig->getFrontendDescriptionIfDisableN30V1());
        $result['payment']['stripe_payments']['cc_description_v2'] =
            $this->updateText($this->paymentConfig->getFrontendDescriptionIfDisableN30V2());
        $result['payment'][Invoice::METHOD_CODE]['customer_phone_number_confirmed'] = $this->verification->isCustomerPhoneNumberConfirmed();
        return $result;
    }

    /**
     * Update text with calculated dates
     *
     * @param string $text
     * @return string
     */
    private function updateText($text): string
    {
        return str_replace(
            ['%days%', '%date%'],
            [
                $this->paymentConfig->getInvoicingDaysDue(),
                $this->paymentConfig->getPaymentDate(self::DATE_FORMAT)
            ],
            $text
        );
    }
}
