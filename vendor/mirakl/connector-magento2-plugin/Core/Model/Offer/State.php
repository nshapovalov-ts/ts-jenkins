<?php
namespace Mirakl\Core\Model\Offer;

use Magento\Framework\Model\AbstractModel;

/**
 * @method  int     getEavOptionId()
 * @method  $this   setEavOptionId(int $eavOptionId)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  int     getSortOrder()
 * @method  $this   setSortOrder(int $sortOrder)
 */
class State extends AbstractModel
{
    const STATE_ID = 'id'; // We define the id fieldname

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'mirakl_offer_state';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getOfferState() in this case
     *
     * @var string
     */
    protected $_eventObject = 'offer_state';

    /**
     * Init resource model and id field
     *
     * @return  void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Mirakl\Core\Model\ResourceModel\Offer\State::class);
        $this->setIdFieldName(self::STATE_ID);
    }
}
