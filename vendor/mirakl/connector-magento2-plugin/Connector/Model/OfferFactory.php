<?php
namespace Mirakl\Connector\Model;

use Magento\Framework\ObjectManagerInterface;

class OfferFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var string
     */
    protected $_instanceName;

    /**
     * @param   ObjectManagerInterface  $objectManager
     * @param   string                  $instanceName
     */
    public function __construct(ObjectManagerInterface $objectManager, $instanceName = Offer::class)
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Creates an offer
     *
     * @param   array   $data
     * @return  Offer
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }

    /**
     * Creates an offer and sets data according to specified JSON
     *
     * @param   string  $json
     * @param   array   $data
     * @return  Offer
     */
    public function fromJson($json, $data = [])
    {
        return $this->create($data)->setData(json_decode($json, true));
    }
}
