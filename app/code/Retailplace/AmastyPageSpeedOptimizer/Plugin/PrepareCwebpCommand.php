<?php

/**
 * Retailplace_AmastyPageSpeedOptimizer
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AmastyPageSpeedOptimizer\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Amasty\PageSpeedOptimizer\Model\OptionSource\WebpOptimization;
use Magento\Framework\Shell;

/**
 * Class PrepareCwebpCommand
 */
class PrepareCwebpCommand
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Shell $object
     * @param string $command
     * @param array $paths
     *
     * @return array
     */
    public function beforeExecute(Shell $object, string $command, array $paths): array
    {
        if (strcasecmp(WebpOptimization::WEBP['command'], $command) == 0) {
            $options = $this->scopeConfig->getValue('amoptimizer/images/webp_additional_parameters');
            $command .= ' ' . $options;
        }

        return [$command, $paths];
    }
}
