<?php
namespace Retailplace\Utility\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Unserialize\Unserialize;

class Data extends AbstractHelper
{
    /**
     * @var null|SerializerInterface
     */
    private $serializer;
    /**
     * @var Unserialize
     */
    private $unserialize;

    /**
     * Helper constructor.
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Unserialize $unserialize
    ) {
        parent::__construct($context);
        if (interface_exists(SerializerInterface::class)) {
            // for magento later then 2.2
            $this->serializer = $objectManager->get(Serialize::class);
        }
        $this->unserialize = $unserialize;
    }

    /**
     * @param $value
     * @return array|bool|float|int|mixed|string|null
     */
    public function unserialize($value)
    {
        if (false === $value || null === $value || '' === $value) {
            return [];
        }
        try {
            if ($this->serializer === null) {
                return $this->unserialize->unserialize($value);
            }
            return $this->serializer->unserialize($value);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @param $value
     * @return bool|string
     */
    public function serialize($value)
    {
        try {
            return $this->serializer->serialize($value);
        } catch (\Exception $e) {
            return '{}';
        }
    }
}
