<?php namespace Radiantweb\Flousps;

use App;
use Backend;
use System\Classes\PluginBase;

/**
 * Ocommerce Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'FloUsps',
            'description' => 'FloCommerce USPS Shipping Plugin',
            'author'      => 'Radiantweb',
            'icon'        => 'icon-shopping-cart'
        ];
    }


    public function registerShippingTypes()
    {
        $types = [
            'USPS' => 'Radiantweb\Flousps\Classes\Usps',
        ];

        return $types;
    }


}
