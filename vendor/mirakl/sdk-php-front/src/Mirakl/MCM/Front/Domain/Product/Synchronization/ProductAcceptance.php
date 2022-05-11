<?php
namespace Mirakl\MCM\Front\Domain\Product\Synchronization;

use Mirakl\MCM\FrontOperator\Domain\Product\Synchronization\AbstractProductAcceptance;

class ProductAcceptance extends AbstractProductAcceptance
{
    /** @deprecated Please use Mirakl\MCM\Front\Domain\Product\ProductAcceptanceStatus::STATUS_ACCEPTED instead */
    const STATUS_ACCEPTED = 'ACCEPTED';
    /** @deprecated Please use Mirakl\MCM\Front\Domain\Product\ProductAcceptanceStatus::STATUS_NEW instead */
    const STATUS_NEW      = 'NEW';
    /** @deprecated Please use Mirakl\MCM\Front\Domain\Product\ProductAcceptanceStatus::REJECTED instead */
    const STATUS_REJECTED = 'REJECTED';
}