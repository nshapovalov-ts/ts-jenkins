<?php
namespace Mirakl\MCM\FrontOperator\Domain\Collection\Product;

use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MCM\FrontOperator\Domain\Product\Synchronization\AbstractProductIntegrationError;

/**
 * @method AbstractProductIntegrationError  current()
 * @method AbstractProductIntegrationError  first()
 * @method AbstractProductIntegrationError  get($offset)
 * @method AbstractProductIntegrationError  offsetGet($offset)
 * @method AbstractProductIntegrationError  last()
 */
abstract class AbstractProductIntegrationErrorCollection extends MiraklCollection
{}