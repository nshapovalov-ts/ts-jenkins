<?php
namespace Mirakl\Process\Block\Adminhtml\Process\Grid\Column;

use Magento\Backend\Block\Template\Context;
use Mirakl\Core\Helper\Data as Helper;

class Duration extends AbstractColumn
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param   Context $context
     * @param   Helper  $helper
     * @param   array   $data
     */
    public function __construct(Context $context, Helper $helper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate($value, $row, $column, $isExport)
    {
        return $this->helper->formatDuration($row->getDuration());
    }
}
