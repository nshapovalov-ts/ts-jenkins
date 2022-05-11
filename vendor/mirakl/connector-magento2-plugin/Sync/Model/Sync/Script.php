<?php
namespace Mirakl\Sync\Model\Sync;

use Magento\Framework\DataObject;
use Mirakl\Core\Helper\Config as MiraklConfig;

/**
 * @method  string  getCode()
 * @method  $this   setCode(string $code)
 * @method  string  getConfig()
 * @method  $this   setConfig(string $config)
 * @method  string  getHelper()
 * @method  $this   setHelper(string $helper)
 * @method  bool    getMethod()
 * @method  $this   setMethod(string $method)
 * @method  string  getTitle()
 * @method  $this   setTitle(string $title)
 */
class Script extends DataObject
{
    /**
     * @var MiraklConfig
     */
    protected $miraklConfig;

    /**
     * @var Script\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param   Script\CollectionFactory $collectionFactory
     * @param   MiraklConfig             $miraklConfig
     * @param   array                    $data
     */
    public function __construct(
        Script\CollectionFactory $collectionFactory,
        MiraklConfig $miraklConfig,
        array $data = []
    ) {
        parent::__construct($data);
        $this->miraklConfig = $miraklConfig;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return  Script\Collection
     */
    public function getCollection()
    {
        return $this->collectionFactory->create();
    }

    /**
     * @return  string
     */
    public function getId()
    {
        return $this->getCode();
    }

    /**
     * @return  bool
     */
    public function isSyncDisable()
    {
        if (empty($this->getConfig())) {
            return false;
        }

        return !$this->miraklConfig->getFlag($this->getConfig());
    }
}
