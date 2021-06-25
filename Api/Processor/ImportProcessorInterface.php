<?php declare(strict_types=1);

namespace Alpaca\EnvironmentConfig\Api\Processor;

use Alpaca\EnvironmentConfig\Api\File\FinderInterface;
use Semaio\ConfigImportExport\Model\File\Reader\ReaderInterface;
use Semaio\ConfigImportExport\Model\Processor\AbstractProcessorInterface;

interface ImportProcessorInterface extends AbstractProcessorInterface
{
    /**
     * @param \Semaio\ConfigImportExport\Model\File\Reader\ReaderInterface $reader
     *
     * @return mixed
     */
    public function setReader(ReaderInterface $reader): ImportProcessorInterface;

    /**
     * @param \Alpaca\EnvironmentConfig\Api\File\FinderInterface $finder
     *
     * @return mixed
     */
    public function setFinder(FinderInterface $finder): ImportProcessorInterface;
}
