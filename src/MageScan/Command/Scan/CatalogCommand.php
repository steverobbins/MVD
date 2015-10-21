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

use MageScan\Check\Catalog;
use MageScan\Command\ScanCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Get catalog information about a site.
 *
 * @category  MageScan
 * @package   MageScan
 * @author    Steve Robbins <steve@steverobbins.com>
 * @copyright 2015 Steve Robbins
 * @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 * @link      https://github.com/steverobbins/magescan
 */
class CatalogCommand extends ScanCommand
{
    /**
     * Configure scan command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('scan:catalog')
            ->setDescription('Get catalog information about a site.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeHeader('Catalog Information');
        $rows     = array();
        $catalog  = new Catalog;
        $catalog->setRequest($this->request);
        $categoryCount = $catalog->categoryCount($this->url);
        $rows[] = array(
            'Categories',
            $categoryCount !== false ? $categoryCount : 'Unknown'
        );
        $productCount = $catalog->productCount($this->url);
        $rows[] = array(
            'Products',
            $productCount !== false ? $productCount : 'Unknown'
        );
        $table = new Table($this->output);
        $table
            ->setHeaders(array('Type', 'Count'))
            ->setRows($rows)
            ->render();
    }
}