<?php
namespace Mirakl\Process\Plugin\Api;

use Mirakl\Api\Helper\ClientHelper\AbstractClientHelper;
use Mirakl\Core\Request\AbstractRequest;
use Mirakl\Process\Helper\Api as ProcessApiHelper;
use Mirakl\Core\Request\AbstractFileRequest;

class ClientHelperPlugin
{
    /**
     * @var ProcessApiHelper
     */
    protected $processApiHelper;

    /**
     * @param   ProcessApiHelper    $processApiHelper
     */
    public function __construct(ProcessApiHelper $processApiHelper)
    {
        $this->processApiHelper = $processApiHelper;
    }

    /**
     * @param   AbstractClientHelper    $subject
     * @param   \Closure                $proceed
     * @param   AbstractFileRequest     $request
     * @return  mixed
     * @throws  \Exception
     */
    public function aroundUpload($subject, \Closure $proceed, AbstractFileRequest $request)
    {
        return $this->processApiHelper->send($subject, $request);
    }

    /**
     * @param   AbstractClientHelper    $subject
     * @param   \Closure                $proceed
     * @param   AbstractRequest         $request
     * @return  mixed
     * @throws  \Exception
     */
    public function aroundPost($subject, \Closure $proceed, AbstractRequest $request)
    {
        return $this->processApiHelper->send($subject, $request);
    }
}
