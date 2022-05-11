<?php
namespace Mirakl\Core\Console\Command;

use Magento\Framework\Authorization\Policy\DefaultPolicy;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Interception\ObjectManager\Config\Compiled;

/**
 * @property \Magento\Framework\App\State                       $appState
 * @property \Magento\Framework\ObjectManagerInterface          $objectManager
 * @property \Magento\Framework\ObjectManager\ConfigInterface   $configManager
 */
trait CommandTrait
{
    /**
     * Initialize a specific ACL policy to allow products creation from CLI
     *
     * @return void
     */
    protected function initAuthorization()
    {
        if ($this->configManager instanceof Compiled) {
            $arguments = $this->configManager->getArguments('Magento\Framework\Authorization\Interceptor');
            if (isset($arguments['aclPolicy']['_i_']) && $arguments['aclPolicy']['_i_'] != DefaultPolicy::class) {
                $arguments['aclPolicy']['_i_'] = DefaultPolicy::class;
                $this->objectManager->configure(['arguments' => [
                    'Magento\Framework\Authorization\Interceptor' => $arguments
                ]]);
            }
        } else {
            $this->objectManager->configure(['preferences' => [PolicyInterface::class => DefaultPolicy::class]]);
        }
    }

    /**
     * Set area code in safe mode
     *
     * @param string $code
     */
    public function setAreaCode($code)
    {
        try {
            $area = $this->appState->getAreaCode();
        } catch (\Exception $e) {
            // Ignore potential exception
        } finally {
            if (empty($area)) {
                $this->appState->setAreaCode($code);
            }
        }
    }
}
