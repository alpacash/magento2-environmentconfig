<?php declare(strict_types=1);

namespace Alpaca\EnvironmentConfig\Api\File;

interface FinderInterface
{
    /**
     * @param string $folder
     *
     * @return \Alpaca\EnvironmentConfig\Api\File\FinderInterface
     */
    public function setFolder(string $folder): FinderInterface;

    /**
     * @param string $format The file extension to search for, on of: 'yaml', 'yml' or 'json'
     *
     * @return \Alpaca\EnvironmentConfig\Api\File\FinderInterface
     */
    public function setFormat(string $format): FinderInterface;

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
    public function setDepth($depth): FinderInterface;

    /**
     * @return array
     */
    public function find(): array;
}
