<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Api;

/**
 * Interface UpdaterInterface
 */
interface UpdaterInterface
{
    /**
     * Get Updater Name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Update Attributes
     *
     * @param string[] $skus
     */
    public function run(array $skus = []);
}
