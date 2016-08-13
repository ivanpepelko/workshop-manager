<?php

namespace PhpSchool\WorkshopManagerTest\Command;

use PhpSchool\WorkshopManager\Command\InstallWorkshop;
use PhpSchool\WorkshopManager\Command\UpdateWorkshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\NoUpdateAvailableException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Installer\Updater;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UpdateWorkshopTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Updater
     */
    private $updater;

    /**
     * @var UpdateWorkshop
     */
    private $command;

    /**
     * @var OutputInterface
     */
    private $output;

    public function setUp()
    {
        $this->updater = $this->createMock(Updater::class);
        $this->command = new UpdateWorkshop($this->updater);
        $this->output = $this->createMock(OutputInterface::class);
    }

    public function testWhenWorkshopIsNotInstalled()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new WorkshopNotFoundException);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> It doesn't look like \"learnyouphp\" is installed, did you spell it correctly?</>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenNoUpdateAvailable()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new NoUpdateAvailableException());

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <fg=magenta> There are no updates available for workshop \"learnyouphp\".</>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenFilesCannotBeCleanedUp()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new IOException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> Failed to uninstall workshop \"learnyouphp\". Error: \"Some error\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenDownloadFails()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new DownloadFailureException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> There was a problem downloading the workshop. Error: \"Some error\"</error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenFailedToMove()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new FailedToMoveWorkshopException('/root/src', '/root/workshops/learnyouphp'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [
                    [
                        ' <error> There was a problem moving downloaded files for "learnyouphp"   </error>',
                        " Please check your file permissions for the following paths\n",
                        ' <info>/root</info>',
                        ' <info>/root/workshops</info>',
                    '',
                    ]
                ]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testWhenComposerInstallFails()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new ComposerFailureException('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> There was a problem installing dependencies for \"learnyouphp\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testAnyOtherFailure()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new \Exception('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> An unknown error occurred: \"Some error\" </error>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testExceptionIsThrownIfInVerboseMode()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willThrowException(new \Exception('Some error'));

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <error> An unknown error occurred: \"Some error\" </error>\n"]
            );

        $this->output
            ->expects($this->once())
            ->method('isVerbose')
            ->willReturn(true);

        $this->expectException(\Exception::class);

        $this->command->__invoke($this->output, 'learnyouphp');
    }

    public function testSuccess()
    {
        $this->updater
            ->expects($this->once())
            ->method('updateWorkshop')
            ->with('learnyouphp')
            ->willReturn('2.0.0');

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [""],
                [" <info>Successfully updated learnyouphp to version 2.0.0</info>\n"]
            );

        $this->command->__invoke($this->output, 'learnyouphp');
    }
}
