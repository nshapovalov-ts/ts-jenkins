<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Synchronization;

abstract class AbstractProductSynchronizationAcceptanceStatus
{
    const STATUS_ACCEPTED          = 'ACCEPTED';
    const STATUS_CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    const STATUS_REJECTED          = 'REJECTED';
    const STATUS_TO_REVIEW         = 'TO_REVIEW';
}