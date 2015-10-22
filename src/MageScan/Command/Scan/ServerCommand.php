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

use MageScan\Check\TechHeader;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Scan server tech command
 *
 * @category  MageScan
 * @package   MageScan
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magescan
 */
class ServerCommand extends AbstractCommand
{
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('scan:server')
            ->setDescription('Check server technology');
        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $techHeader = new TechHeader;
        $techHeader->setRequest($this->request);
        $values = $techHeader->getHeaders($this->url);
        if (empty($values)) {
          if ($input->getOption('json')) {
            $this->output->write(json_encode(['error'=>'No detectable technology was found']));
          } else {
            $this->output->writeln('No detectable technology was found');
          }
            return;
        }

        if ($input->getOption('json')) {
          $this->output->write(json_encode($values));
        } else {
          $rows = array();
          foreach ($values as $key => $value) {
              $rows[] = array($key, $value);
          }
          $this->writeHeader('Server Technology');
          $table = new Table($this->output);
          $table
              ->setHeaders(array('Key', 'Value'))
              ->setRows($rows)
              ->render();
        }
    }
}
