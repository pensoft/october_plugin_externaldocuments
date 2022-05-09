<?php namespace Pensoft\ExternalDocuments;

use Pensoft\Externaldocuments\Components\PublicDocumentsTree;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
	public function registerComponents()
	{
		return [
			PublicDocumentsTree::class => 'publicDocumentsTree'
		];
	}

    public function registerSettings()
    {
    }
}
