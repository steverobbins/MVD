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

require_once __DIR__ . '/../vendor/autoload.php';

use MageScan\Command\Scan;
use MageScan\Command\SelfUpdateCommand;
use Symfony\Component\Console\Application;

$app = new Application('Mage Scan', '1.9.2');

$app->add(new Scan\AllCommand);
$app->add(new Scan\CatalogCommand);
$app->add(new Scan\ModulesCommand);
$app->add(new Scan\PatchesCommand);
$app->add(new Scan\ServerCommand);
$app->add(new Scan\SitemapCommand);
$app->add(new Scan\UnreachableCommand);
$app->add(new Scan\VersionCommand);
$app->add(new SelfUpdateCommand);

$app->run();
