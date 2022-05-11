<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\SessionCleanerInterface;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\Customer\CredentialsValidator;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Stdlib\StringUtils as StringHelper;
use Magento\Store\Model\ScopeInterface;
use Mageplaza\CustomerApproval\Helper\ApprovalAction as ApprovalActionHelper;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Retailplace\CustomerAccount\Api\AccountManagementInterface;
use Retailplace\CustomerAccount\Api\Data\ChangePasswordInfoInterface;
use Retailplace\CustomerAccount\Helper\ApprovalStatus;
use Retailplace\CustomerAccount\Model\Config\Source\AttributeOptions;
use Retailplace\CustomerAccount\Model\Config\Source\AutoApprovedStatusOptions;
use Retailplace\CustomerAccount\Model\Config\Source\IncompleteApplicationStatus;
use Retailplace\CustomerAccount\Model\Service\Abn as AbnService;
use Retailplace\Whitelistemaildomain\Model\WhitelistemaildomainFactory;
use Magento\Customer\Model\Session;

/**
 * Class AccountManagement
 */
class AccountManagement implements AccountManagementInterface
{
    /** @var string */
    const INCOMPLETE_APPLICATION_ATTR = 'incomplete_application';
    const AUTO_APPROVED_STATUS = 'is_auto_approved_status';

    /** @var int */
    const MAX_PASSWORD_LENGTH = 256;

    /**
     * Configuration path to customer password minimum length
     */
    const XML_PATH_MINIMUM_PASSWORD_LENGTH = 'customer/password/minimum_password_length';

    /**
     * Configuration path to customer password required character classes number
     */
    const XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER = 'customer/password/required_character_classes_number';

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var AddressRegistry
     */
    private $addressRegistry;

    /**
     * @var AbnService
     */
    private $abnService;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WhitelistemaildomainFactory
     */
    protected $whitelistemaildomainFactory;

    /**
     * @var ApprovalActionHelper
     */
    private $approvalAction;

    /**
     * @var AuthenticationInterface
     */
    protected $authentication;

    /**
     * @var CredentialsValidator
     */
    private $credentialsValidator;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var SessionCleanerInterface
     */
    private $sessionCleaner;

    /**
     * @var StringHelper
     */
    protected $stringHelper;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var ChangePasswordInfoInterface
     */
    private $changePasswordInfo;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatusHelper;

    /**
     * @var AuPost
     */
    private $auPostProcessor;

    /**
     * @var Session
     */
    private $session;

    /**
     * AccountManagement constructor.
     *
     * @param \Retailplace\CustomerAccount\Helper\ApprovalStatus $approvalStatusHelper
     * @param ApprovalActionHelper $approvalAction
     * @param AbnService $abnService
     * @param AddressRegistry $addressRegistry
     * @param CustomerRegistry $customerRegistry
     * @param CredentialsValidator $credentialsValidator
     * @param CustomerRepositoryInterface $customerRepository
     * @param ChangePasswordInfoInterface $changePasswordInfo
     * @param Encryptor $encryptor
     * @param WhitelistemaildomainFactory $whitelistemaildomainFactory
     * @param SessionCleanerInterface $sessionCleaner
     * @param StringHelper $stringHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param AuPost $auPostProcessor
     * @param Session $customerSession
     */
    public function __construct(
        ApprovalStatus $approvalStatusHelper,
        ApprovalActionHelper $approvalAction,
        AbnService $abnService,
        AddressRegistry $addressRegistry,
        CustomerRegistry $customerRegistry,
        CredentialsValidator $credentialsValidator,
        CustomerRepositoryInterface $customerRepository,
        ChangePasswordInfoInterface $changePasswordInfo,
        Encryptor $encryptor,
        WhitelistemaildomainFactory $whitelistemaildomainFactory,
        SessionCleanerInterface $sessionCleaner,
        StringHelper $stringHelper,
        ScopeConfigInterface $scopeConfig,
        AuPost $auPostProcessor,
        Session $customerSession
    ) {
        $this->approvalStatusHelper = $approvalStatusHelper;
        $this->abnService = $abnService;
        $this->approvalAction = $approvalAction;
        $this->addressRegistry = $addressRegistry;
        $this->credentialsValidator = $credentialsValidator;
        $this->customerRepository = $customerRepository;
        $this->customerRegistry = $customerRegistry;
        $this->changePasswordInfo = $changePasswordInfo;
        $this->whitelistemaildomainFactory = $whitelistemaildomainFactory;
        $this->scopeConfig = $scopeConfig;
        $this->stringHelper = $stringHelper;
        $this->sessionCleaner = $sessionCleaner;
        $this->encryptor = $encryptor;
        $this->auPostProcessor = $auPostProcessor;
        $this->session = $customerSession;
    }

    /**
     * @inheritdoc
     */
    public function update(
        CustomerInterface $customer,
        $needValidateAddress = false,
        $needValidateApproval = false
    ) {
        try {
            $currentCustomer = $this->customerRepository->getById($customer->getId());
            $customerData = $customer;
            $isValidAbn = $this->validateAbn($customer);
            if (true == $needValidateApproval
                && $this->approvalStatusHelper->isIncompleteApplication()
            ) {
                $customerData = $this->prepareApprovalData($customer, $isValidAbn);
            } else {
                //Prevent lost approval data
                if ($isApproved = $currentCustomer->getCustomAttribute('is_approved')) {
                    $customerData->setCustomAttribute('is_approved', $isApproved->getValue());
                    if ($autoApprovedStatus = $currentCustomer->getCustomAttribute(self::AUTO_APPROVED_STATUS)) {
                        $customerData->setCustomAttribute(self::AUTO_APPROVED_STATUS, $autoApprovedStatus->getValue());
                    }
                }
                if ($incompleteApp = $currentCustomer->getCustomAttribute(self::INCOMPLETE_APPLICATION_ATTR)) {
                    $customerData->setCustomAttribute(self::INCOMPLETE_APPLICATION_ATTR, $incompleteApp->getValue());
                }
            }
            $customerData = $this->prepareAddress($currentCustomer, $customerData, $needValidateAddress);
            $customer = $this->customerRepository->save($customerData);
            return $customer;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function changeEmailAndPassword(
        $customerId,
        $email,
        $currentPassword,
        $newPassword,
        $isChangeEmail= false,
        $isChangePassword = false
    ) {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $changePasswordStatus = false;
            $this->getAuthentication()->authenticate(
                $customerId,
                $currentPassword
            );
            $needUpdate = false;
            if (true === $isChangeEmail) {
                $customer->setEmail($email);
                $needUpdate = true;
            }
            if (true == $isChangePassword) {
                $customer = $this->changePassword($customer, $currentPassword, $newPassword);
                $needUpdate = true;
            }
            if ($needUpdate) {
                $customer = $this->customerRepository->save($customer);
                if (true == $isChangePassword) {
                    $changePasswordStatus =  true;
                }
            }

            $changePasswordInfo = $this->changePasswordInfo;
            $changePasswordInfo->setCustomer($customer);
            $changePasswordInfo->setChangePasswordStatus($changePasswordStatus);

            return $changePasswordInfo;
        } catch (NoSuchEntityException $e) {
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }
    }

    /**
     * Disable Customer Address Validation
     *
     * @param CustomerInterface $customer
     * @throws NoSuchEntityException
     */
    private function disableAddressValidation($customer)
    {
        if (is_array($customer->getAddresses())) {
            foreach ($customer->getAddresses() as $address) {
                if ($address->getId()) {
                    $addressModel = $this->addressRegistry->retrieve($address->getId());
                    $addressModel->setShouldIgnoreValidation(true);
                }
            }
        }
    }

    /**
     * @param CustomerInterface $customer
     * @throws LocalizedException
     */
    private function validateAbn($customer)
    {
        if ($customer->getCustomAttribute('abn')) {
            $abn = $customer->getCustomAttribute('abn')->getValue();
            $isValidAbn = $this->abnService->getRecordFromAbrNumber($abn);
            if (true === $isValidAbn) {
                return true;
            } else {
                //Throw error message
                $email = $this->scopeConfig->getValue('trans_email/ident_support/email', ScopeInterface::SCOPE_STORE);
                $message = __("Invalid ABN to learn more please contact %1", $email);
                throw new LocalizedException($message);
            }
        }

        return false;
    }

    /**
     * @param CustomerInterface $customer
     * @param bool $isValidAbn
     * @return CustomerInterface
     */
    private function prepareApprovalData($customer, $isValidAbn)
    {
        $conditionallyApproved = false;
        $autoApproval = false;
        if (true === $isValidAbn) {
            $autoApproval = true;
//            $isValidDomain = $this->checkValidDomain($customer->getEmail());
//            if ($isValidDomain || $this->checkMyNetwork($customer) || $this->auPostProcessor->checkCondition($customer)) {
//                $conditionallyApproved = false;
//            }
        }
        if ($autoApproval) {
            $customer->setCustomAttribute('is_approved', AttributeOptions::APPROVED);
            $customer->setCustomAttribute(self::INCOMPLETE_APPLICATION_ATTR, IncompleteApplicationStatus::COMPLETE_APPLICATION);
            if ($conditionallyApproved) {
                $customer->setCustomAttribute(self::AUTO_APPROVED_STATUS, AutoApprovedStatusOptions::CONDITIONALLY_APPROVED);
            } else {
                $customer->setCustomAttribute(self::AUTO_APPROVED_STATUS, AutoApprovedStatusOptions::APPROVED);
            }

            $dataLayer = $this->session->getCustomerAccountData();
            $dataLayer = empty($dataLayer) || !is_array($dataLayer) ? [] : $dataLayer;
            $dataLayer[] = [
                'event'      => 'AccountApproved',
                'eventLabel' => 'Account Approved'
            ];
            $this->session->setCustomerAccountData($dataLayer);
        }

        return $customer;
    }

    /**
     * @param CustomerInterface $customer
     * @param CustomerInterface $customerData
     * @param false $needValidateAddress
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function prepareAddress(CustomerInterface $customer, CustomerInterface $customerData, $needValidateAddress = false)
    {
        $addresses = [];
        if (!$customer->getAddresses()) {
            if ($customerData->getAddresses()) {
                $addressData = $customerData->getAddresses()[0];
                $addressData->setIsDefaultBilling(1);
                $addressData->setIsDefaultShipping(1);
                $addresses[] = $addressData;
            }
        } else {
            $addresses = $customer->getAddresses();
            if ($customerData->getAddresses()) {
                $addresses[] = $customerData->getAddresses()[0];
            }
        }
        if ($needValidateAddress && empty($addresses)) {
            throw new LocalizedException(__('Address is required.'));
        }
        $customerData->setAddresses($addresses);
        $this->disableAddressValidation($customerData);

        return $customerData;
    }

    /**
     * @param $email
     * @return bool
     */
    private function checkValidDomain($email)
    {
        $domainName = substr(strrchr($email, "@"), 1);
        $domainCollection = $this->whitelistemaildomainFactory->create()
            ->getCollection()
            ->addFieldToFilter('domain', $domainName)
            ->addFieldToFilter('status', 1)
            ->setPageSize(1);
        if ($domainCollection->getSize()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param CustomerInterface $customer
     * @return bool
     */
    private function checkMyNetwork($customer)
    {
        if ($myNetwork = $customer->getCustomAttribute('my_network')) {
            if (!empty($myNetwork->getValue())
                && !in_array($myNetwork->getValue(), ['other'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     *
     * @param $customer
     * @param $currentPassword
     * @param $newPassword
     * @return mixed
     * @throws InvalidEmailOrPasswordException
     * @throws LocalizedException
     */
    public function changePassword($customer, $currentPassword, $newPassword)
    {
        return $this->preparePasswordForCustomer($customer, $currentPassword, $newPassword);
    }

    /**
     * @param $customer
     * @param $currentPassword
     * @param $newPassword
     * @return mixed
     * @throws InputException
     * @throws InvalidEmailOrPasswordException
     * @throws NoSuchEntityException
     * @throws UserLockedException
     */
    private function preparePasswordForCustomer($customer, $currentPassword, $newPassword)
    {
        $customerEmail = $customer->getEmail();
        $this->credentialsValidator->checkPasswordDifferentFromEmail($customerEmail, $newPassword);
        $this->checkPasswordStrength($newPassword);
        $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerSecure->setRpToken(null);
        $customerSecure->setRpTokenCreatedAt(null);
        $customerSecure->setPasswordHash($this->createPasswordHash($newPassword));
        $this->sessionCleaner->clearFor((int)$customer->getId());
        $this->disableAddressValidation($customer);

        return $customer;
    }

    /**
     * Get authentication
     *
     * @return AuthenticationInterface
     */
    private function getAuthentication()
    {
        if (!($this->authentication instanceof AuthenticationInterface)) {
            return ObjectManager::getInstance()->get(
                AuthenticationInterface::class
            );
        } else {
            return $this->authentication;
        }
    }

    /**
     * Make sure that password complies with minimum security requirements.
     *
     * @param string $password
     * @return void
     * @throws InputException
     */
    protected function checkPasswordStrength($password)
    {
        $length = $this->stringHelper->strlen($password);
        if ($length > self::MAX_PASSWORD_LENGTH) {
            throw new InputException(
                __(
                    'Please enter a password with at most %1 characters.',
                    self::MAX_PASSWORD_LENGTH
                )
            );
        }
        $configMinPasswordLength = $this->getMinPasswordLength();
        if ($length < $configMinPasswordLength) {
            throw new InputException(
                __(
                    'The password needs at least %1 characters. Create a new password and try again.',
                    $configMinPasswordLength
                )
            );
        }
        if ($this->stringHelper->strlen(trim($password)) != $length) {
            throw new InputException(
                __("The password can't begin or end with a space. Verify the password and try again.")
            );
        }

        $requiredCharactersCheck = $this->makeRequiredCharactersCheck($password);
        if ($requiredCharactersCheck !== 0) {
            throw new InputException(
                __(
                    'Minimum of different classes of characters in password is %1.' .
                    ' Classes of characters: Lower Case, Upper Case, Digits, Special Characters.',
                    $requiredCharactersCheck
                )
            );
        }
    }

    /**
     * Create a hash for the given password
     *
     * @param string $password
     * @return string
     */
    protected function createPasswordHash($password)
    {
        return $this->encryptor->getHash($password, true);
    }
    /**
     * Retrieve minimum password length
     *
     * @return int
     */
    protected function getMinPasswordLength()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    /**
     * Check password for presence of required character sets
     *
     * @param string $password
     * @return int
     */
    protected function makeRequiredCharactersCheck($password)
    {
        $counter = 0;
        $requiredNumber = $this->scopeConfig->getValue(self::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
        $return = 0;

        if (preg_match('/[0-9]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[A-Z]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[a-z]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[^a-zA-Z0-9]+/', $password)) {
            $counter++;
        }

        if ($counter < $requiredNumber) {
            $return = $requiredNumber;
        }

        return $return;
    }
}
