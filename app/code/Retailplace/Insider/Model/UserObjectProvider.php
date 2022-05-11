<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Magento\Cookie\Helper\Cookie;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Retailplace\Insider\Api\InsiderObjectProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * UserObjectProvider class
 */
class UserObjectProvider implements InsiderObjectProviderInterface
{
    /** @var Cookie */
    private $cookieHelper;

    /** @var CustomerRepositoryInterface */
    private $customerRepositoryInterface;

    /** @var AddressRepositoryInterface */
    private $addressRepositoryInterface;

    /** @var Resolver */
    private $store;

    /** @var CollectionFactory */
    private $orderCollectionFactory;

    /** @var Context */
    private $httpContext;

    /**
     * UserObjectProvider constructor
     *
     * @param Cookie $cookieHelper
     * @param CollectionFactory $orderCollectionFactory
     * @param Resolver $store
     * @param Context $httpContext
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        Cookie $cookieHelper,
        CollectionFactory $orderCollectionFactory,
        Resolver $store,
        Context $httpContext,
        AddressRepositoryInterface $addressRepositoryInterface,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->cookieHelper = $cookieHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->store = $store;
        $this->addressRepositoryInterface = $addressRepositoryInterface;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->httpContext = $httpContext;
    }

    /**
     * Get config
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $config = ['user' => ['language' => $this->getLanguage()]];
        if ($this->getCustomerId()) {
            $config = [
                'user' => [
                    'gender'            => $this->getGender(),
                    'birthday'          => $this->getUser()->getDob(),
                    'has_transacted'    => $this->isTransacted(),
                    'transaction_count' => $this->getTransactionCount(),
                    'gdpr_optin'        => $this->isGdprOption(),
                    'name'              => $this->getUser()->getFirstname(),
                    'surname'           => $this->getUser()->getLastname(),
                    'username'          => $this->getUser()->getEmail(),
                    'email'             => $this->getUser()->getEmail(),
                    'email_optin'       => $this->getUser()->getExtensionAttributes()->getIsSubscribed(),
                    'phone_number'      => $this->getPhone(),
                    'language'          => $this->getLanguage(),
                    'list_id'           => $this->getListNewsletter()
                ]
            ];
        }

        return $config;
    }

    /**
     * Get gender
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getGender(): string
    {
        $result = "";

        if ($this->getUser()->getGender()) {
            switch ($this->getUser()->getGender()) {
                case 1:
                    $result = 'M';
                    break;
                case 2:
                    $result = 'F';
                    break;
            }
        }

        return $result;
    }

    /**
     * Get transaction count
     *
     * @return int
     */
    private function getTransactionCount(): int
    {
        $transactionCount = 0;
        if ($this->orderCollectionFactory->create($this->getCustomerId())->getFirstItem()) {
            $transactionCount = $this->orderCollectionFactory->create($this->getCustomerId())->getSize();
        }

        return $transactionCount;
    }

    /**
     * Get list newsletter
     *
     * @return int[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getListNewsletter(): array
    {
        $result = [0];
        if ($this->getUser()->getExtensionAttributes()->getIsSubscribed()) {
            $result = [1];
        }

        return $result;
    }

    /**
     * Does customer have transactions
     *
     * @return bool
     */
    private function isTransacted(): bool
    {
        $transaction = false;
        if ($this->getTransactionCount() != 0) {
            $transaction = true;
        }

        return $transaction;
    }

    /**
     * Get phone
     *
     * @return string
     * @throws LocalizedException
     */
    private function getPhone(): string
    {
        $phone = "";
        if ($this->getUser()->getDefaultBilling()) {
            $billingAddressId = $this->getUser()->getDefaultBilling();
            $billingAddress = $this->addressRepositoryInterface->getById($billingAddressId);
            if ($billingAddress->getTelephone()) {
                $phone = $billingAddress->getTelephone();
            }
        }

        return $phone;
    }

    /**
     * Get language
     *
     * @return string|string[]
     */
    private function getLanguage()
    {
        return str_ireplace('_', '-', mb_strtolower($this->store->getLocale()));
    }

    /**
     * Get customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return (bool) $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * Get customer id
     *
     * @return mixed|null
     */
    public function getCustomerId()
    {
        return $this->httpContext->getValue('customer_id');
    }

    /**
     * Get customer
     *
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getUser(): CustomerInterface
    {
        return $this->customerRepositoryInterface->getById($this->getCustomerId());
    }

    /**
     * Get gdpr option
     *
     * @return bool|null
     */
    public function isGdprOption(): ?bool
    {
        $gdpr = null;
        if ($this->cookieHelper->isCookieRestrictionModeEnabled()) {
            if (!$this->cookieHelper->isUserNotAllowSaveCookie()) {
                $gdpr = true;
            }
        }

        return $gdpr;
    }
}
