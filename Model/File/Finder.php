<?php declare(strict_types=1);

namespace Alpaca\EnvironmentConfig\Model\File;

use Alpaca\EnvironmentConfig\Api\File\FinderInterface;
use InvalidArgumentException;
use Symfony\Component\Finder\FinderFactory;

class Finder implements FinderInterface
{
    /**
     * @var string
     */
    protected $folder;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var string|int|string[]|int[]|null
     */
    protected $depth = null;

    /**
     * @var \Symfony\Component\Finder\FinderFactory
     */
    protected $finderFactory;

    /**
     * @param \Symfony\Component\Finder\FinderFactory $finderFactory
     */
    public function __construct(FinderFactory $finderFactory)
    {
        $this->finderFactory = $finderFactory;
    }

    /**
     * @param string $folder
     *
     * @return \Alpaca\EnvironmentConfig\Api\File\FinderInterface
     *
     * @throws \InvalidArgumentException
     */
    public function setFolder(string $folder): FinderInterface
    {
        if (!is_dir($folder) || !is_readable($folder)) {
            throw new InvalidArgumentException('Cannot access folder: ' . $folder);
        }

        $this->folder = rtrim($folder, '/');

        return $this;
    }

    /**
     * @param string $format The file extension to search for, on of: 'yaml', 'yml' or 'json'
     *
     * @return \Alpaca\EnvironmentConfig\Api\File\FinderInterface
     *
     * @throws \InvalidArgumentException
     */
    public function setFormat(string $format): FinderInterface
    {
        if (!in_array($format, ['yaml', 'yml', 'json'])) {
            throw new InvalidArgumentException(sprintf("Invalid format '%1'. Valid formats: yaml, yml or json",
                $format));
        }

        $this->format = $format;

        return $this;
    }

    /**
     * Adds tests for the directory depth.
     *
     * Usage:
     *
     *     $finder->setDepth('> 1') // the Finder will start matching at level 1.
     *     $finder->setDepth('< 3') // the Finder will descend at most 3 levels of directories below the starting point.
     *     $finder->setDepth(['>= 1', '< 3'])
     *
     * @param string|int|string[]|int[] $depth The depth level expression or an array of depth levels
     *
     * @return \Alpaca\EnvironmentConfig\Api\File\FinderInterface
     *
     * @see \Symfony\Component\Finder\Iterator\DepthRangeFilterIterator
     * @see \Symfony\Component\Finder\Comparator\NumberComparator
     */
    public function setDepth($depth): FinderInterface
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * @return array
     */
    public function find(): array
    {
        return $this->search($this->folder . DIRECTORY_SEPARATOR, $this->depth);
    }

    /**
     * @param string $path
     * @param null   $depth
     *
     * @return array
     */
    protected function search($path, $depth = null): array
    {
        // Remove trailing slash from path
        $path = rtrim($path, '/');

        /** @var \Symfony\Component\Finder\Finder $finder */
        $finder = $this->finderFactory->create();
        $finder->files()
            ->ignoreUnreadableDirs()
            ->name('*.' . $this->format)
            ->followLinks()
            ->in($path);

        if (null !== $depth) {
            $finder->depth($depth);
        }

        $files = [];
        foreach ($finder as $file) {
            /** @var $file \Symfony\Component\Finder\SplFileInfo */
            $files[] = $file->getPathname();
        }

        return $files;
    }
}
