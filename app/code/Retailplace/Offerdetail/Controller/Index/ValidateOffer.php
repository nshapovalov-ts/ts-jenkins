<?php

/**
 * Retailplace_Offerdetail
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Offerdetail\Controller\Index;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Mirakl\MMP\Front\Domain\Shipping\OfferQuantityShippingTypeTuple;

class ValidateOffer extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $jsonHelper;
    protected $offerHelper;

    protected $customerSession;
    protected $addressFactory;
    protected $cache;
    protected $miraklQuoteHelper;
    protected $miraklConfigHelper;
    protected $connectorConfig;
    protected $shippingZoneHelper;
    protected $api;
    protected $cart;
    protected $coreRegistry;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $addressRepository;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Customer\Model\SessionFactory $customerSession
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Mirakl\FrontendDemo\Helper\Quote $miraklQuoteHelper
     * @param \Mirakl\FrontendDemo\Helper\Config $miraklConfigHelper
     * @param \Mirakl\Connector\Helper\Config $connectorConfig
     * @param \Mirakl\Core\Helper\ShippingZone $shippingZoneHelper
     * @param \Mirakl\Api\Helper\Shipping $api
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Mirakl\Connector\Helper\Offer $offerHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Mirakl\FrontendDemo\Helper\Quote $miraklQuoteHelper,
        \Mirakl\FrontendDemo\Helper\Config $miraklConfigHelper,
        \Mirakl\Connector\Helper\Config $connectorConfig,
        \Mirakl\Core\Helper\ShippingZone $shippingZoneHelper,
        \Mirakl\Api\Helper\Shipping  $api,
        \Magento\Checkout\Model\Cart $cart,
        \Mirakl\Connector\Helper\Offer $offerHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;

        //$this->coreRegistry   = $context->getRegistry();
        $this->offerHelper    = $offerHelper;
        $this->customerSession = $customerSession->create();
        $this->addressFactory = $addressFactory;
        $this->cache = $cache;
        $this->miraklQuoteHelper = $miraklQuoteHelper;
        $this->miraklConfigHelper = $miraklConfigHelper;
        $this->connectorConfig = $connectorConfig;
        $this->shippingZoneHelper = $shippingZoneHelper;
        $this->api = $api;
        $this->cart = $cart;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $offerId = $this->getRequest()->getParam('offer_id');
            $sku = $this->getRequest()->getParam('product_sku');
            $flag = 2;
            $cacheKey = "";
            $shopName = "";
            $customerPostcode = 0;
            if ($this->customerSession->isLoggedIn()
                && $this->customerSession->getCustomer()
                && $this->customerSession->getCustomer()->getId()
                && $this->customerSession->getCustomer()->getDefaultShippingAddress()
            ) {
                $flag = 1;
                $quote = $this->cart->getQuote();
                /* if(!$quote->getBillingAddress()){
                     $quote->setBillingAddress($this->addressFactory->create()->load($billingAddressId));
                 }
                 if(!$quote->getShippingAddress()){
                     $quote->setShippingAddress($shippingAddress);
                 }
                 $addressId = $this->customerSession->getCustomer()->getDefaultShipping();
                 $billingAddressId = $this->customerSession->getCustomer()->getDefaultBilling();
                 if (!$addressId) {
                     $addressId = $billingAddressId;
                 }*/
                $shippingAddress = $quote->getShippingAddress() ? $quote->getShippingAddress() : $quote->getBillingAddress();
                $customerPostcode = (int) $shippingAddress->getData('postcode');
                if (!$customerPostcode) {
                    $customerPostcode = $this->getCustomerPostcode($quote->getCustomer());
                }
                $bestOffer = $this->offerHelper->getOfferById($offerId);
                $shop = $this->offerHelper->getOfferShop($bestOffer);

                $itemsQty = $_REQUEST['min_qty'] ?? $bestOffer->getMinOrderQuantity();
                $itemsQty = $itemsQty == 0 ? 1 : $itemsQty;
                $hash = sha1(json_encode([ $itemsQty,$offerId]));
                $cacheKey     =   sprintf('mirakl_shipping_fees_%s_%s_%s_%s_%s', $offerId, $hash, $sku, $customerPostcode, $itemsQty);
                if ($shippingFees = $this->cache->load($cacheKey)) {
                    $shippingFees = unserialize($shippingFees);
                } else {
                    $zone = $this->miraklQuoteHelper->getQuoteShippingZone($quote);
                    $offersWithQty[] = (new OfferQuantityShippingTypeTuple())
                        ->setOfferId($offerId)
                        ->setQuantity($itemsQty);
                    $locale = $this->miraklConfigHelper->getLocale($quote->getStore());
                    $computeTaxes = $this->miraklQuoteHelper->canComputeTaxes($quote);
                    $shippingAddressData = [];
                    if ($computeTaxes) {
                        // Compute taxes in API SH02 if enabled in config and if shipping address is filled
                        $shippingAddressData = $this->miraklQuoteHelper->getShippingAddressData($quote);
                    }
                    $shippingFees = $this->api->getShippingFees($zone, $offersWithQty, $locale, $computeTaxes, $shippingAddressData);
                    $cacheLifetime = 2678400;//1 month
                    $this->cache->save(serialize($shippingFees), $cacheKey, ['BLOCK_HTML', 'MIRAKL', 'MIRAKL_QUOTE_' . $quote->getId()], $cacheLifetime);
                }
                if ($shippingFees && $shippingFees->getErrors()) {
                    foreach ($shippingFees->getErrors() as $error) {
                        $flag = 2;
                        break;
                    }
                }
                $shopName = $shop->getName();
                $response = ['cache' => $cacheKey,'flag' => $flag, 'response' => "success"];
                if ($flag == 2) {
                    $message = __("%1  does not offer delivery of this product to postcode %2.", $shopName, $customerPostcode);
                    $response = ['cache' => $cacheKey,'flag' => $flag,'response' => "failure","message" => $message];
                }
            } else {
                if ($flag == 2) {
                    $message = '';
                    $response = ['cache' => $cacheKey,'flag' => $flag,'response' => "success","message" => $message];
                }
            }

            return $this->jsonResponse($response);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Get Postcode from Customer Address
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return string
     */
    private function getCustomerPostcode(CustomerInterface $customer): string
    {
        $postcode = 0;
        $shippingAddressId = $customer->getDefaultShipping();
        if ($shippingAddressId) {
            try {
                $shippingAddress = $this->addressRepository->getById($shippingAddressId);
                $postcode = $shippingAddress->getPostcode();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        if (!$postcode) {
            $billingAddressId = $customer->getDefaultBilling();
            if ($billingAddressId) {
                try {
                    $billingAddress = $this->addressRepository->getById($billingAddressId);
                    $postcode = $billingAddress->getPostcode();
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return (string) $postcode;
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
