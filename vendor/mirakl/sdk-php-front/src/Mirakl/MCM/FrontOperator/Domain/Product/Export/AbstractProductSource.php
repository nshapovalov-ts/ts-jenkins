<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Export;

use Mirakl\Core\Domain\MiraklObject;

/**
 * @method string getProviderCode()
 * @method $this  setProviderCode(string $providerCode)
 * @method string getProviderSku()
 * @method $this  setProviderSku(string $providerSku)
*/
abstract class AbstractProductSource extends MiraklObject
{}