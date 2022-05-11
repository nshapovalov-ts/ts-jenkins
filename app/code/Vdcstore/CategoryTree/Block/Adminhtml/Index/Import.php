<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Block\Adminhtml\Index;

class Import extends \Magento\Backend\Block\Widget
{

/**
     * @var string
     */
    protected $_template = 'Vdcstore_CategoryTree::index/import.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->setUseContainer(true);
    }
}

