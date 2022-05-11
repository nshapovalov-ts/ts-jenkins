<?php

/**
 * Retailplace_Misspell
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Misspell\Repository;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Mirasvit\Misspell\Api\ProviderInterface;
use Mirasvit\Misspell\Repository\ProviderRepository as MisspellProviderRepository;

/**
 * ProviderRepository Class
 */
class ProviderRepository extends MisspellProviderRepository
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProviderInterface[]
     */
    private $providers;

    /**
     * ProviderRepository constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param array $providers
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $providers = []
    ) {
        parent::__construct($scopeConfig, $providers);

        $this->scopeConfig = $scopeConfig;
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider()
    {
        $provider = $this->scopeConfig->getValue('search/engine/engine');
        $isEnableElasticProvider = (bool)$this->scopeConfig->getValue('misspell/general/enable_elastic');

        if (isset($this->providers[$provider])) {
            if (($isEnableElasticProvider && $provider == 'elastic') || $provider != 'elastic') {
                return $this->providers[$provider];
            }
        }

        return $this->providers['mysql'];
    }
}
