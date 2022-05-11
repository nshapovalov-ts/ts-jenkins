<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Synchronization;

abstract class AbstractProductSynchronizationOperation
{
    const CREATE_UPDATE = 'CREATE_UPDATE';
    const DELETE        = 'DELETE';
}