<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Retailplace\CustomerAccount\Rewrite\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\ValidationResultsInterfaceFactory;
use Magento\Customer\Api\SessionCleanerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\AccountManagement as BaseAccountManagement;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\Config\Share as ConfigShare;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\Customer\CredentialsValidator;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Customer\Model\Metadata\Validator;
use Magento\Customer\Model\ResourceModel\Visitor\CollectionFactory;
use Magento\Directory\Model\AllowedCountries;
use Magento\Eav\Model\Validator\Attribute\Backend;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils as StringHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as PsrLogger;
use Retailplace\CustomerAccount\Exception\ExistingPendingException;
use Retailplace\CustomerAccount\Model\ApprovalStatus;
use Magento\Framework\Exception\NoSuchEntityException;

class AccountManagement extends BaseAccountManagement
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Api\Data\ValidationResultsInterfaceFactory
     */
    private $validationResultsDataFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var CustomerMetadataInterface
     */
    private $customerMetadataService;

    /**
     * @var PsrLogger
     */
    protected $logger;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var ConfigShare
     */
    private $configShare;

    /**
     * @var StringHelper
     */
    protected $stringHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var ObjectFactory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @var CustomerModel
     */
    protected $customerModel;

    /**
     * @var AuthenticationInterface
     */
    protected $authentication;

    /**
     * @var EmailNotificationInterface
     */
    private $emailNotification;

    /**
     * @var \Magento\Eav\Model\Validator\Attribute\Backend
     */
    private $eavValidator;

    /**
     * @var CredentialsValidator
     */
    private $credentialsValidator;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var AccountConfirmation
     */
    private $accountConfirmation;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AddressRegistry
     */
    private $addressRegistry;

    /**
     * @var AllowedCountries
     */
    private $allowedCountriesReader;

    /**
     * @var GetCustomerByToken
     */
    private $getByToken;

    /**
     * @var SessionCleanerInterface
     */
    private $sessionCleaner;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatus;

    public function __construct(
        ApprovalStatus $approvalStatus,
        CustomerFactory $customerFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Random $mathRandom,
        Validator $validator,
        ValidationResultsInterfaceFactory $validationResultsDataFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerMetadataInterface $customerMetadataService,
        CustomerRegistry $customerRegistry,
        PsrLogger $logger,
        Encryptor $encryptor,
        ConfigShare $configShare,
        StringHelper $stringHelper,
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        Registry $registry,
        CustomerViewHelper $customerViewHelper,
        DateTime $dateTime,
        CustomerModel $customerModel,
        ObjectFactory $objectFactory,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        CredentialsValidator $credentialsValidator = null,
        DateTimeFactory $dateTimeFactory = null,
        AccountConfirmation $accountConfirmation = null,
        SessionManagerInterface $sessionManager = null,
        SaveHandlerInterface $saveHandler = null,
        CollectionFactory $visitorCollectionFactory = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        AddressRegistry $addressRegistry = null,
        GetCustomerByToken $getByToken = null,
        AllowedCountries $allowedCountriesReader = null,
        SessionCleanerInterface $sessionCleaner = null
    ) {
        parent::__construct(
            $customerFactory,
            $eventManager,
            $storeManager,
            $mathRandom,
            $validator,
            $validationResultsDataFactory,
            $addressRepository,
            $customerMetadataService,
            $customerRegistry,
            $logger,
            $encryptor,
            $configShare,
            $stringHelper,
            $customerRepository,
            $scopeConfig,
            $transportBuilder,
            $dataProcessor,
            $registry,
            $customerViewHelper,
            $dateTime,
            $customerModel,
            $objectFactory,
            $extensibleDataObjectConverter,
            $credentialsValidator,
            $dateTimeFactory,
            $accountConfirmation,
            $sessionManager,
            $saveHandler,
            $visitorCollectionFactory,
            $searchCriteriaBuilder,
            $addressRegistry,
            $getByToken,
            $allowedCountriesReader,
            $sessionCleaner
        );
        $this->approvalStatus = $approvalStatus;
        $this->customerFactory = $customerFactory;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->mathRandom = $mathRandom;
        $this->validator = $validator;
        $this->validationResultsDataFactory = $validationResultsDataFactory;
        $this->addressRepository = $addressRepository;
        $this->customerMetadataService = $customerMetadataService;
        $this->customerRegistry = $customerRegistry;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->configShare = $configShare;
        $this->stringHelper = $stringHelper;
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->dataProcessor = $dataProcessor;
        $this->registry = $registry;
        $this->customerViewHelper = $customerViewHelper;
        $this->dateTime = $dateTime;
        $this->customerModel = $customerModel;
        $this->objectFactory = $objectFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $objectManager = ObjectManager::getInstance();
        $this->credentialsValidator =
            $credentialsValidator ?: $objectManager->get(CredentialsValidator::class);
        $this->dateTimeFactory = $dateTimeFactory ?: $objectManager->get(DateTimeFactory::class);
        $this->accountConfirmation = $accountConfirmation ?: $objectManager
            ->get(AccountConfirmation::class);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder
            ?: $objectManager->get(SearchCriteriaBuilder::class);
        $this->addressRegistry = $addressRegistry
            ?: $objectManager->get(AddressRegistry::class);
        $this->getByToken = $getByToken
            ?: $objectManager->get(GetCustomerByToken::class);
        $this->allowedCountriesReader = $allowedCountriesReader
            ?: $objectManager->get(AllowedCountries::class);
        $this->sessionCleaner = $sessionCleaner ?? $objectManager->get(SessionCleanerInterface::class);
    }
    /**
     * Get EAV validator
     *
     * @return Backend
     */
    private function getEavValidator()
    {
        if ($this->eavValidator === null) {
            $this->eavValidator = ObjectManager::getInstance()->get(Backend::class);
        }
        return $this->eavValidator;
    }
    /**
     * @inheritdoc
     */
    public function validate(CustomerInterface $customer)
    {
        $validationResults = $this->validationResultsDataFactory->create();

        $oldAddresses = $customer->getAddresses();
        $customerModel = $this->customerFactory->create()->updateData(
            $customer->setAddresses([])
        );
        $customer->setAddresses($oldAddresses);

        $result = $this->getEavValidator()->isValid($customerModel);

        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $request = $objectManager->get('\Magento\Framework\App\Request\Http');

        $messages = $this->getEavValidator()->getMessages();
        if ($request->getControllerModule() == "Magento_Checkout") {
            $attributeCodes = array_keys($messages);
            $allowedAttributesOnCheckout = [
                'industry',
                'sell_goods_medium',
                'sell_goods_offline',
                'sell_goods',
                'currently_goods_online',
                'business_type'
            ];
            if ($attributeCodes) {
                foreach ($allowedAttributesOnCheckout as $allowedAttributeCode) {
                    if (isset($messages[$allowedAttributeCode])) {
                        unset($messages[$allowedAttributeCode]);
                    }
                }
            }
        }
        if ($result === false && $messages && is_array($messages)) {
            return $validationResults->setIsValid(false)->setMessages(
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                call_user_func_array(
                    'array_merge',
                    $messages
                )
            );
        }
        return $validationResults->setIsValid(true)->setMessages([]);
    }

    public function createAccountWithPasswordHash(CustomerInterface $customer, $hash, $redirectUrl = '')
    {
        //This customer already exists in this store but not complete the application form.
        $this->validateExistingPendingApproval($customer->getEmail());

        // This logic allows an existing customer to be added to a different store.  No new account is created.
        // The plan is to move this logic into a new method called something like 'registerAccountWithStore'
        if ($customer->getId()) {
            $customer = $this->customerRepository->get($customer->getEmail());
            $websiteId = $customer->getWebsiteId();
            if ($this->isCustomerInStore($websiteId, $customer->getStoreId())) {
                throw new InputException(__('This customer already exists in this store.'));
            }
            // Existing password hash will be used from secured customer data registry when saving customer
        }

        // Make sure we have a storeId to associate this customer with.
        if (!$customer->getStoreId()) {
            if ($customer->getWebsiteId()) {
                $storeId = $this->storeManager->getWebsite($customer->getWebsiteId())->getDefaultStore()->getId();
            } else {
                $this->storeManager->setCurrentStore(null);
                $storeId = $this->storeManager->getStore()->getId();
            }
            $customer->setStoreId($storeId);
        }

        // Associate website_id with customer
        if (!$customer->getWebsiteId()) {
            $websiteId = $this->storeManager->getStore($customer->getStoreId())->getWebsiteId();
            $customer->setWebsiteId($websiteId);
        }

        $this->validateCustomerStoreIdByWebsiteId($customer);

        // Update 'created_in' value with actual store name
        if ($customer->getId() === null) {
            $storeName = $this->storeManager->getStore($customer->getStoreId())->getName();
            $customer->setCreatedIn($storeName);
        }

        $customerAddresses = $customer->getAddresses() ?: [];
        $customer->setAddresses(null);
        try {
            // If customer exists existing hash will be used by Repository
            $customer = $this->customerRepository->save($customer, $hash);
        } catch (AlreadyExistsException $e) {
            throw new InputMismatchException(
                __('A customer with the same email address already exists in an associated website.')
            );
        } catch (LocalizedException $e) {
            throw $e;
        }
        try {
            foreach ($customerAddresses as $address) {
                if (!$this->isAddressAllowedForWebsite($address, $customer->getStoreId())) {
                    continue;
                }
                if ($address->getId()) {
                    $newAddress = clone $address;
                    $newAddress->setId(null);
                    $newAddress->setCustomerId($customer->getId());
                    $this->addressRepository->save($newAddress);
                } else {
                    $address->setCustomerId($customer->getId());
                    $this->addressRepository->save($address);
                }
            }
            $this->customerRegistry->remove($customer->getId());
        } catch (InputException $e) {
            $this->customerRepository->delete($customer);
            throw $e;
        }
        $customer = $this->customerRepository->getById($customer->getId());
        $newLinkToken = $this->mathRandom->getUniqueHash();
        $this->changeResetPasswordLinkToken($customer, $newLinkToken);
        $this->sendEmailConfirmation($customer, $redirectUrl);

        return $customer;
    }

    /**
     * Check is address allowed for store
     *
     * @param AddressInterface $address
     * @param int|null $storeId
     * @return bool
     */
    private function isAddressAllowedForWebsite(AddressInterface $address, $storeId): bool
    {
        $allowedCountries = $this->allowedCountriesReader->getAllowedCountries(ScopeInterface::SCOPE_STORE, $storeId);

        return in_array($address->getCountryId(), $allowedCountries);
    }

    /**
     * @param $email
     * @return bool
     * @throws ExistingPendingException
     */
    private function validateExistingPendingApproval($email)
    {
        $isPending = false;
        try {
            $customer = $this->customerRepository->get($email);
            $isPending = $this->approvalStatus->isPending($customer);
        } catch (NoSuchEntityException | LocalizedException $e) {
        }
        if ($isPending) {
            throw new ExistingPendingException(__('This customer already exists in this store but not complete the application form.'));
        }
        return true;
    }
}
