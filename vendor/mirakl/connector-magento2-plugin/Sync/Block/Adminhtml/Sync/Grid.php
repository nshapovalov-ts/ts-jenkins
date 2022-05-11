<?php
namespace Mirakl\Sync\Block\Adminhtml\Sync;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Config\Model\Config\Structure as ConfigStructure;
use Magento\Config\Model\Config\Structure\Element\Section as ConfigSection;
use Mirakl\Connector\Block\Adminhtml\System\Config\Button\ButtonsRendererInterface;
use Mirakl\Core\Helper\Config;
use Mirakl\Sync\Model\Sync\Entry;
use Mirakl\Sync\Model\Sync\EntryFactory;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var ConfigStructure
     */
    protected $configStructure;

    /**
     * @var EntryFactory
     */
    protected $entryFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Context         $context
     * @param   BackendHelper   $backendHelper
     * @param   ConfigStructure $configStructure
     * @param   EntryFactory    $entryFactory
     * @param   Config          $config
     * @param   array           $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        ConfigStructure $configStructure,
        EntryFactory $entryFactory,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->configStructure = $configStructure;
        $this->entryFactory = $entryFactory;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $this->_collection = $this->entryFactory->create()->getCollection();

        /** @var \Magento\Config\Model\Config\Structure\Element\Group $group */
        foreach ($this->getConfigSection()->getChildren() as $group) {
            if (!$group->hasChildren()) {
                continue;
            }

            /** @var Entry $entry */
            $entry = $this->entryFactory->create();
            $entry->setName($group->getLabel());
            $entry->setDescription($group->getComment());

            /** @var \Magento\Config\Model\Config\Structure\Element\Field $field */
            foreach ($group->getChildren() as $field) {
                if (0 === strpos($field->getId(), 'enable_')) {
                    $entry->setStatus($this->config->getValue($field->getPath()));
                } elseif (0 === strpos($field->getId(), 'last_sync_')) {
                    $entry->setLastSyncDate($this->config->getRawValue($field->getPath()));
                } elseif (0 === strpos($field->getId(), 'button_') && $field->getFrontendModel()) {
                    /** @var ButtonsRendererInterface $renderer */
                    $renderer = $this->_layout->getBlockSingleton($field->getFrontendModel());
                    if ($renderer instanceof ButtonsRendererInterface) {
                        $entry->setButtons($renderer->getButtonsHtml());
                        if ($renderer->getDisabled()) {
                            $entry->setStatus(false);
                        }
                    }
                }
            }

            $this->_collection->addItem($entry);
        }

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn('name',
            [
                'header'   => __('Name'),
                'index'    => 'name',
                'filter'   => false,
                'sortable' => false,
            ]
        );
        $this->addColumn('description',
            [
                'header'   => __('Description'),
                'index'    => 'description',
                'filter'   => false,
                'sortable' => false,
            ]
        );
        $this->addColumn('last_sync_date',
            [
                'type'     => 'datetime',
                'header'   => __('Last Sync Date'),
                'index'    => 'last_sync_date',
                'filter'   => false,
                'sortable' => false,
            ]
        );
        $this->addColumn('status',
            [
                'header'         => __('Status'),
                'index'          => 'status',
                'frame_callback' => [$this, 'decorateStatus'],
                'filter'         => false,
                'sortable'       => false,
            ]
        );
        $this->addColumn('api',
            [
                'header'         => __('API'),
                'index'          => 'status',
                'frame_callback' => [$this, 'decorateApi'],
                'filter'         => false,
                'sortable'       => false,
                'align'          => 'center',
            ]
        );
        $this->addColumn('buttons',
            [
                'header'         => __('Action'),
                'index'          => 'buttons',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => [$this, 'decorateButtons'],
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param   mixed   $value
     * @param   Entry   $entry
     * @return  string
     */
    public function decorateApi($value, Entry $entry)
    {
        preg_match('/.*\((.+)\)$/i', $entry->getName(), $matches);

        return isset($matches[1]) ? $matches[1] : 'SYSTEM';
    }

    /**
     * @param   mixed   $value
     * @param   Entry   $entry
     * @return  string
     */
    public function decorateStatus($value, Entry $entry)
    {
        switch ($entry->getStatus()) {
            case '0':
                $class = 'grid-severity-critical';
                $label = __('Disabled');
                break;
            case '1':
                $class = 'grid-severity-notice';
                $label = __('Enabled');
                break;
            default:
                $class = 'grid-severity-notice';
                $label = __('Enabled');
        }

        return '<span class="' . $class . '"><span>' . $label . '</span></span>';
    }

    /**
     * @param   mixed   $value
     * @param   Entry   $entry
     * @return  string
     */
    public function decorateButtons($value, Entry $entry)
    {
        return $entry->getButtons(); // avoid html escaping
    }

    /**
     * @return  ConfigSection
     */
    protected function getConfigSection()
    {
        /** @var ConfigSection $section */
        $section = $this->configStructure->getElement('mirakl_sync');

        return $section;
    }
}
