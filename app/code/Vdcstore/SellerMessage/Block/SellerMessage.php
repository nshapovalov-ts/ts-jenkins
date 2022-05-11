<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\SellerMessage\Block;

use Magento\Catalog\Helper\Data as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;

class SellerMessage extends Template
{
    /**
     * @var ProductHelper
     */
    private $productHelper;
    /**
     * @var Context
     */
    private $httpContext;

    /**
     * Constructor
     *
     * @param TemplateContext $context
     * @param ProductHelper $productHelper
     * @param Context $httpContext
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        ProductHelper $productHelper,
        Context $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productHelper = $productHelper;
        $this->httpContext = $httpContext;
    }

    /**
     * @return string
     */
    public function getPopupLinkPath(): string
    {
        return $this->getUrl("sellermessage/message/show", ['product_sku' => $this->getProductSku()]);
    }

    /**
     * @return string
     */
    public function getProductSku(): string
    {
        return $this->productHelper->getProduct()->getSku();
    }

    /**
     * @return mixed|null
     */
    public function isUserLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->productHelper->getProduct();
    }
}

