<?php
/**
 * Retailplace_Wishlist
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Wishlist\Block;

use Magento\Framework\View\Element\Template;
use Retailplace\Wishlist\Model\Wishlist\Config;

/**
 * Class Wishlist
 */
class Wishlist extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Loading state flag
     *
     * @var bool
     */
    protected $isLoaded;

    /**
     * Wishlist constructor.
     * @param Config $config
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Config $config,
        Template\Context $context,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Is Allow
     *
     * @return bool
     */
    public function isAllow()
    {
        return $this->config->isAllow();
    }
}
