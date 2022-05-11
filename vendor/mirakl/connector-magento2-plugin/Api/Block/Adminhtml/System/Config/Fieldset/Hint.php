<?php
namespace Mirakl\Api\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Mirakl\Api\Helper\ClientHelper\MMP as ApiHelper;
use Mirakl\Core\Helper\Data as CoreHelper;

/**
 * @method  string  getError()
 * @method  $this   setError(string $error)
 */
class Hint extends Template implements RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'Mirakl_Api::system/config/fieldset/hint.phtml';

    /**
     * @var CoreHelper
     */
    private $coreHelper;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @param   Context     $context
     * @param   CoreHelper  $coreHelper
     * @param   ApiHelper   $apiHelper
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        ApiHelper $apiHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreHelper = $coreHelper;
        $this->apiHelper = $apiHelper;
    }

    /**
     * @return  string
     */
    public function getConnectorVersion()
    {
        return $this->coreHelper->getVersion();
    }

    /**
     * @return  string
     */
    public function getVersionSDK()
    {
        return $this->coreHelper->getVersionSDK();
    }

    /**
     * @return  string|null
     */
    public function getMiraklVersion()
    {
        $version = null;
        try {
            $version = $this->apiHelper->getVersion();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
        }

        return $version;
    }

    /**
     * Render fieldset html
     *
     * @param   AbstractElement $element
     * @return  string
     */
    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }
}
