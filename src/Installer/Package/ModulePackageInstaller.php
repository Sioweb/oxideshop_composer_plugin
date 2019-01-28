<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\ComposerPlugin\Installer\Package;

use Composer\IO\IOInterface;
use OxidEsales\EshopCommunity\Internal\Application\ContainerFactory;
use Composer\Package\PackageInterface;
use OxidEsales\EshopCommunity\Internal\Common\Exception\DirectoryExistentException;
use OxidEsales\EshopCommunity\Internal\Module\Install\ModuleFilesInstallerInterface;
use OxidEsales\EshopCommunity\Internal\Module\Install\ModuleFilesInstaller;

/**
 * @inheritdoc
 */
class ModulePackageInstaller
{
    /** @var IOInterface $io */
    private $io;

    /** @var ModuleFilesInstaller $modulyCopyService */
    private $moduleCopyService;

    /** @var PackageInterface */
    private $package;

    /** @var string */
    private $rootDirectory;

    /**
     * AbstractInstaller constructor.
     *
     * @param IOInterface      $io
     * @param string           $rootDirectory
     * @param PackageInterface $package
     */
    public function __construct(IOInterface $io, $rootDirectory, PackageInterface $package)
    {
        $this->io = $io;
        $container = ContainerFactory::getInstance()->getContainer();
        $this->moduleCopyService = $container->get(ModuleFilesInstallerInterface::class);
        $this->package = $package;
        $this->rootDirectory = $rootDirectory;
    }

    /**
     * @return bool
     *
     * @deprecated since v.3.0.0 (2019-01-31); install() and update() can handle the isInstalle case with an exception.
     */
    public function isInstalled()
    {
        return file_exists($this->formTargetPath());
    }

    /**
     * Copies module files to shop directory.
     *
     * @param string $packagePath
     */
    public function install($packagePath)
    {
        $this->io->write("Installing module {$packagePath} package.");
        try {
            $this->moduleCopyService->copy($packagePath);
        } catch (DirectoryExistentException $exception) {
            $directoryAlreadyExistent = $exception->getDirectoryAlreadyExistent();
            $this->io->write("The directory $directoryAlreadyExistent already exists. Aborting.");
        }
    }

    /**
     * Update module files.
     *
     * @param string $packagePath
     */
    public function update($packagePath)
    {
        $this->io->write("Installing module {$packagePath} package.");
        try {
            $this->moduleCopyService->copy($packagePath);
        } catch (DirectoryExistentException $exception) {
            $directoryAlreadyExistent = $exception->getDirectoryAlreadyExistent();
            $question = 'All files in the following directories will be overwritten:' . PHP_EOL .
                        '- ' . $directoryAlreadyExistent . PHP_EOL .
                        'Do you want to overwrite them? (y/N) ';
            if ($this->askQuestion($question)) {
                $this->moduleCopyService->forceCopy($packagePath);
            }
        }
    }

    /**
     * Returns true if the human answer to the given question was answered with a positive value (Yes/yes/Y/y).
     *
     * @param string $messageToAsk
     * @return bool
     */
    protected function askQuestion(string $messageToAsk) : bool
    {
        $userInput = $this->io->ask($messageToAsk, 'N');

        return $this->isPositiveUserInput($userInput);
    }

    /**
     * Return true if the input from user is a positive answer (Yes/yes/Y/y)
     *
     * @param string $userInput Raw user input
     *
     * @return bool
     */
    private function isPositiveUserInput(string $userInput) :bool
    {
        $positiveAnswers = ['yes', 'y'];

        return in_array(strtolower(trim($userInput)), $positiveAnswers, true);
    }

    /**
     * @deprecated since v.3.0.0 (2019-01-31); isInstalled() method will be removed in futuru
     *
     * @return string
     */
    protected function formTargetPath()
    {
        $extraParameters = $this->package->getExtra();

        if ((isset($extraParameters['oxideshop']['target-directory'])) &&
            (!empty($extraParameters['oxideshop']['target-directory']))) {
            $targetDirectory = $extraParameters['oxideshop']['target-directory'];
        } else {
            $targetDirectory = $this->package->getName();
        };

        return Path::join($this->rootDirectory, 'modules', $targetDirectory);
    }
}
