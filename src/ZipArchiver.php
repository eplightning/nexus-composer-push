<?php


namespace Elendev\NexusComposerPush;

use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ZipArchiver
{

    /**
     * Archive the given directory in the $destination file
     *
     * @param string $source archive source
     * @param string $destination archive destination
     * @param string $subDirectory subdirectory in which the sources will be
     *               archived. If null, put at the root of the directory.
     * @param array $ignores
     * @param \Composer\IO\IOInterface|null $io
     * @param bool $reproducible If true, will attempt to create reproducible zip
     * 
     * @throws \Exception
     */
    public static function archiveDirectory(
        $source,
        $destination,
        $subDirectory = null,
        $ignores = [],
        $io = null,
        $reproducible = false
    ) {
        if (empty($io)) {
            $io = new NullIO();
        }

        if ($subDirectory) {
            $io->write('[ZIP Archive] Archive into the subdirectory ' . $subDirectory);
        } else {
            $io->write('[ZIP Archive] Archive into root directory');
        }

        $finder = new Finder();
        $fileSystem = new Filesystem();

        $finder->in($source)->ignoreVCS(true);

        foreach ($ignores as $ignore) {
            $finder->notPath($ignore);
        }

        if ($reproducible) {
            $finder->sortByName();
        }

        $archive = new \ZipArchive();

        $io->write(
            'Create ZIP file ' . $destination,
            true,
            IOInterface::VERY_VERBOSE
        );

        if (!$archive->open($destination, \ZipArchive::CREATE)) {
            $io->writeError(
                'Impossible to create ZIP file ' . $destination,
                true
            );
            throw new \Exception('Impossible to create the file ' . $destination);
        }

        foreach ($finder as $fileInfo) {
            if ($subDirectory) {
                $zipPath = $subDirectory . '/';
            } else {
                $zipPath = '';
            }

            $zipPath .= rtrim($fileSystem->makePathRelative(
                $fileInfo->getRealPath(),
                $source
            ), '/');

            if (!$fileInfo->isFile()) {
                continue;
            }

            $io->write(
                'Zip file ' . $fileInfo->getPath() . ' to ' . $zipPath,
                true,
                IOInterface::VERY_VERBOSE
            );

            if ($reproducible) {
                // TODO: Would be nice to do this without modyfing filesystem
                touch($fileInfo->getRealPath(), 1500000000);
            }

            $archive->addFile($fileInfo->getRealPath(), $zipPath);
        }

        $io->write('Zip archive ' . $destination . ' done');
        $archive->close();
    }
}
