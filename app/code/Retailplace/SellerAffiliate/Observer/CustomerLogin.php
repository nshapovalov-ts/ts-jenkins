<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Observer;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Retailplace\SellerAffiliate\Model\SellerAffiliateManagement;
use Retailplace\SellerAffiliate\Api\Data\SellerAffiliateInterface;

/**
 * Class CustomerLogin implements observer for customer login
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CustomerLogin implements ObserverInterface
{
    /** @var CustomerSession */
    private $customerSession;

    /** @var SellerAffiliateInterface */
    private $serializer;

    /** @var CookieManagerInterface */
    private $cookieManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /** @var array */
    private $cookie = [];

    /**
     * Observer constructor
     *
     * @param CustomerSession $customerSession
     * @param CookieManagerInterface $cookieManager
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param SellerAffiliateManagement $sellerAffiliateManagement
     */
    public function __construct(
        CustomerSession $customerSession,
        CookieManagerInterface $cookieManager,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        SellerAffiliateManagement $sellerAffiliateManagement
    ) {
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
    }

    /**
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        try {
            $affiliateData = $this->getAffiliateKeyFromCookie();
            if ($this->customerSession->isLoggedIn() && $this->customerSession->getCustomer() && $affiliateData) {
                $customerId = (int) $this->customerSession->getCustomer()->getId();
                $affiliateData = $this->serializer->unserialize($affiliateData);
                foreach ($affiliateData as $affiliate) {
                    $sellerId = (int) $affiliate[SellerAffiliateManagement::AFFILIATE_COOKIE_SELLER_ID];
                    $affiliateUrl = (string) $affiliate[SellerAffiliateManagement::AFFILIATE_COOKIE_CURRENT_URL] ?? '';
                    $currentTime = (string) $affiliate[SellerAffiliateManagement::AFFILIATE_COOKIE_CURRENT_DATE] ?? '';
                    $this->sellerAffiliateManagement->createSellerAffiliateEntity(
                        $sellerId,
                        $customerId,
                        $affiliateUrl,
                        $currentTime
                    );
                }
            }
        } catch (Exception $exception) {
            $this->logger->critical($exception);
        }
    }

    /**
     * @return string|null
     */
    private function getAffiliateKeyFromCookie(): ?string
    {
        if (!isset($this->cookie[SellerAffiliateManagement::AFFILIATE_COOKIE_NAME])) {
            $this->cookie[SellerAffiliateManagement::AFFILIATE_COOKIE_NAME]
                = $this->cookieManager->getCookie(SellerAffiliateManagement::AFFILIATE_COOKIE_NAME);
        }

        return $this->cookie[SellerAffiliateManagement::AFFILIATE_COOKIE_NAME];
    }
}
