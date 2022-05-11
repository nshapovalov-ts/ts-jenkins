<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Export;

abstract class AbstractProductSynchronizationStatus
{
    const SYNCHRONIZED       = 'SYNCHRONIZED';
    const PENDING            = 'PENDING';
    const INTEGRATION_ERRORS = 'INTEGRATION_ERRORS';
}