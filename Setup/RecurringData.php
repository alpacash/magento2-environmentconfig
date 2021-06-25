<?php declare(strict_types=1);

namespace Alpaca\EnvironmentConfig\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RecurringData implements InstallDataInterface
{
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @param \Magento\Framework\App\DeploymentConfig     $deploymentConfig
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        DirectoryList $directoryList
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->directoryList = $directoryList;
    }

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $autoUpdateEnabled = (bool) $this->deploymentConfig->get('environment_config/auto_update/enabled', false);

        if (!$autoUpdateEnabled) {
            return;
        }

        $setup->startSetup();

        $command = sprintf(
            "php %s environment:config:process %s %s --no-cache",
            $this->directoryList->getRoot() . '/bin/magento',
            $this->deploymentConfig->get('environment_config/auto_update/directory', '.environment'),
            $this->getFormat()
        );

        echo PHP_EOL . "Running: " . str_replace($this->directoryList->getRoot() . "/", "", $command) . PHP_EOL;

        exec($command, $output);

        $setup->endSetup();
    }

    /**
     * @return string|null
     */
    protected function getFormat(): ?string
    {
        $value = $this->deploymentConfig->get('environment_config/auto_update/format', 'yaml');

        return empty($value) ? "--format {$value}" : null;
    }
}
