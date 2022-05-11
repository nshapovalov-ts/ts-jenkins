<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\SellerMessage\Block\Message;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Mirakl\Core\Model\Shop;
use Mirakl\FrontendDemo\Block\Product\View\Tab\Offers;

class Show extends Template
{
    protected $shop;
    /**
     * @var Offers
     */
    private $offerBlock;
    /**
     * @var Registry
     */
    private $coreRegistry;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Session
     */
    private $customer;
    private $productSku;
    private $_currentSeller;
    private $_sellerLogo;
    private $_sellerName;

    /**
     * Constructor
     *
     * @param TemplateContext $context
     * @param ProductRepositoryInterface $productRepository
     * @param Session $customer
     * @param Offers $offerBlock
     * @param Context $productContext
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        ProductRepositoryInterface $productRepository,
        Session $customer,
        Offers $offerBlock,
        Context $productContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRepository = $productRepository;
        $this->offerBlock = $offerBlock;
        $this->customer = $customer;
        $this->coreRegistry = $productContext->getRegistry();
    }

    /**
     * @return Shop
     * @throws NoSuchEntityException
     */
    public function getSeller(): Shop
    {
        if ($this->_currentSeller === null) {
            $currentSeller = $this->getCurrentSeller();
            if (!empty($currentSeller)) {
                $this->_currentSeller = $currentSeller;
            }
        }

        return $this->_currentSeller;
    }

    /**
     * Get Seller Name
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSellerName(): string
    {
        if ($this->_sellerName !== null) {
            return $this->_sellerName;
        }

        $seller = $this->getSeller();
        if (!empty($seller)) {
            $this->_sellerName = $seller->getName();
        } else {
            $this->_sellerName = "";
        }

        return $this->_sellerName;
    }

    /**
     * Set Seller Name
     *
     * @param $sellerName
     * @return $this
     */
    public function setSellerName($sellerName): Show
    {
        $this->_sellerName = $sellerName;
        return $this;
    }

    /**
     * Get Seller Image
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSellerImage(): string
    {
        if ($this->_sellerLogo !== null) {
            return $this->_sellerLogo;
        }

        $seller = $this->getSeller();
        if (!empty($seller)) {
            $this->_sellerLogo = (string)$seller->getLogo();
        } else {
            $this->_sellerLogo = "";
        }

        return $this->_sellerLogo;
    }

    /**
     * Set Seller Image
     *
     * @param $sellerImage
     * @return Show
     */
    public function setSellerImage($sellerImage): Show
    {
        $this->_sellerLogo = $sellerImage;
        return $this;
    }

    /**
     * Get Current Seller
     *
     * @return null|Shop
     * @throws NoSuchEntityException
     */
    public function getCurrentSeller(): ?Shop
    {
        if ($this->shop !== null) {
            return $this->shop;
        }

        $productSku = $this->getProductSku();
        $product = $this->productRepository->get($productSku);
        $this->coreRegistry->unregister('product');
        $this->coreRegistry->register('product', $product);
        if ($product) {
            $bestOffer = $this->offerBlock->getOfferHelper()->getBestOffer($product);
            if ($bestOffer) {
                $shop = $this->offerBlock->getOfferHelper()->getOfferShop($bestOffer);
                $this->shop = $shop;
            }
        }

        return $this->shop;
    }

    /**
     * Get Customer
     *
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer->getCustomer();
    }

    /**
     * Get Submit Url
     *
     * @return string
     */
    public function getSubmitUrl(): string
    {
        return $this->getUrl("sellermessage/message/send");
    }

    /**
     * @param string $sku
     * @return Show
     */
    public function setProductSku(string $sku): Show
    {
        $this->productSku = $sku;
        return $this;
    }

    /**
     * @return string
     */
    public function getProductSku(): string
    {
        return $this->productSku;
    }
}
