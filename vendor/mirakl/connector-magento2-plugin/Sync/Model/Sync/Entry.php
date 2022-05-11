<?php
namespace Mirakl\Sync\Model\Sync;

use Magento\Framework\DataObject;

/**
 * @method  string  getButtons()
 * @method  $this   setButtons(string $buttons)
 * @method  string  getDescription()
 * @method  $this   setDescription(string $description)
 * @method  string  getLastSyncDate()
 * @method  $this   setLastSyncDate(string $lastSyncDate)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  bool    getStatus()
 * @method  $this   setStatus(bool $status)
 */
class Entry extends DataObject
{
    /**
     * @var Entry\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param   Entry\CollectionFactory $collectionFactory
     * @param   array                   $data
     */
    public function __construct(Entry\CollectionFactory $collectionFactory, array $data = [])
    {
        parent::__construct($data);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return  Entry\Collection
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }
}