<?php

/**
 * A Magento 2 module named Retailplace/Variantsfix
 * Copyright (C) 2019
 *
 * This file included in Retailplace/Variantsfix is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

/**
 * Retailplace_Variantsfix
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\Variantsfix\Rewrite\Magento\Checkout\Model;

use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Address\CustomerAddressDataProvider;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrlManager;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Locale\FormatInterface as LocaleFormat;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface as ShippingMethodManager;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Element\Multiline;
use Magento\Customer\Model\Form;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Model\Config;
use Magento\Quote\Model\Cart\TotalSegment;
use Magento\Quote\Model\Cart\Totals\Item;
use Magento\Quote\Api\Data\TotalsInterface;
use function is_array;
use Magento\Framework\Exception\LocalizedException;
use Exception;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Helper\Data;
use Magento\Checkout\Model\Cart\ImageProvider;
use Magento\Directory\Model\Country\Postcode\ConfigInterface;
use Magento\Catalog\Helper\Image;
use Magento\Customer\Model\Address\Mapper;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class DefaultConfigProvider
 */
class DefaultConfigProvider extends \Magento\Checkout\Model\DefaultConfigProvider
{
    /**
     * @var AttributeOptionManagementInterface
     */
    private $attributeOptionManager;

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerUrlManager
     */
    private $customerUrlManager;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteItemRepository
     */
    private $quoteItemRepository;

    /**
     * @var ShippingMethodManager
     */
    private $shippingMethodManager;

    /**
     * @var ConfigurationPool
     */
    private $configurationPool;
    /**
     * @var AddressMetadataInterface
     */
    private $addressMetadata;

    /**
     * @var CustomerAddressDataProvider
     */
    private $customerAddressData;

    /**
     * DefaultConfigProvider constructor.
     * @param CheckoutHelper $checkoutHelper
     * @param CheckoutSession $checkoutSession
     * @param CustomerRepository $customerRepository
     * @param CustomerSession $customerSession
     * @param CustomerUrlManager $customerUrlManager
     * @param HttpContext $httpContext
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteItemRepository $quoteItemRepository
     * @param ShippingMethodManager $shippingMethodManager
     * @param ConfigurationPool $configurationPool
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param LocaleFormat $localeFormat
     * @param Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param FormKey $formKey
     * @param Image $imageHelper
     * @param \Magento\Framework\View\ConfigInterface $viewConfig
     * @param ConfigInterface $postCodesConfig
     * @param ImageProvider $imageProvider
     * @param Data $directoryHelper
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $shippingMethodConfig
     * @param StoreManagerInterface $storeManager
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param UrlInterface $urlBuilder
     * @param AddressMetadataInterface|null $addressMetadata
     * @param AttributeOptionManagementInterface|null $attributeOptionManager
     * @param CustomerAddressDataProvider|null $customerAddressData
     */
    public function __construct(
        CheckoutHelper $checkoutHelper,
        CheckoutSession $checkoutSession,
        CustomerRepository $customerRepository,
        CustomerSession $customerSession,
        CustomerUrlManager $customerUrlManager,
        HttpContext $httpContext,
        CartRepositoryInterface $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        ShippingMethodManager $shippingMethodManager,
        ConfigurationPool $configurationPool,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        LocaleFormat $localeFormat,
        Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        FormKey $formKey,
        Image $imageHelper,
        \Magento\Framework\View\ConfigInterface $viewConfig,
        ConfigInterface $postCodesConfig,
        ImageProvider $imageProvider,
        Data $directoryHelper,
        CartTotalRepositoryInterface $cartTotalRepository,
        ScopeConfigInterface $scopeConfig,
        Config $shippingMethodConfig,
        StoreManagerInterface $storeManager,
        PaymentMethodManagementInterface $paymentMethodManagement,
        UrlInterface $urlBuilder,
        AddressMetadataInterface $addressMetadata = null,
        AttributeOptionManagementInterface $attributeOptionManager = null,
        CustomerAddressDataProvider $customerAddressData = null
    ) {
        parent::__construct(
            $checkoutHelper,
            $checkoutSession,
            $customerRepository,
            $customerSession,
            $customerUrlManager,
            $httpContext,
            $quoteRepository,
            $quoteItemRepository,
            $shippingMethodManager,
            $configurationPool,
            $quoteIdMaskFactory,
            $localeFormat,
            $addressMapper,
            $addressConfig,
            $formKey,
            $imageHelper,
            $viewConfig,
            $postCodesConfig,
            $imageProvider,
            $directoryHelper,
            $cartTotalRepository,
            $scopeConfig,
            $shippingMethodConfig,
            $storeManager,
            $paymentMethodManagement,
            $urlBuilder,
            $addressMetadata,
            $attributeOptionManager,
            $customerAddressData
        );

        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerUrlManager = $customerUrlManager;
        $this->httpContext = $httpContext;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->configurationPool = $configurationPool;
        $this->addressMetadata = $addressMetadata ?: ObjectManager::getInstance()->get(AddressMetadataInterface::class);
        $this->attributeOptionManager = $attributeOptionManager ??
            ObjectManager::getInstance()->get(AttributeOptionManagementInterface::class);
        $this->customerAddressData = $customerAddressData ?:
            ObjectManager::getInstance()->get(CustomerAddressDataProvider::class);
    }

    /**
     * Return configuration array
     *
     * @return array|mixed
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getConfig()
    {
        $quote = $this->checkoutSession->getQuote();
        $quoteId = $quote->getId();
        $email = $quote->getShippingAddress()->getEmail();
        $quoteItemData = $this->getQuoteItemData();
        $output['formKey'] = $this->formKey->getFormKey();
        $output['customerData'] = $this->getCustomerData();
        $output['quoteData'] = $this->getQuoteData();
        $output['quoteItemData'] = $quoteItemData;
        $output['quoteMessages'] = $this->getQuoteItemsMessages($quoteItemData);
        $output['isCustomerLoggedIn'] = $this->isCustomerLoggedIn();
        $output['selectedShippingMethod'] = $this->getSelectedShippingMethod();
        if ($email && !$this->isCustomerLoggedIn()) {
            $shippingAddressFromData = $this->getAddressFromData($quote->getShippingAddress());
            $billingAddressFromData = $this->getAddressFromData($quote->getBillingAddress());
            $output['shippingAddressFromData'] = $shippingAddressFromData;
            if ($shippingAddressFromData != $billingAddressFromData) {
                $output['billingAddressFromData'] = $billingAddressFromData;
            }
            $output['validatedEmailValue'] = $email;
        }
        $output['storeCode'] = $this->getStoreCode();
        $output['isGuestCheckoutAllowed'] = $this->isGuestCheckoutAllowed();
        $output['isCustomerLoginRequired'] = $this->isCustomerLoginRequired();
        $output['registerUrl'] = $this->getRegisterUrl();
        $output['checkoutUrl'] = $this->getCheckoutUrl();
        $output['defaultSuccessPageUrl'] = $this->getDefaultSuccessPageUrl();
        $output['pageNotFoundUrl'] = $this->pageNotFoundUrl();
        $output['forgotPasswordUrl'] = $this->getForgotPasswordUrl();
        $output['staticBaseUrl'] = $this->getStaticBaseUrl();
        $output['priceFormat'] = $this->localeFormat->getPriceFormat(
            null,
            $quote->getQuoteCurrencyCode()
        );
        $output['basePriceFormat'] = $this->localeFormat->getPriceFormat(
            null,
            $quote->getBaseCurrencyCode()
        );
        $output['postCodes'] = $this->postCodesConfig->getPostCodes();
        $output['imageData'] = $this->imageProvider->getImages($quoteId);

        $output['totalsData'] = $this->getTotalsData();
        $output['shippingPolicy'] = [
            'isEnabled'             => $this->scopeConfig->isSetFlag(
                'shipping/shipping_policy/enable_shipping_policy',
                ScopeInterface::SCOPE_STORE
            ),
            'shippingPolicyContent' => nl2br(
                $this->scopeConfig->getValue(
                    'shipping/shipping_policy/shipping_policy_content',
                    ScopeInterface::SCOPE_STORE
                )
            )
        ];
        $output['useQty'] = $this->scopeConfig->isSetFlag(
            'checkout/cart_link/use_qty',
            ScopeInterface::SCOPE_STORE
        );
        $output['activeCarriers'] = $this->getActiveCarriers();
        $output['originCountryCode'] = $this->getOriginCountryCode();
        $output['paymentMethods'] = $this->getPaymentMethods();
        $output['autocomplete'] = $this->isAutocompleteEnabled();
        $output['displayBillingOnPaymentMethod'] = $this->checkoutHelper->isDisplayBillingOnPaymentMethodAvailable();
        return $output;
    }

    /**
     * Retrieve quote item data
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getQuoteItemData(): array
    {
        $quoteItemData = [];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $quoteItems = $this->quoteItemRepository->getList($quoteId);
            foreach ($quoteItems as $index => $quoteItem) {
                $product = $quoteItem->getProduct();
                if ($product->getTypeId() == "configurable") {
                    foreach ($quoteItem->getChildren() as $childItem) {
                        $product->setName($childItem->getName());
                        $quoteItem->setName($childItem->getName());
                        $quoteItem->setProduct($product);
                    }
                }
                $quoteItemData[$index] = $quoteItem->toArray();
                $quoteItemData[$index]['options'] = $this->getFormattedOptionValue($quoteItem);
                $quoteItemData[$index]['thumbnail'] = $this->imageHelper->init(
                    $quoteItem->getProduct(),
                    'product_thumbnail_image'
                )->getUrl();
                $quoteItemData[$index]['message'] = $quoteItem->getMessage();
            }
        }

        return $quoteItemData;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     */
    private function getCustomerData(): array
    {
        $customerData = [];
        if ($this->isCustomerLoggedIn()) {
            /** @var CustomerInterface $customer */
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customerData = $customer->__toArray();
            $customerData['addresses'] = $this->customerAddressData->getAddressDataByCustomer($customer);
        }
        return $customerData;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoggedIn()
    {
        return (bool) $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Retrieve quote data
     *
     * @return array
     */
    private function getQuoteData()
    {
        $quoteData = [];
        if ($this->checkoutSession->getQuote()->getId()) {
            $quote = $this->quoteRepository->get($this->checkoutSession->getQuote()->getId());
            $quoteData = $quote->toArray();
            if (null !== $quote->getExtensionAttributes()) {
                $quoteData['extension_attributes'] = $quote->getExtensionAttributes()->__toArray();
            }
            $quoteData['is_virtual'] = $quote->getIsVirtual();

            if (!$quote->getCustomer()->getId()) {
                /** @var $quoteIdMask QuoteIdMask */
                $quoteIdMask = $this->quoteIdMaskFactory->create();
                $quoteData['entity_id'] = $quoteIdMask->load(
                    $this->checkoutSession->getQuote()->getId(),
                    'quote_id'
                )->getMaskedId();
            }
        }
        return $quoteData;
    }

    /**
     * Get notification messages for the quote items
     *
     * @param array $quoteItemData
     * @return array
     */
    private function getQuoteItemsMessages(array $quoteItemData): array
    {
        $quoteItemsMessages = [];
        if ($quoteItemData) {
            foreach ($quoteItemData as $item) {
                $quoteItemsMessages[$item['item_id']] = $item['message'];
            }
        }

        return $quoteItemsMessages;
    }

    /**
     * Retrieve selected shipping method
     *
     * @return array|null
     */
    private function getSelectedShippingMethod()
    {
        $shippingMethodData = null;
        try {
            $quoteId = $this->checkoutSession->getQuote()->getId();
            $shippingMethod = $this->shippingMethodManager->get($quoteId);
            if ($shippingMethod) {
                $shippingMethodData = $shippingMethod->__toArray();
            }
        } catch (Exception $exception) {
            $shippingMethodData = null;
        }
        return $shippingMethodData;
    }

    /**
     * Create address data appropriate to fill checkout address form
     *
     * @param AddressInterface $address
     * @return array
     * @throws LocalizedException
     */
    private function getAddressFromData(AddressInterface $address)
    {
        $addressData = [];
        $attributesMetadata = $this->addressMetadata->getAllAttributesMetadata();
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }
            $attributeCode = $attributeMetadata->getAttributeCode();
            $attributeData = $address->getData($attributeCode);
            if ($attributeData) {
                if ($attributeMetadata->getFrontendInput() === Multiline::NAME) {
                    $attributeData = is_array($attributeData) ? $attributeData : explode("\n", $attributeData);
                    $attributeData = (object) $attributeData;
                }
                if ($attributeMetadata->isUserDefined()) {
                    $addressData[CustomAttributesDataInterface::CUSTOM_ATTRIBUTES][$attributeCode] = $attributeData;
                    continue;
                }
                $addressData[$attributeCode] = $attributeData;
            }
        }
        return $addressData;
    }

    /**
     * Retrieve store code
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getStoreCode()
    {
        return $this->checkoutSession->getQuote()->getStore()->getCode();
    }

    /**
     * Check if guest checkout is allowed
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isGuestCheckoutAllowed()
    {
        return $this->checkoutHelper->isAllowedGuestCheckout($this->checkoutSession->getQuote());
    }

    /**
     * Check if customer must be logged in to proceed with checkout
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoginRequired()
    {
        return $this->checkoutHelper->isCustomerMustBeLogged();
    }

    /**
     * Return forgot password URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getForgotPasswordUrl()
    {
        return $this->customerUrlManager->getForgotPasswordUrl();
    }

    /**
     * Return quote totals data
     *
     * @return array
     */
    private function getTotalsData()
    {
        /** @var TotalsInterface $totals */
        $totals = $this->cartTotalRepository->get($this->checkoutSession->getQuote()->getId());
        $items = [];
        /** @var  Item $item */
        foreach ($totals->getItems() as $item) {
            $items[] = $item->__toArray();
        }
        $totalSegmentsData = [];
        /** @var TotalSegment $totalSegment */
        foreach ($totals->getTotalSegments() as $totalSegment) {
            $totalSegmentArray = $totalSegment->toArray();
            if (is_object($totalSegment->getExtensionAttributes())) {
                $totalSegmentArray['extension_attributes'] = $totalSegment->getExtensionAttributes()->__toArray();
            }
            $totalSegmentsData[] = $totalSegmentArray;
        }
        $totals->setItems($items);
        $totals->setTotalSegments($totalSegmentsData);
        $totalsArray = $totals->toArray();
        if (is_object($totals->getExtensionAttributes())) {
            $totalsArray['extension_attributes'] = $totals->getExtensionAttributes()->__toArray();
        }
        return $totalsArray;
    }

    /**
     * Returns active carriers codes
     *
     * @return array
     */
    private function getActiveCarriers()
    {
        $activeCarriers = [];
        foreach ($this->shippingMethodConfig->getActiveCarriers() as $carrier) {
            $activeCarriers[] = $carrier->getCarrierCode();
        }
        return $activeCarriers;
    }

    /**
     * Returns origin country code
     *
     * @return string
     */
    private function getOriginCountryCode()
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_ORIGIN_COUNTRY_ID,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    /**
     * Returns array of payment methods
     *
     * @return array $paymentMethods
     * @throws NoSuchEntityException
     */
    private function getPaymentMethods()
    {
        $paymentMethods = [];
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getIsVirtual()) {
            foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
                $paymentMethods[] = [
                    'code'  => $paymentMethod->getCode(),
                    'title' => $paymentMethod->getTitle()
                ];
            }
        }
        return $paymentMethods;
    }

    /**
     * Is autocomplete enabled for storefront
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function isAutocompleteEnabled()
    {
        return $this->scopeConfig->getValue(
            Form::XML_PATH_ENABLE_AUTOCOMPLETE,
            ScopeInterface::SCOPE_STORE
        ) ? 'on' : 'off';
    }
}
