<?php
namespace Mirakl\FrontendDemo\Helper;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\OrderFactory;
use Mirakl\Api\Helper\Message as MessageApi;
use Mirakl\Api\Helper\Reason as ReasonApi;
use Mirakl\Core\Helper\Config as CoreConfig;
use Mirakl\MMP\Common\Domain\Collection\Message\Thread\ThreadParticipantCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadEntity;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\FrontOperator\Domain\Reason;

class Message extends AbstractHelper
{
    /**
     * @var CoreConfig
     */
    protected $coreConfig;

    /**
     * @var MessageApi
     */
    protected $messageApi;

    /**
     * @var ReasonApi
     */
    protected $reasonApi;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerResource
     */
    protected $customerResource;

    /**
     * @var array
     */
    protected $reasonLabels;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @param  Context          $context
     * @param  CoreConfig       $coreConfig
     * @param  MessageApi       $messageApi
     * @param  ReasonApi        $reasonApi
     * @param  Session          $customerSession
     * @param  CustomerResource $customerResource
     * @param  OrderFactory     $orderFactory
     */
    public function __construct(
        Context $context,
        CoreConfig $coreConfig,
        MessageApi $messageApi,
        ReasonApi $reasonApi,
        Session $customerSession,
        CustomerResource $customerResource,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->coreConfig = $coreConfig;
        $this->messageApi = $messageApi;
        $this->reasonApi = $reasonApi;
        $this->customerSession = $customerSession;
        $this->customerResource = $customerResource;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param   ThreadEntity    $entity
     * @return  string
     */
    public function getEntityName(ThreadEntity $entity)
    {
        switch ($entity->getType()) {
            case 'MMP_ORDER':
            case 'MPS_ORDER':
                return 'Order';

            case 'MMP_OFFER':
            case 'MPS_OFFER':
                return 'Product';
        }

        return '';
    }

    /**
     * @param   Thread  $thread
     * @param   array   $excludeTypes
     * @return  array
     */
    public function getCurrentParticipantsNames(Thread $thread, $excludeTypes = [])
    {
        return $this->getParticipantsNames($thread->getCurrentParticipants(), $excludeTypes);
    }

    /**
     * @param   Thread  $thread
     * @param   array   $excludeTypes
     * @return  array
     */
    public function getAuthorizedParticipantsNames(Thread $thread, array $excludeTypes = [])
    {
        return $this->getParticipantsNames($thread->getAuthorizedParticipants(), $excludeTypes);
    }

    /**
     * @param   ThreadParticipantCollection $participants
     * @param   array                       $excludeTypes
     * @return  array
     */
    protected function getParticipantsNames(ThreadParticipantCollection $participants, array $excludeTypes = [])
    {
        $participantsNames = [];

        /** @var ThreadParticipant $participant */
        foreach ($participants as $participant) {
            if (!empty($excludeTypes) && in_array($participant->getType(), $excludeTypes)) {
                continue;
            }
            $participantsNames[] = $participant->getDisplayName();
        }

        return $participantsNames;
    }

    /**
     * @param   Thread  $thread
     * @return  string
     */
    public function getTopic(Thread $thread)
    {
        $thread = $thread->toArray();

        if (!isset($thread['topic']['type']) || !isset($thread['topic']['value'])) {
            return '';
        }

        $topicValue = $thread['topic']['value'];
        if ($thread['topic']['type'] == 'REASON_CODE') {
            $reasonLabels = $this->getReasonLabels();

            return isset($reasonLabels[$topicValue]) ? $reasonLabels[$topicValue] : '';
        }

        return $topicValue;
    }

    /**
     * @return  array
     */
    protected function getReasonLabels()
    {
        if ($this->reasonLabels === null) {
            $locale = $this->coreConfig->getLocale();
            $reasons = $this->reasonApi->getReasons($locale);

            $this->reasonLabels = [];
            /** @var Reason $reason */
            foreach ($reasons as $reason) {
                $this->reasonLabels[$reason->getCode()] = $reason->getLabel();
            }
        }

        return $this->reasonLabels;
    }

    /**
     * @param   string  $string
     * @return  \DateTime
     */
    public function getMiraklDate($string)
    {
        $gmt = new \DateTimeZone('GMT');
        $date = new \DateTime($string);
        $date->setTimezone($gmt);

        return $date;
    }

    /**
     * @param   string  $miraklOrderId
     * @return  string
     */
    public function getIncrementIdFromMiraklOrderId($miraklOrderId)
    {
        return preg_replace('/(.*)(\-\w+)/', '$1', $miraklOrderId);
    }

    /**
     * @param   string  $incrementId
     * @return  \Magento\Sales\Model\Order
     */
    public function getOrderFromIncrementId($incrementId)
    {
        $order = $this->orderFactory->create();
        $order->loadByIncrementId($incrementId);

        return $order;
    }

    /**
     * @param   string  $miraklOrderId
     * @return  \Magento\Sales\Model\Order
     */
    public function getOrderFromMiraklOrderId($miraklOrderId)
    {
        $incrementId = $this->getIncrementIdFromMiraklOrderId($miraklOrderId);

        return $this->getOrderFromIncrementId($incrementId);
    }

    /**
     * @param   Thread  $thread
     * @return  \Magento\Sales\Model\Order|null
     */
    public function getOrderFromThread(Thread $thread)
    {
        $order = null;
        $entities = $thread->getEntities();

        if (!empty($entities)) {
            $miraklOrderId = $entities->first()->getId();
            if ($incrementId = $this->getIncrementIdFromMiraklOrderId($miraklOrderId)) {
                $order = $this->getOrderFromIncrementId($incrementId);
                if ($order && $order->getId()) {
                    $order->setMiraklOrderId($miraklOrderId);
                }
            }
        }

        return $order;
    }
}
