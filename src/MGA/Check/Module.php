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

namespace MGA\Check;

use MGA\Request;

/**
 * Check for installed modules
 */
class Module
{
    /**
     * Files and the modules they belong to
     *
     * @var array
     */
    public $files = array(
        'skin/frontend/base/default/aw_islider/representations/default/style.css' => 'AW_Islider',
        'skin/frontend/base/default/css/magestore/sociallogin.css'                => 'Magestore_Sociallogin',
    );

    /**
     * Check for module files that exist in a url
     *
     * @param  string $url
     * @return array
     */
    public function checkForModules($url)
    {
        $modules = array();
        $request = new Request;
        foreach ($this->files as $file => $name) {
            $response = $request->fetch($url . $file, array(
                CURLOPT_NOBODY         => true,
                CURLOPT_FOLLOWLOCATION => true
            ));
            if ($response->code == 200 && (!isset($modules[$name]) || $modules[$name] === false)) {
                $modules[$name] = true;
            } else {
                $modules[$name] = false;
            }
        }
        ksort($modules);
        return $modules;
    }
}
