<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\ComposerPlugin\Installer\Package;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use OxidEsales\EshopCommunity\Internal\Application\Dao\ProjectYamlDao;
use OxidEsales\EshopCommunity\Internal\Application\Service\ProjectYamlImportService;
use OxidEsales\EshopCommunity\Internal\Application\Service\ProjectYamlImportServiceInterface;
use OxidEsales\EshopCommunity\Internal\Application\Utility\BasicContext;

class ComponentInstaller extends AbstractPackageInstaller
{
    /**
     * @var ProjectYamlImportService
     */
    private $projectYamlImportService;

    /**
     * @param IOInterface $io
     * @param string $rootDirectory
     * @param PackageInterface $package
     * @param ProjectYamlImportServiceInterface|null $projectYamlImportService
     */
    public function __construct(
        IOInterface $io,
        $rootDirectory,
        PackageInterface $package
    ) {
        parent::__construct($io, $rootDirectory, $package);

        $context = new BasicContext();
        $projectYamlDao = new ProjectYamlDao($context);
        $this->projectYamlImportService = new ProjectYamlImportService($projectYamlDao);
        if (file_exists($context->getContainerCacheFilePath())) {
            unlink($context->getContainerCacheFilePath());
        }
    }

    public function install($packagePath)
    {
        $this->writeInstallingMessage("component");
        $this->importServiceFile($packagePath);
    }

    public function update($packagePath)
    {
        $this->writeUpdatingMessage("component");
        $this->importServiceFile($packagePath);
    }

    /**
     * @param $packagePath
     */
    protected function importServiceFile($packagePath)
    {
        $this->projectYamlImportService->removeNonExistingImports();
        $this->projectYamlImportService->addImport($packagePath);
    }
}
