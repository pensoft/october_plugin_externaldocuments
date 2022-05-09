<?php namespace Pensoft\ExternalDocuments\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreatePensoftExternaldocumentsFolders extends Migration
{
    public function up()
    {
        Schema::create('pensoft_externaldocuments_folders', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('name');
            $table->integer('parent_id')->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->string('slug')->nullable();
            $table->integer('type_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('pensoft_externaldocuments_folders');
    }
}
