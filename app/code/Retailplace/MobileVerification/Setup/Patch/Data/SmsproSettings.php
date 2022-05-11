<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Setup\Patch\Data;

use Magecomp\Smspro\Helper\Customer as CustomerHelper;
use Magecomp\Smspro\Helper\Data as SmsproHelper;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class SmsproSettings
 */
class SmsproSettings implements DataPatchInterface
{
    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /**
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(WriterInterface $configWriter)
    {
        $this->configWriter = $configWriter;
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        $this->smsproSettings();
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Run code inside patch
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Set Smspro settings
     */
    private function smsproSettings()
    {
        $otpTemplate = '{{var otp}} is your TradeSquare verification code';

        $this->configWriter->save(SmsproHelper::SMS_GENERAL_ENABLED, 1);
        $this->configWriter->save(CustomerHelper::SMS_OTP_LENGTH, 4);
        $this->configWriter->save(CustomerHelper::SMS_CUSTOMER_MOBILE_CONFIRMATIOM_TEMPLATE, $otpTemplate);
    }
}
