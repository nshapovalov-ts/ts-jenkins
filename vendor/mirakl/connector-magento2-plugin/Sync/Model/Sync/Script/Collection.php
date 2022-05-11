<?php
namespace Mirakl\Sync\Model\Sync\Script;

use Mirakl\Sync\Model\Sync\Script;

/**
 * @method  Script  getItemById($id)
 */
class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var string
     */
    protected $_itemObjectClass = Script::class;
}