<?php
namespace Mirakl\MCM\Front\Domain\Product\Synchronization;

use Mirakl\MCM\Front\Domain\Collection\Product\ProductSynchronizationErrorCollection;

/**
 * @method  string                                  getStatus()
 * @method  $this                                   setStatus(string $status)
 * @method  ProductSynchronizationErrorCollection   getSynchronizationErrors()
 * @method  $this                                   setSynchronizationErrors(ProductSynchronizationErrorCollection $synchronizationErrorCollection)
 */
class ProductSynchronizationProcessed extends ProductSynchronization
{}