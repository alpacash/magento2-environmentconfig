<?php declare(strict_types=1);

namespace Alpaca\EnvironmentConfig\Model\Processor;

use Alpaca\EnvironmentConfig\Api\File\FinderInterface;
use Alpaca\EnvironmentConfig\Api\Processor\ImportProcessorInterface;
use InvalidArgumentException;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Semaio\ConfigImportExport\Model\Converter\ScopeConverterInterface;
use Semaio\ConfigImportExport\Model\File\Reader\ReaderInterface;
use Semaio\ConfigImportExport\Model\Processor\AbstractProcessor;
use Semaio\ConfigImportExport\Model\Validator\ScopeValidatorInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class ImportProcessor extends AbstractProcessor implements ImportProcessorInterface
{
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    /**
     * @var \Semaio\ConfigImportExport\Model\Validator\ScopeValidatorInterface
     */
    protected $scopeValidator;

    /**
     * @var \Semaio\ConfigImportExport\Model\Converter\ScopeConverterInterface
     */
    protected $scopeConverter;

    /**
     * @var \Semaio\ConfigImportExport\Model\File\Reader\ReaderInterface
     */
    protected $reader;

    /**
     * @var \Alpaca\EnvironmentConfig\Api\File\FinderInterface
     */
    protected $finder;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param \Magento\Framework\App\Config\Storage\WriterInterface              $configWriter
     * @param \Semaio\ConfigImportExport\Model\Validator\ScopeValidatorInterface $scopeValidator
     * @param \Semaio\ConfigImportExport\Model\Converter\ScopeConverterInterface $scopeConverter
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        WriterInterface $configWriter,
        ScopeValidatorInterface $scopeValidator,
        ScopeConverterInterface $scopeConverter,
        EncryptorInterface $encryptor
    ) {
        $this->configWriter = $configWriter;
        $this->scopeValidator = $scopeValidator;
        $this->scopeConverter = $scopeConverter;
        $this->encryptor = $encryptor;
    }

    /**
     * @param \Semaio\ConfigImportExport\Model\File\Reader\ReaderInterface $reader
     */
    public function setReader(ReaderInterface $reader): ImportProcessorInterface
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * @param \Alpaca\EnvironmentConfig\Api\File\FinderInterface $finder
     */
    public function setFinder(FinderInterface $finder): ImportProcessorInterface
    {
        $this->finder = $finder;

        return $this;
    }

    /**
     * Process the import
     */
    public function process()
    {
        $files = $this->finder->find();

        if (empty($files)) {
            throw new InvalidArgumentException('No files found for format: *.' . $this->getFormat());
        }

        foreach ($files as $file) {
            $valuesSet = 0;

            $configurations = $this->getConfigurationsFromFile($file);

            foreach ($configurations as $configPath => $configValues) {
                $scopeConfigValues = $this->transformConfigToScopeConfig($configPath, $configValues);
                foreach ($scopeConfigValues as $scopeConfigValue) {

                    $this->configWriter->save(
                        $configPath,
                        $scopeConfigValue['value'],
                        $scopeConfigValue['scope'],
                        $this->scopeConverter->convert($scopeConfigValue['scope_id'], $scopeConfigValue['scope'])
                    );

                    $this->getOutput()->writeln(
                        sprintf('<comment>%s => %s</comment>', $configPath, $scopeConfigValue['value'])
                    );

                    $valuesSet++;
                }
            }

            $this->getOutput()->writeln(
                sprintf('<info>Processed: %s with %s value(s).</info>', $file, $valuesSet)
            );
        }
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected function getConfigurationsFromFile(string $file): array
    {
        $configurations = $this->reader->parse($file);

        if (!is_array($configurations)) {
            $this->getOutput()->writeln(
                sprintf("<error>Skipped: '%s' (not an array: %s).</error>", $file, var_export($configurations, true))
            );
            $configurations = [];
        }

        return $configurations;
    }

    /**
     * @param string $path
     * @param array  $config
     *
     * @return array
     */
    protected function transformConfigToScopeConfig(string $path, array $config): array
    {
        $return = [];
        foreach ($config as $scope => $scopeIdValue) {
            if (!$scopeIdValue) {
                continue;
            }

            foreach ($scopeIdValue as $scopeId => $value) {
                $value = (string)$value;
                if (!$this->scopeValidator->validate($scope, $scopeId)) {
                    $errorMsg = sprintf(
                        '<error>ERROR: Invalid scopeId "%s" for scope "%s" (%s => %s)</error>',
                        $scopeId,
                        $scope,
                        $path,
                        $value
                    );
                    $this->getOutput()->writeln($errorMsg);
                    continue;
                }

                $return[] = [
                    'value' => $this->convertValue($value),
                    'scope' => $scope,
                    'scope_id' => $scopeId,
                ];
            }
        }

        return $return;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function convertValue(string $value): string
    {
        preg_match("/^\!(?<type>\w+?)\s(?<value>.+)/", $value, $matches);

        if (!empty($matches) && !empty($matches['type']) && !empty($matches['value'])) {
            switch ($matches['type']) {
                case 'encrypted':
                    $value = $this->encryptor->encrypt($matches['value']);
                    break;
                default:
                    // Keep existing value
                    break;
            }
        }

        return $value;
    }
}
