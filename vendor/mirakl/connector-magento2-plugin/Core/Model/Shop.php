<?php
namespace Mirakl\Core\Model;

use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Mirakl\FrontendDemo\Model\Evaluation\EvaluationFormatter;

/**
 * @method  mixed   setAdditionalInfo(mixed $info)
 * @method  string  getClosedFrom()
 * @method  $this   setClosedFrom(string $closedFrom)
 * @method  string  getClosedTo()
 * @method  $this   setClosedTo(string $closedTo)
 * @method  string  getDateCreated()
 * @method  $this   setDateCreated(string $dateCreated)
 * @method  string  getDescription()
 * @method  $this   setDescription(int $description)
 * @method  int     getEavOptionId()
 * @method  $this   setEavOptionId(int $eavOptionId)
 * @method  bool    getFreeShipping()
 * @method  $this   setFreeShipping(bool $flag)
 * @method  bool    getPremium()
 * @method  $this   setPremium(bool $flag)
 * @method  bool    getProfessional()
 * @method  $this   setProfessional(bool $flag)
 * @method  string  getLogo()
 * @method  $this   setLogo(string $logo)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  string  getState()
 * @method  $this   setState(string $state)
 * @method  string  getId()
 * @method  $this   setId($shopId)
 * @method  float   getGrade()
 * @method  $this   setGrade(float $grade)
 * @method  int     getEvaluationsCount()
 * @method  $this   setEvaluationsCount(int $evaluationsCount)
 */
class Shop extends AbstractModel
{
    const STATE_OPEN      = 'OPEN';
    const STATE_CLOSE     = 'CLOSE';
    const STATE_SUSPENDED = 'SUSPENDED';

    const SHOP_ID = 'id'; // We define the id fieldname

    /**
     * @var array
     */
    private static $states = [
        self::STATE_OPEN      => self::STATE_OPEN,
        self::STATE_SUSPENDED => self::STATE_SUSPENDED,
        self::STATE_CLOSE     => self::STATE_CLOSE,
    ];

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'shops'; // parent value is 'core_abstract'

    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'shop'; // parent value is 'object'

    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = self::SHOP_ID; // parent value is 'id'

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   UrlInterface                $urlBuilder
     * @param   AbstractResource|null       $resource
     * @param   AbstractDbCollection|null   $resourceCollection
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        UrlInterface $urlBuilder,
        AbstractResource $resource = null,
        AbstractDbCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Shop::class);
    }

    /**
     * @return  DataObject
     */
    public function getAdditionalInfo()
    {
        $info = new DataObject();
        $data = $this->_getData('additional_info');
        if (is_string($data)) {
            $data = unserialize($data);
        }
        if (is_array($data)) {
            $info->setData($data);
        }

        return $info;
    }

    /**
     * @param   int $stars
     * @return  float
     */
    public function getFormattedGrade($stars = 5)
    {
        return EvaluationFormatter::format($this->getGrade(), $stars);
    }

    /**
     * @return  array
     */
    public static function getStates()
    {
        return self::$states;
    }

    /**
     * @return  string
     */
    public function getUrl()
    {
        return $this->urlBuilder->getUrl('marketplace/shop/view', [
            'id' => $this->getId()
        ]);
    }
}
