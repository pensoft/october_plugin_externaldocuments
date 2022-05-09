<?php namespace Pensoft\ExternalDocuments\Models;

use Model;

/**
 * Model
 */
class Folders extends Model
{
    use \October\Rain\Database\Traits\Validation;
	use \October\Rain\Database\Traits\NestedTree;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'pensoft_externaldocuments_folders';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

	protected $nullable = ['parent_id'];

	public $belongsTo = [
		'parent' => 'Pensoft\Externaldocuments\Models\Folders',
		'type' => 'Pensoft\Externaldocuments\Models\Types',
	];

	public $attachMany = [
		'files' => ['System\Models\File', 'order' => 'sort_order'],
		'images' => ['System\Models\File', 'order' => 'sort_order'],
	];

	public $attachOne = [
		'cover' => 'System\Models\File'
	];
}
