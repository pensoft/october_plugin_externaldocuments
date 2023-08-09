<?php namespace Pensoft\Externaldocuments;

use Backend;
use System\Classes\PluginBase;

/**
 * Externaldocuments Plugin Information File
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
            'name'        => 'Externaldocuments',
            'description' => 'No description provided yet...',
            'author'      => 'Pensoft',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Pensoft\Externaldocuments\Components\Documents' => 'ExternalDocumentsList',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'pensoft.externaldocuments.some_permission' => [
                'tab' => 'Externaldocuments',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'externaldocuments' => [
                'label'       => 'Externaldocuments',
                'url'         => Backend::url('pensoft/externaldocuments/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['pensoft.externaldocuments.*'],
                'order'       => 500,
            ],
        ];
    }
}
