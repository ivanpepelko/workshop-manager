<?php

namespace PhpSchool\WorkshopManager\Command;

use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\NoUpdateAvailableException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Updater;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

class UpdateWorkshop
{
    /**
     * @var Updater
     */
    private $updater;

    public function __construct(Updater $updater)
    {
        $this->updater = $updater;
    }

    public function __invoke(OutputInterface $output, string $workshopName): void
    {
        $output->writeln('');

        try {
            $output->writeln(
                sprintf(
                    " <info>Successfully updated %s to version %s</info>\n",
                    $workshopName,
                    $this->updater->updateWorkshop($workshopName)
                )
            );
        } catch (WorkshopNotFoundException $e) {
            $output->writeln(
                sprintf(
                    " <fg=magenta> It doesn't look like \"%s\" is installed, did you spell it correctly?</>\n",
                    $workshopName
                )
            );
            return;
        } catch (NoUpdateAvailableException $e) {
            $output->writeln(
                sprintf(" <fg=magenta> There are no updates available for workshop \"%s\".</>\n", $workshopName)
            );
            return;
        } catch (IOException $e) {
            $output->writeln(
                sprintf(
                    " <error> Failed to uninstall workshop \"%s\". Error: \"%s\" </error>\n",
                    $workshopName,
                    $e->getMessage()
                )
            );
        } catch (DownloadFailureException $e) {
            $output->writeln(
                sprintf(
                    " <error> There was a problem downloading the workshop. Error: \"%s\"</error>\n",
                    $e->getMessage()
                )
            );
        } catch (FailedToMoveWorkshopException $e) {
            $output->writeln([
                sprintf(' <error> There was a problem moving downloaded files for "%s"   </error>', $workshopName),
                " Please check your file permissions for the following paths\n",
                sprintf(' <info>%s</info>', dirname($e->getSrcPath())),
                sprintf(' <info>%s</info>', dirname($e->getDestPath())),
                ''
            ]);
        } catch (ComposerFailureException $e) {
            $output->writeln(
                sprintf(" <error> There was a problem installing dependencies for \"%s\" </error>\n", $workshopName)
            );
        } catch (\Exception $e) {
            $output->writeln(
                sprintf(" <error> An unknown error occurred: \"%s\" </error>\n", $e->getMessage())
            );
        }

        if (isset($e) && $output->isVerbose()) {
            throw $e;
        } elseif (isset($e)) {
            return;
        }
    }
}
