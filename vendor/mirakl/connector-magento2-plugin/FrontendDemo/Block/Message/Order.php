<?php
namespace Mirakl\FrontendDemo\Block\Message;

use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\Api\Helper\Message as MessageApi;

class Order extends Template
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
    protected $tabTitle = '';

    /**
     * @var array
     */
    protected $tabChildren = [];

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
    public function _construct()
    {
        $threads = $this->getThreads();
        $nbThreads = $threads->getCollection()->count();

        if ($nbThreads == 0) {
            $this->tabChildren[] = $this->addBlock('marketplace.message.form.order', FormOrder::class);
        } else if ($nbThreads == 1) {
            $thread = $this->getThread($threads->getCollection()->first())->toArray();
            $this->tabTitle = $thread['topic']['value'] ?? '';
            $this->tabChildren[] = $this->addBlock('marketplace.message.form.new', FormNew::class)->setAsModal(true);
            $this->tabChildren[] = $this->addBlock('marketplace.message.view', View::class);
            $this->tabChildren[] = $this->addBlock('marketplace.message.form.reply', FormReply::class);
        } else {
            $this->tabChildren[] = $this->addBlock('marketplace.message.form.new', FormNew::class)->setAsModal(true);
            $this->tabChildren[] = $this->addBlock('marketplace.message.index', Index::class);
        }
    }

    /**
     * @return  array
     */
    public function getTabChildren()
    {
        return $this->tabChildren;
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

        $threads = $this->messageApi->getThreads(
            $customerId,
            'MMP_ORDER',
            $this->getMiraklOrder()->getId()
        );

        $this->coreRegistry->register('mirakl_threads', $threads);

        return $threads;
    }

    /**
     * @param   Thread  $thread
     * @return  ThreadDetails
     */
    public function getThread(Thread $thread)
    {
        $threadDetails = $this->messageApi->getThreadDetails(
            $thread->getId(),
            $this->customerSession->getCustomerId()
        );

        $this->coreRegistry->register('mirakl_thread', $threadDetails);

        return $threadDetails;
    }

    /**
     * @return  string
     */
    public function getTabTitle()
    {
        return $this->tabTitle;
    }

    /**
     * @return  MiraklOrder
     */
    public function getMiraklOrder()
    {
        return $this->coreRegistry->registry('mirakl_order');
    }

    /**
     * @param   string  $blockName
     * @param   string  $blockClass
     * @return  BlockInterface
     */
    public function addBlock($blockName, $blockClass)
    {
        $block = $this->getLayout()->getBlock($blockName);
        if (!$block) {
            $block = $this->getLayout()->addBlock($blockClass, $blockName, $this->_nameInLayout);
        }

        return $block;
    }
}
