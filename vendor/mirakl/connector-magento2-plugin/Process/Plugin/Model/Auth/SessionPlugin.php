<?php
namespace Mirakl\Process\Plugin\Model\Auth;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;

class SessionPlugin
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @param   Http    $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    /**
     * @return  bool
     */
    private function isMiraklProcessAsync()
    {
        return $this->request->getFullActionName() === 'mirakl_process_async';
    }

    /**
     * @param   Session     $session
     * @param   \Closure    $proceed
     * @return  void
     */
    public function aroundProlong(Session $session, $proceed)
    {
        if (!$this->isMiraklProcessAsync()) {
            $proceed();
        }
    }
}
