<?php
namespace Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Transaction;

use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\FrontOperator\Domain\Payment\Transaction\TransactionLine;

/**
 * @method  TransactionLine current()
 * @method  TransactionLine first()
 * @method  TransactionLine get($offset)
 * @method  TransactionLine offsetGet($offset)
 * @method  TransactionLine last()
 */
class TransactionLineCollection extends MiraklCollection
{
    /**
     * @var string
     */
    protected $itemClass = TransactionLine::class;
}