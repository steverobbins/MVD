<?php
/**
 * Magento Guest Audit
 *
 * PHP version 5
 * 
 * @author    Steve Robbins <steven.j.robbins@gmail.com>
 * @license   http://creativecommons.org/licenses/by/4.0/
 * @link      https://github.com/steverobbins/magento-guest-audit
 */

namespace MGA\Command;

use MGA\Check\Catalog;
use MGA\Check\Module;
use MGA\Check\Version;
use MGA\Check\Sitemap;
use MGA\Request;
use MGA\Url;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Add scan command and run tests
 */
class ScanCommand extends Command
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * URL of Magento site
     * @var string
     */
    private $url;

    /**
     * List of paths that we shouldn't be able to access
     * @var array
     */
    protected $unreachablePathDefault = array(
        '.git/config',
        '.svn/entries',  
        'admin',
        'app/etc/local.xml',
        'phpinfo.php',
        'var/log/exception.log',
        'var/log/system.log',
    );

    /**
     * More paths that we shouldn't be able to access
     * @var array
     */
    protected $unreachablePathMore = array(
        '.bzr/',
        '.cvs/',
        '.git/',
        '.git/refs/',
        '.gitignore',
        '.hg/',
        '.svn/',  
        'app/etc/enterprise.xml',
        'p.php',
        'info.php',
        'var/export/export_all_products.csv',
        'var/export/export_product_stocks.csv',
        'var/export/export_customers.csv',
        'var/log/payment_authnetcim.log',
        'var/log/payment_authorizenet.log',
        'var/log/payment_authorizenet_directpost.log',
        'var/log/payment_cybersource_soap.log',
        'var/log/payment_ogone.log',
        'var/log/payment_payflow_advanced.log',
        'var/log/payment_payflow_link.log',
        'var/log/payment_paypal_billing_agreement.log',
        'var/log/payment_paypal_direct.log',
        'var/log/payment_paypal_express.log',
        'var/log/payment_paypal_standard.log',
        'var/log/payment_paypaluk_express.log',
        'var/log/payment_pbridge.log',
        'var/log/payment_verisign.log',
    );

    /**
     * Headers that provide information about the technology used
     * @var array
     */
    protected $techHeader = array(
        'Server',
        'Via',
        'X-Mod-Pagespeed',
        'X-Powered-By',
    );

    /**
     * Configure scan command
     */
    protected function configure()
    {
        $this
            ->setName('scan')
            ->setDescription('Audit a Magento site as best you can by URL')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The URL of the Magento application'
            )
            ->addOption(
                'all-paths',
                null,
                InputOption::VALUE_NONE,
                'Crawl all urls that should not be reachable'
            )
        ;
    }

    /**
     * Run scan command
     * 
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input   = $input;
        $this->output  = $output;
        $style = new OutputFormatterStyle('white', 'blue', array('bold'));
        $this->output->getFormatter()->setStyle('header', $style);

        $this->setUrl($input->getArgument('url'));
        $this->output->writeln('Scanning <info>' . $this->url . '</info>...');

        $this->checkMagentoInfo();
        $this->checkModules();
        $this->checkCatalog();
        $this->checkSitemapExists();
        $this->checkServerTech();
        $this->checkUnreachablePath($input->getOption('all-paths'));
    }

    /**
     * Get information about the Magento application
     */
    protected function checkMagentoInfo()
    {
        $this->writeHeader('Magento Information');
        $request = new Request;
        $response = $request->fetch(
            $this->url . 'js/varien/product.js', 
            array(
                CURLOPT_FOLLOWLOCATION => true
            )
        );
        $version = new Version;
        $edition = $version->getMagentoEdition($response);
        $version = $version->getMagentoVersion($response, $edition);
        $rows = array(
            array('Edition', $edition),
            array('Version', $version)
        );
        $this->getHelper('table')
            ->setHeaders(array('Parameter', 'Value'))
            ->setRows($rows)
            ->render($this->output);
    }

    /**
     * Check for files known to be associated with a module
     */
    protected function checkModules()
    {
        $this->writeHeader('Installed Modules');
        $module = new Module;
        $rows = array();
        foreach ($module->checkForModules($this->url) as $name => $exists) {
            $rows[] = array(
                $name,
                $exists ? '<bg=green>Yes</bg=green>' : 'No'
            );
        }
        $this->getHelper('table')
            ->setHeaders(array('Module', 'Installed'))
            ->setRows($rows)
            ->render($this->output);
    }

    /**
     * Get catalog data
     */
    protected function checkCatalog()
    {
        $this->writeHeader('Catalog Information');
        $rows     = array();
        $catalog  = new Catalog;
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
        $this->getHelper('table')
            ->setHeaders(array('Type', 'Count'))
            ->setRows($rows)
            ->render($this->output);
    }

    /**
     * Check HTTP status codes for files/paths that shouldn't be reachable
     */
    protected function checkUnreachablePath($all = false)
    {
        $this->writeHeader('Unreachable Path Check');
        $paths = $this->unreachablePathDefault;
        if ($all) {
            $paths += $this->unreachablePathMore;
            sort($paths);
        }
        $rows = array();
        $request = new Request;
        foreach ($paths as $path) {
            $response = $request->fetch($this->url . $path, array(
                CURLOPT_NOBODY => true
            ));
            $rows[] = array(
                $path,
                $response->code,
                $this->getUnreachableStatus($response)
            );
        }
        $this->getHelper('table')
            ->setHeaders(array('Path', 'Response Code', 'Status'))
            ->setRows($rows)
            ->render($this->output);
    }

    /**
     * Get the status string for the given response
     * 
     * @param  \stdClass $response
     * @return string
     */
    protected function getUnreachableStatus(\stdClass $response)
    {
        switch ($response->code) {
            case 200:
                return '<error>Fail</error>';
            case 301:
            case 302:
                $redirect = $response->header['Location'];
                if ($redirect != $this->url) {
                    return $redirect;
                }
        }
        return '<bg=green>Pass</bg=green>';
    }

    /**
     * Analize the server technology being used
     */
    protected function checkServerTech()
    {
        $this->writeHeader('Server Technology');
        $request = new Request;
        $response = $request->fetch($this->url, array(
            CURLOPT_NOBODY => true
        ));
        $rows = array();
        foreach ($this->techHeader as $value) {
            $rows[] = array(
                $value,
                isset($response->header[$value])
                    ? $response->header[$value]
                    : ''
            );
        }
        $this->getHelper('table')
            ->setHeaders(array('Key', 'Value'))
            ->setRows($rows)
            ->render($this->output);
    }

    /**
     * Check that the store is correctly using a sitemap
     */
    protected function checkSitemapExists()
    {
        $this->writeHeader('Sitemap');
        $url = $this->getSitemapUrl();
        $request = new Request;
        $response = $request->fetch($url, array(
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => true
        ));
        if ($response->code == 200) {
            $this->output
                ->writeln('<info>Sitemap is accessible:</info> ' . $url);
        } else {
            $this->output
                ->writeln('<error>Sitemap is not accessible:</error> ' . $url);
        }
    }

    /**
     * Parse the robots.txt text file to find the sitemap
     * 
     * @return string
     */
    protected function getSitemapUrl()
    {
        $request = new Request;
        $response = $request->fetch($this->url . 'robots.txt');
        $sitemap = new Sitemap;
        $sitemap  = $sitemap->getSitemapFromRobotsTxt($response);
        if ($sitemap === false) {
            $this->output->writeln(
                '<error>Sitemap is not declared in robots.txt</error>'
            );
            return $this->url . 'sitemap.xml';
        }
        $this->output
            ->writeln('<info>Sitemap is declared in robots.txt</info>');
        return $sitemap;
    }

    /**
     * Validate and set url
     * 
     * @param  string                   $input
     * @throws InvalidArgumentException
     */
    protected function setUrl($input)
    {   
        $url = new Url;
        $this->url = $url->clean($input);
        $request = new Request;
        $response = $request->fetch($this->url, array(
            CURLOPT_NOBODY => true
        ));
        if ($response->code == 0) {
            throw new \InvalidArgumentException(
                'Could not connect to URL: ' . $this->url
            );
        }
        if (isset($response->header['Location'])) {
            $this->url = $response->header['Location'];
        }
    }

    /**
     * Write a header block
     * 
     * @param  string $text
     * @param  string $style
     */
    protected function writeHeader($text, $style = 'bg=blue;fg=white')
    {
        $this->output->writeln(array(
            '',
            $this->getHelperSet()->get('formatter')
                ->formatBlock($text, $style, true),
            '',
        ));
    }
}
