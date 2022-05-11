<?php
/**
 * Retailplace_Bestsellerlabel
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Bestsellerlabel\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\Product as ModelProduct;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Bestsellerlabel
 */
class Bestsellerlabel extends AbstractHelper
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getNumberOfPurchase()
    {
        return $this->scopeConfig->getValue("bestlabel/config/purchase", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getNumberOfMonth()
    {
        return $this->scopeConfig->getValue("bestlabel/config/pastmonths", ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getBestsellerCategory()
    {
        return $this->scopeConfig->getValue("bestlabel/config/bestsellercat", ScopeInterface::SCOPE_STORE);
    }
}

