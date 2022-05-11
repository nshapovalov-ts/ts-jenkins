<?php
namespace Retailplace\Landingpage\Controller;
use Retailplace\Landingpage\Model\LandingpageFactory;
class Router implements \Magento\Framework\App\RouterInterface {
     /**
     * @var \Magento\Framework\App\ActionFactory
     */
    protected $actionFactory;
    protected $_storeManager;
    protected $_landingpage;
    /**
     * Router constructor.
     *
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        LandingpageFactory $landingpage
    ) {
    	$this->_storeManager = $storeManager;        
        $this->actionFactory = $actionFactory;
        $this->_landingpage = $landingpage;
    }

    /**
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        $chunk = explode('/', $identifier, 2);
        $landingpage = $this->_landingpage->create();
        $messages = $landingpage->getCollection();
        $messages->addFieldToFilter('url_key', $chunk[0]);
        $landingpageid = null;
        foreach ($messages as $value) {
          $landingpageid = $value['landingpage_id'];
        }
        if($landingpageid ) {
            $request->setModuleName('landingpage')->setControllerName('index')->setActionName('view')->setParam('id', $landingpageid);  
        }else {
           return false;
        }
       
        return $this->actionFactory->create(
            'Magento\Framework\App\Action\Forward',
            ['request' => $request]
        );
    }
}
?>