<?php
namespace Mirakl\Api\Block\Adminhtml\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Mirakl\Api\Model\Log\LoggerManager;
use Mirakl\Core\Helper\Data as CoreHelper;

class Log extends Field
{
    /**
     * @var LoggerManager
     */
    protected $loggerManager;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @param   Template\Context    $context
     * @param   LoggerManager       $loggerManager
     * @param   CoreHelper          $coreHelper
     * @param   array               $data
     */
    public function __construct(
        Template\Context $context,
        LoggerManager $loggerManager,
        CoreHelper $coreHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loggerManager = $loggerManager;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label'   => 'Download',
            'title'   => 'Download log file',
            'url'     => 'mirakl_api/log/download',
            'class'   => 'scalable',
        ],
        [
            'label'   => 'Clear',
            'title'   => 'Clear log file',
            'url'     => 'mirakl_api/log/clear',
            'confirm' => 'Are you sure? This will erase all API log contents.',
            'class'   => 'scalable primary',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '';

        $logFileSize = $this->loggerManager->getLogFileSize();

        foreach ($this->buttonsConfig as $buttonConfig) {
            /** @var Button $button */
            $button = $this->getLayout()->createBlock(Button::class);
            $button->setLabel(__($buttonConfig['label']))
                ->setClass($buttonConfig['class']);

            if (isset($buttonConfig['title'])) {
                $button->setTitle(__($buttonConfig['title']));
            }

            if (isset($buttonConfig['url'])) {
                $url = $this->getUrl($buttonConfig['url'], ['_current' => true]);
                $button->setOnClick("setLocation('$url')");

                if (isset($buttonConfig['confirm'])) {
                    $confirm = __($buttonConfig['confirm']);
                    $button->setOnClick("confirmSetLocation('$confirm', '$url')");
                }
            }

            if (isset($buttonConfig['onclick'])) {
                $button->setOnClick($buttonConfig['onclick']);
            }

            if (!$logFileSize) {
                $button->setDisabled(true);
            }

            $html .= $button->toHtml();
        }

        if (!$logFileSize) {
            $html .= __('(log file is empty)');
        } else {
            $html .= '(' . $this->coreHelper->formatSize($logFileSize) . ')';
        }

        return $html;
    }
}
