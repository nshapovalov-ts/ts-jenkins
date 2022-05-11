<?php
namespace Mirakl\FrontendDemo\Block\Shop;

use Magento\Framework\DataObject;
use Magento\Framework\Data\Collection\EntityFactoryInterface as CollectionEntityFactoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Pager;
use Mirakl\Api\Helper\Shop as ShopHelper;
use Mirakl\Core\Helper\Config as ConfigHelper;
use Mirakl\Core\Model\Collection;
use Mirakl\FrontendDemo\Common\AssessmentTrait;
use Mirakl\MMP\Common\Domain\Collection\Evaluation\EvaluationCollection;
use Mirakl\MMP\Common\Domain\Evaluation;
use Psr\Log\LoggerInterface;

class Evaluations extends View
{
    use AssessmentTrait;

    /**
     * @var ShopHelper
     */
    protected $_shopHelper;

    /**
     * @var ConfigHelper
     */
    protected $_configHelper;

    /**
     * @var EvaluationCollection
     */
    protected $_evaluations;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var Pager
     */
    protected $_pager;

    /**
     * @var CollectionEntityFactoryInterface
     */
    protected $_collectionEntityFactory;

    /**
     * @var string
     */
    protected $_template = 'shop/evaluations.phtml';

    /**
     * @param   Context                             $context
     * @param   Registry                            $registry
     * @param   ShopHelper                          $shopHelper
     * @param   LoggerInterface                     $logger
     * @param   CollectionEntityFactoryInterface    $collectionEntityFactory
     * @param   ConfigHelper                        $configHelper
     * @param   array                               $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ShopHelper $shopHelper,
        LoggerInterface $logger,
        CollectionEntityFactoryInterface $collectionEntityFactory,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->_shopHelper              = $shopHelper;
        $this->_logger                  = $logger;
        $this->_collectionEntityFactory = $collectionEntityFactory;
        $this->_configHelper            = $configHelper;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Returns evaluations of current Mirakl shop
     *
     * @return  EvaluationCollection
     */
    public function getEvaluations()
    {
        if (null === $this->_evaluations) {
            try {
                $locale             = $this->_configHelper->getLocale();
                $this->_evaluations = $this->_shopHelper->getShopEvaluations(
                    $this->getShop()->getId(),
                    $this->_getLimit(),
                    $this->_getOffset(),
                    $locale
                );
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
                $this->getLayout()->getMessagesBlock()->addError($e->getMessage());
                $this->_evaluations = new EvaluationCollection();
            }
        }

        return $this->_evaluations;
    }

    /**
     * Returns evaluation date
     *
     * @param   Evaluation  $evaluation
     * @return  string
     */
    public function getEvaluationDate(Evaluation $evaluation)
    {
        $date = $evaluation->getDate()->format('Y-m-d');

        try {
            return $this->formatDate($date, \IntlDateFormatter::MEDIUM);
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Returns evaluation reviewer
     *
     * @param   Evaluation  $evaluation
     * @return  string
     */
    public function getEvaluationAuthor(Evaluation $evaluation)
    {
        return $evaluation->getFirstname() . ' ' . $evaluation->getLastname();
    }

    /**
     * {@inheritdoc}
     */
    protected function _setTabTitle()
    {
        $title = $this->_convertCollection()->getSize()
            ? __('Reviews %1', '<span class="counter">' . $this->_convertCollection()->getSize() . '</span>')
            : __('Reviews');
        $this->setTitle($title);
    }

    /**
     * Converts a Mirakl collection into a Magento collection
     *
     * @return  Collection
     */
    protected function _convertCollection()
    {
        $collection = new Collection($this->_collectionEntityFactory);

        try {
            $evaluations = $this->getEvaluations();
            if (!empty($evaluations)) {
                foreach ($evaluations as $evaluation) {
                    /** @var Evaluation $evaluation */
                    $collection->addItem(new DataObject($evaluation->getData()));
                }
                $collection->setTotalRecords($evaluations->getTotalCount());
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            $this->getLayout()->getMessagesBlock()->addError($e->getMessage());
        }

        return $collection;
    }

    /**
     * @return  int
     */
    protected function _getLimit()
    {
        return $this->_getPager()->getLimit();
    }

    /**
     * @return  int
     */
    protected function _getOffset()
    {
        return ($this->_getPager()->getCurrentPage() - 1) * $this->_getLimit();
    }

    /**
     * @return  Pager
     */
    protected function _getPager()
    {
        if (empty($this->_pager)) {
            $this->_setPager();
        }

        return $this->_pager;
    }

    /**
     * Set Magento pager template
     *
     * @return  $this
     */
    protected function _setPager()
    {
        if (empty($this->_pager)) {
            $this->_pager = $this->getLayout()->getBlock('mirakl.shop.evaluations.toolbar');
            $this->_pager->setAvailableLimit([5 => 5, 10 => 10, 15 => 15, 20 => 20]);
            $this->_pager->setLimit(intval($this->getRequest()->getParam('limit', 10)));
            $this->_pager->setCollection($this->_convertCollection());
            $this->setChild('toolbar', $this->_pager);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->_setPager();

        return $this;
    }
}