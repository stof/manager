<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Package;

use Puli\RepositoryManager\Api\Environment\ProjectEnvironment;
use Puli\RepositoryManager\Api\FileNotFoundException;
use Puli\RepositoryManager\Api\InvalidConfigException;
use Puli\RepositoryManager\Api\NoDirectoryException;
use Puli\RepositoryManager\Api\Package\InstallInfo;
use Puli\RepositoryManager\Api\Package\NameConflictException;
use Puli\RepositoryManager\Api\Package\Package;
use Puli\RepositoryManager\Api\Package\PackageCollection;
use Puli\RepositoryManager\Api\Package\PackageFile;
use Puli\RepositoryManager\Api\Package\PackageManager;
use Puli\RepositoryManager\Api\Package\PackageState;
use Puli\RepositoryManager\Api\Package\RootPackage;
use Puli\RepositoryManager\Api\Package\RootPackageFile;
use Puli\RepositoryManager\Api\Package\UnsupportedVersionException;
use Puli\RepositoryManager\Assert\Assert;
use Webmozart\PathUtil\Path;

/**
 * Manages the package repository of a Puli project.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageManagerImpl implements PackageManager
{
    /**
     * @var ProjectEnvironment
     */
    private $environment;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var RootPackageFile
     */
    private $rootPackageFile;

    /**
     * @var PackageFileStorage
     */
    private $packageFileStorage;

    /**
     * @var PackageCollection
     */
    private $packages;

    /**
     * Loads the package repository for a given project.
     *
     * @param ProjectEnvironment $environment        The project environment.
     * @param PackageFileStorage $packageFileStorage The package file storage.
     *
     * @throws FileNotFoundException If the install path of a package not exist.
     * @throws NoDirectoryException If the install path of a package points to a file.
     * @throws InvalidConfigException If a configuration file contains invalid configuration.
     * @throws NameConflictException If a package has the same name as another loaded package.
     */
    public function __construct(
        ProjectEnvironment $environment,
        PackageFileStorage $packageFileStorage
    )
    {
        $this->environment = $environment;
        $this->rootDir = $environment->getRootDirectory();
        $this->rootPackageFile = $environment->getRootPackageFile();
        $this->packageFileStorage = $packageFileStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function installPackage($installPath, $name = null, $installerName = InstallInfo::DEFAULT_INSTALLER_NAME)
    {
        Assert::nullOrPackageName($name);

        $this->assertPackagesLoaded();

        $installPath = Path::makeAbsolute($installPath, $this->rootDir);

        if ($this->isPackageInstalled($installPath)) {
            return;
        }

        if (null === $name) {
            // Read the name from the package file
            $name = $this->loadPackageFile($installPath)->getPackageName();
        }

        if (null === $name) {
            throw new InvalidConfigException(sprintf(
                'Could not find a name for the package at %s. The name should '.
                'either be passed to the installer or be set in the "name" '.
                'property of %s.',
                $installPath,
                $installPath.'/puli.json'
            ));
        }

        if ($this->packages->contains($name)) {
            $conflictingPackage = $this->packages->get($name);

            throw new NameConflictException(sprintf(
                'Cannot load package "%s" at %s: The package at %s has the '.
                'same name.',
                $name,
                $installPath,
                $conflictingPackage->getInstallPath()
            ));
        }

        $relInstallPath = Path::makeRelative($installPath, $this->rootDir);
        $installInfo = new InstallInfo($name, $relInstallPath);
        $installInfo->setInstallerName($installerName);

        // Don't catch exceptions
        $package = $this->loadPackage($installInfo, false);

        // OK, now add it
        $this->rootPackageFile->addInstallInfo($installInfo);
        $this->packages->add($package);

        $this->packageFileStorage->saveRootPackageFile($this->rootPackageFile);
    }

    /**
     * {@inheritdoc}
     */
    public function isPackageInstalled($installPath)
    {
        $this->assertPackagesLoaded();

        $installPath = Path::makeAbsolute($installPath, $this->rootDir);

        foreach ($this->packages as $package) {
            if ($installPath === $package->getInstallPath()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removePackage($name)
    {
        $this->assertPackagesLoaded();

        if (!$this->packages->contains($name)) {
            return;
        }

        $this->packages->remove($name);

        if ($this->rootPackageFile->hasInstallInfo($name)) {
            $this->rootPackageFile->removeInstallInfo($name);
            $this->packageFileStorage->saveRootPackageFile($this->rootPackageFile);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasPackage($name)
    {
        $this->assertPackagesLoaded();

        return $this->packages->contains($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getPackage($name)
    {
        $this->assertPackagesLoaded();

        return $this->packages->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getRootPackage()
    {
        $this->assertPackagesLoaded();

        return $this->packages->getRootPackage();
    }

    /**
     * {@inheritdoc}
     */
    public function getPackages($state = null)
    {
        $this->assertPackagesLoaded();

        $packages = new PackageCollection();

        foreach ($this->packages as $package) {
            if (null === $state || $state === $package->getState()) {
                $packages[] = $package;
            }
        }

        return $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackagesByInstaller($installer, $state = PackageState::ENABLED)
    {
        $this->assertPackagesLoaded();

        $packages = new PackageCollection();

        foreach ($this->packages as $package) {
            $installInfo = $package->getInstallInfo();

            // The root package has no install info
            if ($installInfo && $installer === $installInfo->getInstallerName() && $state === $package->getState()) {
                $packages->add($package);
            }
        }

        return $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Loads all packages referenced by the install file.
     *
     * @throws FileNotFoundException If the install path of a package not exist.
     * @throws NoDirectoryException If the install path of a package points to a
     *                              file.
     * @throws InvalidConfigException If a package is not configured correctly.
     * @throws NameConflictException If a package has the same name as another
     *                               loaded package.
     */
    private function loadPackages()
    {
        $this->packages = new PackageCollection();
        $this->packages->add(new RootPackage($this->rootPackageFile, $this->rootDir));

        foreach ($this->rootPackageFile->getInstallInfos() as $installInfo) {
            // Catch and log exceptions so that single packages cannot break
            // the whole repository
            $this->packages->add($this->loadPackage($installInfo));
        }
    }

    /**
     * Loads a package for the given install info.
     *
     * @param InstallInfo $installInfo     The install info.
     * @param bool        $catchExceptions Whether to catch exceptions and store
     *                                     them with the package for later
     *                                     access.
     *
     * @return Package The package.
     *
     * @throws FileNotFoundException If the install path does not exist.
     * @throws NoDirectoryException If the install path points to a file.
     * @throws NameConflictException If the package has the same name as another
     *                               loaded package.
     */
    private function loadPackage(InstallInfo $installInfo, $catchExceptions = true)
    {
        $installPath = Path::makeAbsolute($installInfo->getInstallPath(), $this->rootDir);
        $packageFile = null;
        $loadError = null;

        try {
            $packageFile = $this->loadPackageFile($installPath, $catchExceptions);
        } catch (InvalidConfigException $loadError) {
        } catch (UnsupportedVersionException $loadError) {
        } catch (FileNotFoundException $loadError) {
        } catch (NoDirectoryException $loadError) {
        }

        if ($loadError && !$catchExceptions) {
            throw $loadError;
        }

        return new Package($packageFile, $installPath, $installInfo, $loadError);
    }

    /**
     * Loads the package file for the package at the given install path.
     *
     * @param string $installPath The absolute install path of the package
     *
     * @return PackageFile The loaded package file.
     */
    private function loadPackageFile($installPath)
    {
        if (!file_exists($installPath)) {
            throw FileNotFoundException::forPath($installPath);
        }

        if (!is_dir($installPath)) {
            throw new NoDirectoryException(sprintf(
                'The path %s is a file. Expected a directory.',
                $installPath
            ));
        }

        return $this->packageFileStorage->loadPackageFile($installPath.'/puli.json');
    }

    private function assertPackagesLoaded()
    {
        if (!$this->packages) {
            $this->loadPackages();
        }
    }
}