<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Retailplace\Insider\Api\InsiderObjectProviderInterface;

/**
 * InsiderObjectProvider class
 */
class InsiderObjectProvider implements InsiderObjectProviderInterface
{
    /** @var InsiderObjectProviderInterface[] */
    private $insiderProviders;

    /**
     * InsiderObjectProvider constructor
     *
     * @param InsiderObjectProviderInterface[] $insiderProviders
     * @codeCoverageIgnore
     */
    public function __construct(
        array $insiderProviders
    ) {
        $this->insiderProviders = $insiderProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array
    {
        $config = [];
        foreach ($this->insiderProviders as $insiderProvider) {
            $config = array_merge_recursive($config, $insiderProvider->getConfig());
        }

        return $config;
    }
}
