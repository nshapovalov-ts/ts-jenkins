<?php
/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class SearchFilter
 */
class SearchFilter
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $wildcardMode;

    /**
     * @var array
     */
    private $fieldsBuilder;

    /**
     * @var bool
     */
    private $isSellerView;

    /**
     * @var array
     */
    private $sellerIds = [];

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Set Mode Wildcard Autocomplete
     *
     * @param string|null $wildcardMode
     */
    public function setModeWildcardAutocomplete(?string $wildcardMode = "")
    {
        if (!$wildcardMode) {
            $wildcardMode = $this->scopeConfig->getValue(
                'search/advanced/wildcard_autocomplete',
                ScopeInterface::SCOPE_STORE
            );
        }

        $this->wildcardMode = $wildcardMode;
    }

    /**
     * Get Mode Wildcard Autocomplete
     *
     * @return string|null
     */
    public function getModeWildcardAutocomplete(): ?string
    {
        return $this->wildcardMode;
    }

    /**
     * Set Seller View
     *
     * @param bool $status
     */
    public function setSellerView(bool $status)
    {
        $this->isSellerView = $status;
    }

    /**
     * Get Seller View
     *
     * @return bool|null
     */
    public function getSellerView(): ?bool
    {
        return $this->isSellerView;
    }

    /**
     * Get Seller Ids
     *
     * @return array
     */
    public function getNewSellerViewIds(): array
    {
        return $this->sellerIds;
    }

    /**
     * Set Seller Ids
     *
     * @param array $sellerIds
     *
     * @return void
     */
    public function setNewSellerViewIds(array $sellerIds): void
    {
        $this->sellerIds = $sellerIds;
    }
}
