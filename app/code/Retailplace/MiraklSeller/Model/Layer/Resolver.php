<?php

namespace Retailplace\MiraklSeller\Model\Layer;

/**
 * Retailplace MiraklSeller model layer resolver class
 */
class Resolver extends \Magento\Catalog\Model\Layer\Resolver
{
    /**
     * @var \Retailplace\MiraklSeller\Model\Layer
     */
    protected $layer;

    /**
     * Catalog view layer models list
     *
     * @var array
     */
    protected $layersPool;
    
    /**
     * Resolver constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Retailplace\MiraklSeller\Model\Layer $layer
     * @param array $layersPool
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Retailplace\MiraklSeller\Model\Layer $layer,
        array $layersPool
    ) {
        $this->layer = $layer;
        $this->layersPool = $layersPool;
        parent::__construct($objectManager, $layersPool);
    }

    public function create($layerType)
    {
        if (isset($this->layer)) {
            throw new \RuntimeException('Catalog Layer has been already created');
        }
        if (!isset($this->layersPool[$layerType])) {
            throw new \InvalidArgumentException($layerType . ' does not belong to any registered layer');
        }
        $this->layer = $this->objectManager->create($this->layersPool[$layerType]);
    }
}
