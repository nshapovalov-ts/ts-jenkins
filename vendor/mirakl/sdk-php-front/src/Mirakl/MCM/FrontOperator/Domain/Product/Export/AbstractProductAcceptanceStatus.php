<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Export;

abstract class AbstractProductAcceptanceStatus
{
    const STATUS_ACCEPTED          = 'ACCEPTED';
    const STATUS_CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    const STATUS_NEW               = 'NEW';
    const STATUS_REJECTED          = 'REJECTED';
    const STATUS_TO_REVIEW         = 'TO_REVIEW';
}