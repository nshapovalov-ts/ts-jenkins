<?php
namespace Mirakl\Process\Block\Adminhtml\Process;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Mirakl\Process\Model\Process;

class View extends Container
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_mode = 'view';
        $this->_controller = 'adminhtml_process';
        $this->_blockGroup = 'Mirakl_Process';

        parent::_construct();

        $this->removeButton('save');
        $this->removeButton('reset');

        $process = $this->getProcess();

        $this->buttonList->update('delete', 'class', 'primary');

        if ($process && $process->canCheckMiraklStatus()) {
            $confirmText = $this->escapeJsQuote(__('Are you sure?'));
            $this->addButton('check_mirakl_status', [
                'label'   => __('Check Mirakl Status'),
                'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getCheckMiraklStatusUrl()}')",
            ]);
        }

        if (!$process) {
            $this->removeButton('delete');
        } elseif ($process->canRun()) {
            $confirmText = $this->escapeJsQuote(__('Are you sure?'));
            $this->addButton('run', [
                'label'   => __('Run'),
                'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getRunUrl()}')",
            ]);
        } elseif ($process->canStop()) {
            $confirmText = $this->escapeJsQuote(__('Are you sure?'));
            $this->addButton('stop', [
                'label'   => __('Stop'),
                'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getStopUrl()}')",
            ]);
        }
    }

    /**
     * @return  Process
     */
    public function getProcess()
    {
        return $this->coreRegistry->registry('process');
    }

    /**
     * @return  string
     */
    public function getRunUrl()
    {
        return $this->getUrl('*/*/run', ['id' => $this->getProcess()->getId()]);
    }

    /**
     * @return  string
     */
    public function getStopUrl()
    {
        return $this->getUrl('*/*/stop', ['id' => $this->getProcess()->getId()]);
    }

    /**
     * @return  string
     */
    public function getCheckMiraklStatusUrl()
    {
        return $this->getUrl('*/*/checkMiraklStatus', ['id' => $this->getProcess()->getId()]);
    }
}
