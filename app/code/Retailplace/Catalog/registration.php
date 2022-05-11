<?php

/**
 * Retailplace_Catalog
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Retailplace_Catalog',
    __DIR__
);
