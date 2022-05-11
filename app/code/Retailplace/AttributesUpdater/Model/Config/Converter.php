<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Config;

use Magento\Framework\Config\ConverterInterface;

/**
 * Class Converter
 */
class Converter implements ConverterInterface
{
    /**
     * Convert config
     *
     * @param \DOMDocument $source
     * @return array
     */
    public function convert($source): array
    {
        $updatersNodeList = $source->getElementsByTagName('updaters');
        $updatersData = [];
        foreach ($updatersNodeList as $updaters) {
            foreach ($updaters->childNodes as $updater) {
                if ($updater->nodeType == 1 && $updater->getAttribute('class')) {

                    $sortOrder = (int) $updater->getAttribute('sortOrder');
                    while (isset($updatersData[$sortOrder])) {
                        $sortOrder++;
                    }

                    $updatersData[$sortOrder] = [
                        'class' => $updater->getAttribute('class'),
                        'name' => $updater->getAttribute('name')
                    ];
                }
            }
        }

        return ['updaters' => $updatersData];
    }
}
