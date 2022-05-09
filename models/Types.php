<?php namespace Pensoft\ExternalDocuments\Models;

use Model;

/**
 * Model
 */
class Types extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'pensoft_externaldocuments_types';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
