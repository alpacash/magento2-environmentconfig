<?php declare(strict_types=1);

namespace Alpaca\EnvironmentConfig\Command;

use Alpaca\EnvironmentConfig\Api\File\FinderInterfaceFactory;
use Alpaca\EnvironmentConfig\Api\Processor\ImportProcessorInterfaceFactory;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\ObjectManager\ConfigLoader;
use Magento\Framework\App\State as AppState;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Semaio\ConfigImportExport\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCommand extends AbstractCommand
{
    /**
     * Command Name
     */
    const COMMAND_NAME = 'environment:config:process';

    /**
     * @var \Alpaca\EnvironmentConfig\Api\Processor\ImportProcessorInterfaceFactory
     */
    protected $importProcessorFactory;

    /**
     * @var array
     */
    protected $readers;

    /**
     * @var \Alpaca\EnvironmentConfig\Api\File\FinderInterfaceFactory
     */
    protected $finderFactory;

    /**
     * @param \Magento\Framework\Registry                                             $registry
     * @param \Magento\Framework\App\State                                            $appState
     * @param \Magento\Framework\App\ObjectManager\ConfigLoader                       $configLoader
     * @param \Magento\Framework\ObjectManagerInterface                               $objectManager
     * @param \Magento\Framework\App\Cache\Manager                                    $cacheManager
     * @param \Alpaca\EnvironmentConfig\Api\Processor\ImportProcessorInterfaceFactory $importProcessorFactory
     * @param \Alpaca\EnvironmentConfig\Api\File\FinderInterfaceFactory               $finderFactory
     * @param array                                                                   $readers
     * @param null                                                                    $name
     */
    public function __construct(
        Registry $registry,
        AppState $appState,
        ConfigLoader $configLoader,
        ObjectManagerInterface $objectManager,
        CacheManager $cacheManager,
        ImportProcessorInterfaceFactory $importProcessorFactory,
        FinderInterfaceFactory $finderFactory,
        array $readers = [],
        $name = null
    ) {
        $this->importProcessorFactory = $importProcessorFactory;
        $this->readers = $readers;
        $this->finderFactory = $finderFactory;

        parent::__construct(
            $registry,
            $appState,
            $configLoader,
            $objectManager,
            $cacheManager,
            $name
        );
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription('Import "core_config_data" settings for an environment');

        $this->addArgument(
            'folder',
            InputArgument::OPTIONAL,
            'Import folder name',
            '.environment'
        );

        $this->addOption(
            'format',
            'f',
            InputOption::VALUE_OPTIONAL,
            'Format: yaml, json (Default: yaml)',
            'yaml'
        );

        $this->addOption(
            'no-cache',
            null,
            InputOption::VALUE_NONE,
            'Do not clear cache after config data import.'
        );

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        parent::execute($input, $output);

        $this->writeSection('Start Import');

        // Check if there is a reader for the given file extension
        $format = $this->getFormat();
        if (!array_key_exists($format, $this->readers)) {
            throw new \InvalidArgumentException('Format "' . $format . '" is currently not supported."');
        }

        /** @var \Semaio\ConfigImportExport\Model\File\Reader\ReaderInterface $reader */
        $reader = $this->getObjectManager()->create($this->readers[$format]);
        if (!$reader || !is_object($reader)) {
            throw new \InvalidArgumentException(ucfirst($format) . ' file reader could not be instantiated."');
        }

        // Retrieve the arguments
        $folder = $input->getArgument('folder');

        // Configure the finder
        /** @var \Alpaca\EnvironmentConfig\Api\File\FinderInterface $finder */
        $finder = $this->finderFactory->create();
        $finder->setFolder($folder);
        $finder->setFormat($format);

        // Process the import
        /** @var \Alpaca\EnvironmentConfig\Api\Processor\ImportProcessorInterface $processor */
        $processor = $this->importProcessorFactory->create();
        $processor->setFormat($format);
        $processor->setReader($reader);
        $processor->setFinder($finder);
        $processor->setOutput($output);
        $processor->process();

        // Clear the cache after import
        if ($input->getOption('no-cache') === false) {
            $this->writeSection('Clear cache');

            $this->getCacheManager()->clean(['config', 'full_page']);

            $output->writeln(sprintf('<info>Cache cleared.</info>'));
        }

        return 1;
    }
}
