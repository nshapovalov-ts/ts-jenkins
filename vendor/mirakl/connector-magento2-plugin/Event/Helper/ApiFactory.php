<?php
namespace Mirakl\Event\Helper;

use Magento\Framework\ObjectManagerInterface;
use Mirakl\Api\Helper as Api;
use Mirakl\Event\Model\Event;

class ApiFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param   ObjectManagerInterface  $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param   string  $type
     * @return  Api\ExportDataInterface|Api\SynchroResultInterface
     * @throws  \InvalidArgumentException
     */
    public function create($type)
    {
        switch ($type) {
            case Event::TYPE_VL01:
                $instanceName = Api\ValueList::class;
                break;
            case Event::TYPE_H01:
                $instanceName = Api\Hierarchy::class;
                break;
            case Event::TYPE_PM01:
                $instanceName = Api\Attribute::class;
                break;
            case Event::TYPE_CA01:
                $instanceName = Api\Category::class;
                break;
            case Event::TYPE_P21:
                $instanceName = Api\Product::class;
                break;
            case Event::TYPE_CM21:
                $instanceName = Api\Mcm\Product::class;
                break;
            default:
                throw new \InvalidArgumentException('Could not find helper name for event type %1', $type);
        }

        return $this->objectManager->create($instanceName);
    }
}
