<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Plugin;

use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\Customer;
use Retailplace\EmailConfirmation\Model\Validator;
use Magento\Framework\Math\Random;
use Magento\Customer\Model\ResourceModel\Customer as MagentoCustomerResourceModel;

/**
 * Class CustomerResourceModel
 */
class CustomerResourceModel
{
    /** @var \Magento\Customer\Model\AccountConfirmation */
    private $accountConfirmation;

    /** @var \Magento\Framework\Math\Random */
    private $random;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Model\AccountConfirmation $accountConfirmation
     * @param \Magento\Framework\Math\Random $random
     */
    public function __construct(
        AccountConfirmation $accountConfirmation,
        Random $random
    ) {
        $this->accountConfirmation = $accountConfirmation;
        $this->random = $random;
    }

    /**
     * Add confirmation_alt field to Customer Data
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer $subject
     * @param \Magento\Customer\Model\Customer $model
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave(MagentoCustomerResourceModel $subject, Customer $model)
    {
        if (!$model->getId() &&
            $this->accountConfirmation->isConfirmationRequired(
                $model->getWebsiteId(),
                $model->getId(),
                $model->getEmail()
            )
        ) {
            $model->setData(Validator::CUSTOMER_CONFIRMATION_ALT, $this->generateConfirmationAlt());
        }

        return [$model];
    }

    /**
     * Generate OTP Code for Email
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateConfirmationAlt(): string
    {
        return $this->random->getRandomString(Validator::EMAIL_OTP_CODE_LENGTH, Random::CHARS_DIGITS);
    }
}
