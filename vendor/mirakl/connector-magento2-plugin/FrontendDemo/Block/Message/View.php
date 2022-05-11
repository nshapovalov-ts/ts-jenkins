<?php
namespace Mirakl\FrontendDemo\Block\Message;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Mirakl\Connector\Helper\Offer as OfferHelper;
use Mirakl\FrontendDemo\Helper\Message as MessageHelper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadEntity;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;

class View extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var MessageHelper
     */
    protected $messageHelper;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var string
     */
    protected $_template = 'Mirakl_FrontendDemo::message/view.phtml';

    /**
     * @param   Registry            $coreRegistry
     * @param   FormKey             $formKey
     * @param   MessageHelper       $messageHelper
     * @param   OrderFactory        $orderFactory
     * @param   OfferHelper         $offerHelper
     * @param   ProductRepository   $productRepository
     * @param   Context             $context
     * @param   array               $data
     */
    public function __construct(
        Registry $coreRegistry,
        FormKey $formKey,
        MessageHelper $messageHelper,
        OrderFactory $orderFactory,
        OfferHelper $offerHelper,
        ProductRepository $productRepository,
        Context $context,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->formKey = $formKey;
        $this->messageHelper = $messageHelper;
        $this->orderFactory = $orderFactory;
        $this->offerHelper = $offerHelper;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set($this->messageHelper->getTopic($this->getThread()));
    }

    /**
     * @return  MessageHelper
     */
    public function getMessageHelper()
    {
        return $this->messageHelper;
    }

    /**
     * @return  ThreadDetails
     */
    public function getThread()
    {
        return $this->coreRegistry->registry('mirakl_thread');
    }

    /**
     * @return  string
     */
    public function getThreadTitle()
    {
        $titles = [];
        $thread = $this->getThread();
        /** @var ThreadEntity $entity */
        foreach ($thread->getEntities() as $entity) {
            $titles[] = __('%1: %2',
                $this->escapeHtmlAttr(__($this->messageHelper->getEntityName($entity))),
                $this->escapeHtmlAttr($entity->getLabel())
            );
        }

        return implode(', ', $titles);
    }

    /**
     * @param   ThreadMessage   $message
     * @return  bool
     */
    public function isCustomerMessage(ThreadMessage $message)
    {
        return $message->getFrom()->getType() == 'CUSTOMER_USER';
    }

    /**
     * @param   ThreadAttachment    $attachment
     * @return  string
     */
    public function getAttachmentUrl(ThreadAttachment $attachment)
    {
        $thread = $this->getThread();

        return $this->getUrl('*/*/attachment', [
            'id'       => $attachment->getId(),
            'thread'   => $thread->getId(),
            'form_key' => $this->formKey->getFormKey(),
        ]);
    }

    /**
     * @param   ThreadEntity    $entity
     * @return  string
     */
    public function getEntityUrl(ThreadEntity $entity)
    {
        switch ($entity->getType()) {
            case 'MMP_ORDER':
            case 'MPS_ORDER':
                $order = $this->messageHelper->getOrderFromMiraklOrderId($entity->getId());

                if ($order && $order->getId()) {
                    return $this->_urlBuilder->getUrl('marketplace/order/message', [
                        'order_id' => $order->getId(),
                        'remote_id' => $entity->getId(),
                    ]);
                }

                break;

            case 'MMP_OFFER':
            case 'MPS_OFFER':
                $productUrl = $this->getProductUrl($entity->getId());

                if ($productUrl) {
                    return $productUrl;
                }

                break;
        }

        return '';
    }

    /**
     * @return  string[]
     */
    public function getMessageAllowedTags()
    {
        return [
            'a', 'br', 'strong', 'div', 'p', 'span', 'blockquote', 'img', 'nav',
            'em', 'u', 'ol', 'ul', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'nav', 'header', 'footer', 'section', 'article', 'hr', 'pre', 'code',
            'table', 'tr', 'th', 'td', 'tbody', 'thead', 'tfoot',
        ];
    }

    /**
     * @param   string  $remoteId
     * @return  Order
     */
    protected function getOrderByMiraklRemoteId($remoteId)
    {
        $incrementId = preg_replace('/(.*)(\-\w+)/', '$1', $remoteId);

        $order = $this->orderFactory->create();
        $order->loadByIncrementId($incrementId);

        if ($order->getId()) {
            $this->coreRegistry->register('current_order', $order, true);
        }

        return $order;
    }

    /**
     * @param   string  $remoteId
     * @return  string
     */
    protected function getOrderIdByMiraklRemoteId($remoteId)
    {
        $order = $this->getOrderByMiraklRemoteId($remoteId);

        return $order->getId();
    }

    /**
     * @param   string  $offerId
     * @return  string
     */
    protected function getProductUrl($offerId)
    {
        $offer = $this->offerHelper->getOfferById($offerId);

        if (!$offer || !$offer->getId()) {
            return '';
        }

        try {
            $product = $this->productRepository->get($offer->getProductSku());
        } catch (NoSuchEntityException $e) {
            return '';
        }

        return $product->getProductUrl();
    }

    /**
     * @param   ThreadMessage   $message
     * @return  string
     */
    public function getSenderName(ThreadMessage $message)
    {
        $message = $message->toArray();

        if (isset($message['from']['organization_details']['display_name'])) {
            return $message['from']['organization_details']['display_name'];
        }

        return $message['from']['display_name'];
    }

    /**
     * @param   ThreadMessage   $message
     * @return  array
     */
    public function getRecipientNames(ThreadMessage $message)
    {
        $names = [];

        $message = $message->toArray();

        if (!empty($message['to'])) {
            foreach ($message['to'] as $recipient) {
                if (!empty($recipient['display_name'])) {
                    $names[] = $recipient['display_name'];
                }
            }
        }

        return $names;
    }
}
