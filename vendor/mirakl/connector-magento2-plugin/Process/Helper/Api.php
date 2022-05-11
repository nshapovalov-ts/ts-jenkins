<?php
namespace Mirakl\Process\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Helper\ClientHelper\AbstractClientHelper;
use Mirakl\Core\Domain\MiraklObject;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Core\Request\AbstractFileRequest;
use Mirakl\Core\Request\AbstractRequest;
use Mirakl\Process\Helper\Data as ProcessHelper;
use Mirakl\Process\Model\Process;
use Mirakl\Process\Model\ProcessFactory;

class Api extends AbstractHelper
{
    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var ProcessHelper
     */
    protected $processHelper;

    /**
     * @var ProcessFactory
     */
    protected $processFactory;

    /**
     * @var array
     */
    protected $synchroIdKeys;

    /**
     * @param   Context         $context
     * @param   CoreHelper      $coreHelper
     * @param   ProcessHelper   $processHelper
     * @param   ProcessFactory  $processFactory
     * @param   array           $synchroIdKeys
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        ProcessHelper $processHelper,
        ProcessFactory $processFactory,
        array $synchroIdKeys = []
    ) {
        parent::__construct($context);

        $this->coreHelper     = $coreHelper;
        $this->processHelper  = $processHelper;
        $this->processFactory = $processFactory;
        $this->synchroIdKeys  = $synchroIdKeys;
    }

    /**
     * @param   AbstractClientHelper    $apiClientHelper
     * @param   AbstractRequest         $request
     * @return  Process
     */
    private function createProcess(AbstractClientHelper $apiClientHelper, AbstractRequest $request)
    {
        /** @var Process $process */
        $process = $this->processFactory->create()
            ->setType(Process::TYPE_API)
            ->setName($request->getMethod() . ' ' . $request->getEndpoint())
            ->setMiraklStatus(Process::STATUS_PROCESSING) // This process must not be started by command cron:run
            ->setHelper(get_class($apiClientHelper));

        $extension = null;
        if ($request instanceof AbstractFileRequest) {
            $file = $request->getFile();
        } else {
            $file = \Mirakl\create_temp_file(json_encode($request->toArray(), JSON_PRETTY_PRINT));
            $extension = 'json';
        }

        if ($filepath = $this->processHelper->saveFile($file, $extension)) {
            $fileSize = $this->coreHelper->formatSize(filesize($filepath));
            $process->setFile($filepath);
            $process->output(__('File has been saved as "%1" (%2)', basename($filepath), $fileSize));
        }

        return $process;
    }

    /**
     * Sends specified request to Mirakl API and logs file contents + response in Mirakl Reports with process
     *
     * @param   AbstractClientHelper    $apiClientHelper
     * @param   AbstractRequest         $request
     * @return  mixed
     * @throws  \Exception
     */
    public function send(AbstractClientHelper $apiClientHelper, AbstractRequest $request)
    {
        $process = $this->createProcess($apiClientHelper, $request);

        return $this->sendRequest($apiClientHelper, $request, $process);
    }

    /**
     * @param   AbstractClientHelper    $apiClientHelper
     * @param   AbstractRequest         $request
     * @param   Process                 $process
     * @return  mixed
     * @throws  \Exception
     */
    private function sendRequest(AbstractClientHelper $apiClientHelper, AbstractRequest $request, Process $process)
    {
        $result = false;

        $process->start();
        $process->output(__('Sending request...'), true);

        try {
            // Get raw response
            $response = $apiClientHelper->send($request, true);
            $process->output(__('Response: [%1] %2', $response->getStatusCode(), (string) $response->getBody()));
            $result = $request->getResponseDecorator()->decorate($response);
            if ($result instanceof MiraklObject) {
                foreach ($this->synchroIdKeys as $key) {
                    if ($synchroId = $result->getData($key)) {
                        $process->setSynchroId($synchroId);
                        break;
                    }
                }
            }
            $process->stop();
        } catch (\Exception $e) {
            $process->fail($e->getMessage());
            throw $e;
        } finally {
            $apiClientHelper->getClient()->raw(false);
        }

        return $result;
    }
}
