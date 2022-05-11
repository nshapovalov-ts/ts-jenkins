<?php
namespace Retailplace\Whitelistemaildomain\Block\Adminhtml\Whitelistemaildomain;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Retailplace\Whitelistemaildomain\Model\whitelistemaildomainFactory
     */
    protected $_whitelistemaildomainFactory;

    /**
     * @var \Retailplace\Whitelistemaildomain\Model\Status
     */
    protected $_status;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Retailplace\Whitelistemaildomain\Model\whitelistemaildomainFactory $whitelistemaildomainFactory
     * @param \Retailplace\Whitelistemaildomain\Model\Status $status
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Retailplace\Whitelistemaildomain\Model\WhitelistemaildomainFactory $WhitelistemaildomainFactory,
        \Retailplace\Whitelistemaildomain\Model\Status $status,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_whitelistemaildomainFactory = $WhitelistemaildomainFactory;
        $this->_status = $status;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('postGrid');
        $this->setDefaultSort('domain_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
        $this->setVarNameFilter('post_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_whitelistemaildomainFactory->create()->getCollection();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'domain_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'domain_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );


		
				$this->addColumn(
					'domain',
					[
						'header' => __('Domain'),
						'index' => 'domain',
					]
				);
				

						$this->addColumn(
							'status',
							[
								'header' => __('Status'),
								'index' => 'status',
								'type' => 'options',
								'options' => \Retailplace\Whitelistemaildomain\Block\Adminhtml\Whitelistemaildomain\Grid::getOptionArray1()
							]
						);

						


		
        //$this->addColumn(
            //'edit',
            //[
                //'header' => __('Edit'),
                //'type' => 'action',
                //'getter' => 'getId',
                //'actions' => [
                    //[
                        //'caption' => __('Edit'),
                        //'url' => [
                            //'base' => '*/*/edit'
                        //],
                        //'field' => 'domain_id'
                    //]
                //],
                //'filter' => false,
                //'sortable' => false,
                //'index' => 'stores',
                //'header_css_class' => 'col-action',
                //'column_css_class' => 'col-action'
            //]
        //);
		

		
		   $this->addExportType($this->getUrl('whitelistemaildomain/*/exportCsv', ['_current' => true]),__('CSV'));
		   $this->addExportType($this->getUrl('whitelistemaildomain/*/exportExcel', ['_current' => true]),__('Excel XML'));

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

	
    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {

        $this->setMassactionIdField('domain_id');
        //$this->getMassactionBlock()->setTemplate('Retailplace_Whitelistemaildomain::whitelistemaildomain/grid/massaction_extended.phtml');
        $this->getMassactionBlock()->setFormFieldName('whitelistemaildomain');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('whitelistemaildomain/*/massDelete'),
                'confirm' => __('Are you sure?')
            ]
        );

        $statuses = $this->_status->getOptionArray();

        $this->getMassactionBlock()->addItem(
            'status',
            [
                'label' => __('Change status'),
                'url' => $this->getUrl('whitelistemaildomain/*/massStatus', ['_current' => true]),
                'additional' => [
                    'visibility' => [
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => __('Status'),
                        'values' => $statuses
                    ]
                ]
            ]
        );


        return $this;
    }
		

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('whitelistemaildomain/*/index', ['_current' => true]);
    }

    /**
     * @param \Retailplace\Whitelistemaildomain\Model\whitelistemaildomain|\Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
		
        return $this->getUrl(
            'whitelistemaildomain/*/edit',
            ['domain_id' => $row->getId()]
        );
		
    }

	
		static public function getOptionArray1()
		{
            $data_array=array(); 
			$data_array[0]='Disable';
			$data_array[1]='Enable';
            return($data_array);
		}
		static public function getValueArray1()
		{
            $data_array=array();
			foreach(\Retailplace\Whitelistemaildomain\Block\Adminhtml\Whitelistemaildomain\Grid::getOptionArray1() as $k=>$v){
               $data_array[]=array('value'=>$k,'label'=>$v);
			}
            return($data_array);

		}
		

}