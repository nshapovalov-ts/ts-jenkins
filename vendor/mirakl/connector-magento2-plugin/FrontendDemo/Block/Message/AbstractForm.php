<?php
namespace Mirakl\FrontendDemo\Block\Message;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\Api\Helper\Reason as ReasonApi;
use Mirakl\Core\Helper\Config as CoreConfig;
use Mirakl\FrontendDemo\Helper\Message as MessageHelper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\MMP\FrontOperator\Domain\Reason;

/**
 * @method bool  getAsModal()
 * @method $this setAsModal(bool $asModal)
 */
abstract class AbstractForm extends Template
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
     * @var ReasonApi
     */
    protected $reasonApi;

    /**
     * @var CoreConfig
     */
    protected $coreConfig;

    /**
     * @var MessageHelper
     */
    protected $messageHelper;

    /**
     * @var string
     */
    protected $_template = 'Mirakl_FrontendDemo::message/form.phtml';

    /**
     * @var string
     */
    protected $_formTitle = 'Send a Message';

    /**
     * @var string
     */
    protected $_reasonsLabel = 'Subject';

    /**
     * @var array
     */
    protected $_postMessage;

    /**
     * @param   Context         $context
     * @param   Registry        $coreRegistry
     * @param   FormKey         $formKey
     * @param   ReasonApi       $reasonApi
     * @param   CoreConfig      $coreConfig
     * @param   MessageHelper   $messageHelper
     * @param   array           $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FormKey $formKey,
        ReasonApi $reasonApi,
        CoreConfig $coreConfig,
        MessageHelper $messageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
        $this->formKey = $formKey;
        $this->reasonApi = $reasonApi;
        $this->coreConfig = $coreConfig;
        $this->messageHelper = $messageHelper;
    }

    /**
     * @return  string
     */
    abstract function getFormAction();

    /**
     * @param   string  $field
     * @return  string
     */
    public function getFormField($field)
    {
        return $field;
    }

    /**
     * @return  \Magento\Framework\Phrase
     */
    public function getFormTitle()
    {
        return __($this->_formTitle);
    }

    /**
     * @return  MiraklOrder
     */
    public function getMiraklOrder()
    {
        return $this->coreRegistry->registry('mirakl_order');
    }

    /**
     * @return  \Magento\Sales\Model\Order|null
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * Retrieve form data stored in session
     *
     * @param   string|null $field
     * @return  array|string
     */
    public function getPostMessage($field = null)
    {
        if ($this->_postMessage === null) {
            $this->_postMessage = $this->_session->getFormData();
        }

        if ($field) {
            return $this->_postMessage[$field] ?? '';
        }

        return $this->_postMessage;
    }

    /**
     * @return  \Magento\Framework\Phrase
     */
    public function getReasonsLabel()
    {
        return __($this->_reasonsLabel);
    }

    /**
     * @return  array
     */
    public function getReasons()
    {
        return [];
    }

    /**
     * @return  array
     */
    public function getRecipients()
    {
        $sellerName = $this->getSellerName();

        return [
            'SHOP'     => $sellerName,
            'OPERATOR' => __('Operator'),
            'BOTH'     => __('%1 and Operator', $sellerName),
        ];
    }

    /**
     * @return  string
     */
    public function getSellerName()
    {
        if ($thread = $this->getThread()) {
            /** @var ThreadParticipant $participant */
            foreach ($thread->getCurrentParticipants() as $participant) {
                if ($participant->getType() == 'SHOP') {
                    return $participant->getDisplayName();
                }
            }
        } elseif ($miraklOrder = $this->getMiraklOrder()) {
            return $miraklOrder->getShopName();
        }

        return (string) __('Seller');
    }

    /**
     * @return  ThreadDetails
     */
    public function getThread()
    {
        return $this->coreRegistry->registry('mirakl_thread');
    }

    /**
     * @param   Reason  $reason
     * @return  bool
     */
    public function isReasonSelected(Reason $reason)
    {
        return false;
    }

    /**
     * @return  bool
     */
    public function withFile()
    {
        return false;
    }
}
