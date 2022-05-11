<?php
namespace Mirakl\Sync\Model\Sync\Entry;

use Mirakl\Sync\Model\Sync\Entry;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var string
     */
    protected $_itemObjectClass = Entry::class;
}