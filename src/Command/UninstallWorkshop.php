<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\ManagerState;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PhpSchool\WorkshopManager\Uninstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UninstallWorkshop
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class UninstallWorkshop
{
    /**
     * @var Uninstaller
     */
    private $uninstaller;
    
    /**
     * @var WorkshopRepository
     */
    private $workshopRepository;

    /**
     * @var Linker
     */
    private $linker;

    /**
     * @var ManagerState
     */
    private $managerState;

    /**
     * @param Uninstaller $uninstaller
     * @param WorkshopRepository $installedRepository
     * @param Linker $linker
     * @param ManagerState $managerState
     */
    public function __construct(
        Uninstaller $uninstaller,
        WorkshopRepository $installedRepository,
        Linker $linker,
        ManagerState $managerState
    ) {
        $this->uninstaller        = $uninstaller;
        $this->workshopRepository = $installedRepository;
        $this->linker             = $linker;
        $this->managerState       = $managerState;
    }

    /**
     * @param OutputInterface $output
     * @param string $workshopName
     *
     * @return void
     * @throws \RuntimeException
     */
    public function __invoke(OutputInterface $output, $workshopName)
    {
        $output->writeln('');

        try {
            $workshop = $this->workshopRepository->getByName($workshopName);
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(sprintf(' <error> No workshops found matching "%s" </error>', $workshopName));
            return;
        }

        try {
            $this->linker->unlink($workshop, $input->getOption('force'));
            $this->uninstaller->uninstallWorkshop($workshop);
        } catch (WorkshopNotInstalledException $e) {
            $output->writeln(sprintf(' <error> Workshop "%s" not currently installed </error>', $workshop->getName()));
            return;
        } catch (\RuntimeException $e) {
            $output->writeln([
                '',
                sprintf(' <error> Failed to uninstall workshop "%s" </error>', $workshop->getName())
            ]);

            if ($$output->isVerbose()) {
                throw $e;
            }
            return;
        }

        $this->managerState->removeWorkshop($workshop);
        $output->writeln(sprintf(' <info>Successfully uninstalled "%s"</info>', $workshop->getName()));
    }
}