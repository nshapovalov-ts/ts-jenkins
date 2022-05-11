<?php
namespace Mirakl\FrontendDemo\Block\Order;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Mirakl\Api\Helper\Order as OrderApi;
use Mirakl\Core\Model\Document\TypeFactory;
use Mirakl\Core\Model\ResourceModel\Document\Type\CollectionFactory as DocumentTypeCollectionFactory;
use Mirakl\MMP\Common\Domain\Collection\Order\Document\OrderDocumentCollection;
use Mirakl\MMP\Common\Domain\Order\Document\OrderDocument;

class Document extends Info
{
    /**
     * @var OrderApi
     */
    protected $orderApi;

    /**
     * @var TypeFactory
     */
    protected $documentTypeFactory;

    /**
     * @var DocumentTypeCollectionFactory
     */
    protected $documentTypeCollectionFactory;

    /**
     * @var array $documentTypes
     */
    protected $documentTypes;

    /**
     * @var string
     */
    protected $_template = 'order/document.phtml';

    /**
     * @param   TemplateContext                 $context
     * @param   Registry                        $registry
     * @param   PaymentHelper                   $paymentHelper
     * @param   AddressRenderer                 $addressRenderer
     * @param   FormKey                         $formKey
     * @param   OrderApi                        $orderApi
     * @param   TypeFactory                     $documentTypeFactory
     * @param   DocumentTypeCollectionFactory   $documentTypeCollectionFactory
     * @param   array                           $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        FormKey $formKey,
        OrderApi $orderApi,
        TypeFactory $documentTypeFactory,
        DocumentTypeCollectionFactory $documentTypeCollectionFactory,
        array $data = []
    ) {
        $this->documentTypeFactory = $documentTypeFactory;
        $this->documentTypeCollectionFactory = $documentTypeCollectionFactory;
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $formKey, $orderApi);
    }

    /**
     * Retrieves document list of current Mirakl order
     *
     * @return  OrderDocumentCollection
     */
    public function getDocuments()
    {
        return $this->orderApi->getOrderDocuments($this->getMiraklOrder());
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
     * Get the label for a document type code
     *
     * @param   string  $code
     * @return  string
     */
    public function getDocumentTypeLabel($code)
    {
        if ($this->documentTypes === null) {
            $collection = $this->documentTypeCollectionFactory->create();
            $this->documentTypes = [];
            foreach ($collection as $documentType) {
                $this->documentTypes[$documentType->getCode()] = $documentType->getLabel();
            }
        }

        return isset($this->documentTypes[$code]) ? $this->documentTypes[$code] : '';
    }
}
