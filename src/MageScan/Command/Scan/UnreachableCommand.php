<?php
/**
 * Mage Scan
 *
 * PHP version 5
 *
 * @category  MageScan
 * @package   MageScan
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magescan
 */

namespace MageScan\Command\Scan;

use MageScan\Check\UnreachablePath;
use MageScan\Command\ScanCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check accessibility of common exploit paths.
 *
 * @category  MageScan
 * @package   MageScan
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magescan
 */
class UnreachableCommand extends ScanCommand
{
    /**
     * Configure scan command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('scan:unreachable')
            ->setDescription('Check accessibility of common exploit paths.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeHeader('Unreachable Path Check');
        $unreachablePath = new UnreachablePath;
        $unreachablePath->setRequest($this->request);
        $results = $unreachablePath->checkPaths($this->url);
        foreach ($results as &$result) {
            if ($result[2] === false) {
                $result[2] = '<error>Fail</error>';
            } elseif ($result[2] === true) {
                $result[2] = '<bg=green>Pass</bg=green>';
            }
        }
        $table = new Table($this->output);
        $table
            ->setHeaders(array('Path', 'Response Code', 'Status'))
            ->setRows($results)
            ->render();
    }
}