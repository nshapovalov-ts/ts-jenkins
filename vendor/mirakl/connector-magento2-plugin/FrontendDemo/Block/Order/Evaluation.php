<?php
namespace Mirakl\FrontendDemo\Block\Order;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Session\Generic as GenericSession;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Mirakl\Api\Helper\Evaluation as EvaluationApi;
use Mirakl\Api\Helper\Order as OrderApi;
use Mirakl\FrontendDemo\Common\AssessmentTrait;
use Mirakl\FrontendDemo\Helper\Order as OrderHelper;
use Mirakl\MMP\Common\Domain\Evaluation as MiraklEvaluation;
use Mirakl\MMP\Common\Domain\Collection\Evaluation\AssessmentCollection;
use Mirakl\MMP\Common\Domain\Order\Document\OrderDocument;

class Evaluation extends Info
{
    use AssessmentTrait;

    /**
     * @var GenericSession
     */
    protected $session;

    /**
     * @var EvaluationApi
     */
    protected $evaluationApi;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var array
     */
    protected $documentTypes;

    /**
     * @var array
     */
    protected $_postEvaluation;

    /**
     * @var string
     */
    protected $_template = 'order/evaluation.phtml';

    /**
     * @param   TemplateContext $context
     * @param   Registry        $registry
     * @param   PaymentHelper   $paymentHelper
     * @param   AddressRenderer $addressRenderer
     * @param   GenericSession  $session
     * @param   FormKey         $formKey
     * @param   OrderApi        $orderApi
     * @param   EvaluationApi   $evaluationApi
     * @param   OrderHelper     $orderHelper
     * @param   array           $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        GenericSession $session,
        FormKey $formKey,
        OrderApi $orderApi,
        EvaluationApi $evaluationApi,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        $this->session = $session;
        $this->evaluationApi = $evaluationApi;
        $this->orderHelper = $orderHelper;
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $formKey, $orderApi, $data);
    }

    /**
     * @return  MiraklEvaluation
     * @throws  \Exception
     */
    public function getEvaluation()
    {
        return $this->orderHelper->getOrderEvaluation($this->getMiraklOrder());
    }

    /**
     * @param   OrderDocument   $doc
     * @return  string
     */
    public function getDownloadUrl(OrderDocument $doc)
    {
        return $this->getUrl('*/order/download', [
            'order_id'  => $this->getOrder()->getId(),
            'remote_id' => $this->getMiraklOrder()->getId(),
            'doc_id'    => $doc->getId(),
            'form_key'  => $this->formKey->getFormKey(),
        ]);
    }

    /**
     * Retrieve form data strored in session
     *
     * @param   string  $field
     * @return  array|string
     */
    public function getPostEvaluation($field = null)
    {
        if ($this->_postEvaluation === null) {
            $this->_postEvaluation = $this->session->getFormData(true);
        }

        if ($field) {
            return isset($this->_postEvaluation[$field]) ? $this->_postEvaluation[$field] : '';
        }

        return $this->_postEvaluation;
    }

    /**
     * @return  AssessmentCollection
     */
    public function getAssessments()
    {
        return $this->evaluationApi->getAssessments();
    }

    /**
     * @return  string
     */
    public function getFormAction()
    {
        return $this->getUrl('*/order/postEvaluation', [
            'order_id'  => $this->getOrder()->getId(),
            'remote_id' => $this->getMiraklOrder()->getId(),
        ]);
    }
}
