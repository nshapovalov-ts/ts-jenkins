<?php
namespace Mirakl\FrontendDemo\Block\Message;

use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\Api\Helper\Message as MessageApi;
use Mirakl\Connector\Model\Offer;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class Index extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var string
     */
    protected $_template = 'Mirakl_FrontendDemo::message/index.phtml';

    /**
     * @var array
     */
    protected $availableLimit = [10 => 10, 20 => 20, 50 => 50];

    /**
     * @var int
     */
    protected $defaultLimit = 10;

    /**
     * @var int
     */
    protected $limit = 10;

    /**
     * @param   Registry    $coreRegistry
     * @param   MessageApi  $messageApi
     * @param   Session     $customerSession
     * @param   Context     $context
     * @param   array       $data
     */
    public function __construct(
        Registry $coreRegistry,
        MessageApi $messageApi,
        Session $customerSession,
        Context $context,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->messageApi = $messageApi;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Messages'));
    }

    /**
     * @return  SeekableCollection
     */
    public function getThreads()
    {
        if ($threads = $this->coreRegistry->registry('mirakl_threads')) {
            return $threads;
        }

        if (!($customerId = $this->customerSession->getCustomerId())) {
            return new SeekableCollection();
        }

        $entityType = null;
        $entityId = null;

        if ($order = $this->getMiraklOrder()) {
            $entityType = 'MMP_ORDER';
            $entityId = $order->getId();
        } elseif ($offer = $this->getMiraklOffer()) {
            $entityType = 'MMP_OFFER';
            $entityId = $offer->getId();
        }

        $token = $this->getRequest()->getParam('token');

        $this->limit = $this->getRequest()->getParam('limit');
        if (!isset($this->availableLimit[$this->limit])) {
            $this->limit = $this->defaultLimit;
        }

        $threads = $this->messageApi->getThreads(
            $customerId,
            $entityType,
            $entityId,
            $this->limit,
            $token
        );

        $this->coreRegistry->register('mirakl_threads', $threads);

        return $threads;
    }

    /**
     * @param   Thread  $thread
     * @return  string
     */
    public function getMessageUrl(Thread $thread)
    {
        return $this->getUrl('marketplace/message/view', ['thread' => $thread->getId()]);
    }

    /**
     * @param   \DateTimeInterface|string   $date
     * @return  string
     */
    public function formatDateShort($date)
    {
        $gmt = new \DateTimeZone('GMT');
        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        $date->setTimezone($gmt);

        $now = new \DateTime();
        $now->setTimezone($gmt);

        if ($date->format('Ymd') == $now->format('Ymd')) {
            return $this->formatTime(\Mirakl\date_format($date));
        }

        return $this->formatDate(\Mirakl\date_format($date));
    }

    /**
     * @return  MiraklOrder
     */
    public function getMiraklOrder()
    {
        return $this->coreRegistry->registry('mirakl_order');
    }

    /**
     * @return  Offer
     */
    public function getMiraklOffer()
    {
        return $this->coreRegistry->registry('offer');
    }

    /**
     * @param   string  $token
     * @return  string
     */
    public function getSeekPageUrl($token)
    {
        return $this->getUrl('*/*/*', ['limit' => $this->limit, 'token' => $token]);
    }

    /**
     * @return  array
     */
    public function getAvailableLimit()
    {
        return $this->availableLimit;
    }

    /**
     * @param   string  $key
     * @return  bool
     */
    public function isLimitCurrent($key)
    {
        return $this->limit == $key;
    }

    /**
     * @param   string  $key
     * @return  string
     */
    public function getLimitUrl($key)
    {
        return $this->getUrl('*/*/*', ['limit' => $key]);
    }
}
