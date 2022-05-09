<?php namespace Pensoft\Externaldocuments\Components;

use Backend\Facades\BackendAuth;
use Cms\Classes\ComponentBase;
use Media\Classes\MediaLibrary;
use Pensoft\ExternalDocuments\Models\Folders;
use Pensoft\ExternalDocuments\Models\Types;
use System\Models\File;

/**
 * PublicDocumentsTree Component
 */
class PublicDocumentsTree extends ComponentBase
{

	public $loggedIn;

    public function componentDetails()
    {
        return [
            'name' => 'PublicDocumentsTree Component',
            'description' => 'No description provided yet...'
        ];
    }

	public function onRun()
	{
		// by default users are not logged in
		$this->loggedIn = false;
		// end then if getUser returns other value than NULL then our user is logged in
		if (!empty(BackendAuth::getUser())) {
			$this->loggedIn = true;
		}

		$this->addJs('assets/js/popper.min.js');
		$this->addJs('assets/js/tippy-bundle.umd.min.js');
		$this->addJs('assets/js/def.js');

		if(get('download')){
			$this->downloadFiles();
		}

		$this->page['queries'] = http_build_query(\Input::all());
		$this->page['is_download'] = get('download', false);

		if($query = post('query')){
			$this->page['has_query'] = true;
			$Folders = Folders::where('name', 'iLIKE', '%' . $query . '%')->get();
			$this->page['sub_folders'] = $Folders;

			$this->page['files'] = File::where('attachment_type', 'Pensoft\Externaldocuments\Models\Folders')
				->where('file_name', 'ilike', '%' . $query . '%')
				->get()
				->map(function ($file) {
					$folder = Folders::find($file->attachment_id);
					$file->folderData = $folder;
					return $file;
				});
		}

		if((int)$this->property('typeId') > 0){
			$this->page['folders'] = Folders::where('parent_id', null)
				->where('type_id', (int)$this->property('typeId'))
				->get();
		}else{
			$this->page['folders'] = Folders::where('parent_id', null)->get();
		}

		if($this->param('parent_id')){
			$this->page['expand'] = $this->param('parent_id');
		}
	}

    public function defineProperties()
    {
        return [
			'typeId' => [
				'title' => 'Type',
				'required' => false,
				'type' => 'dropdown',
				'description' => 'Select type/category',
				'emptyOption' => ' -- None -- ',
			],
		];
    }

	public function getTypeidOptions(){
		return Types::all()->lists('name', 'id');
	}

	public function downloadFiles(){
//		$inputs = Input::except('download');
		$download = Input::get('download');
//		$link = $this->pageUrl($pageId) .'?'. http_build_query($inputs);

		$file_ids = explode(',', $download);
		if(count($file_ids) === 1){
			$file = File::find($file_ids[0]);
			$file->output('attachment');
			exit();
		}else if(count($file_ids) > 1){
			$files = File::find($file_ids);
			$zip_file = tempnam(sys_get_temp_dir(), "archives");
			$zip = new \ZipArchive();
			$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);


			foreach ($files as $item) {
				$fileId = $item['id'];
				$file = File::find($fileId);
				$filePath = $file->getLocalPath();
				$filename = $item['file_name'];
				$i = 1;
				if ($filename == basename($filePath)) {
					// If this file already exists add "-1, -2"
					$filename = $i . '-' . basename($filePath);
					$i++;
				} else {
					$filename = basename($filePath);
					$i = 1;
				}

				$zip->addFile(
					$filePath,
					$filename
				);
			}
			$zip->close();
			header("Content-type: application/zip");
			header("Content-Disposition: attachment; filename=archives.zip");
			header("Pragma: no-cache");
			header("Expires: 0");
			readfile($zip_file);
			exit();
		}
//		return redirect($link);
	}

	public function onDeleteFile(){
		$fileId = post('id');
		$file = File::find($fileId);
		if($file)
			$file->delete();
		return ['#delete_result_'.$fileId => ''];
	}

	public function onDeleteFolder(){
		$folderId = post('id');
		$folder = Folders::find($folderId);
		if($folder){
			$folder->delete();
		}
		return ['#delete_result_'.$folderId => ''];
	}

	public function onSubmit(){
		$validator = \Validator::make(
			[
				'name' => \Input::get('name'),
				'files' => \Input::file('files'),
				'images' => \Input::file('images'),
			],
			[
				'name' => 'required_without_all:files,images',
				'files' => 'required_without_all:images,name',
				'images' => 'required_without_all:name,files',
			]
		);

		if($validator->fails()){
			return \Redirect::back()->withErrors($validator);
		}

		if(\Input::get('name')){
			$subfolder = new Folders();
			$subfolder->name = \Input::get('name');
			$subfolder->parent_id = \Input::get('parent');
			$subfolder->type_id = \Input::get('type_id');
			$subfolder->save();
		}

		$folderData = Folders::where('id', \Input::get('parent'))->first();

		$this->page['folder'] = $folderData;
		$this->page['group_id'] = \Input::get('parent');

	}

	public function onFolderRename(){
		$folderId = \Input::get('id');
		$folderName = \Input::get('name');

		$folder = Folders::find($folderId);
		if($folder){
			Folders::where('id', $folderId)->update(['name' => $folderName]);
		}

		$this->page['name'] = $folderName;
	}

	public function onImageUpload(){
		$image = \Input::all();
		$images = $image['images'];

		$output = '';
		foreach ($images as $photo) {
			$file = (new File())->fromPost($photo);
			$output .= '<img src="' . $file->getThumb(170, 120, ['mode' => 'crop']) . '"> ';
		}

		return  [
			'#image_result' => $output
		];
	}

	public function onFileUpload(){
		$image = \Input::all();
		$images = $image['files'];

		$output = '';
		foreach ($images as $photo) {
			$file = (new File())->fromPost($photo);
			if($file->getExtension() == 'docx' || $file->getExtension() == 'doc'){
				$mediaFileName = 'files_doc.svg';
			}else if($file->getExtension() == 'pdf'){
				$mediaFileName = 'files_pdf.svg';
			}else{
				$mediaFileName = 'files_file.svg';
			}
			$output .= '<img src="' . MediaLibrary::url($mediaFileName) . '" style="width: 30px; float: left; margin-right: 8px;"> '. $file->getFilename().' <br>';
		}

		return  [
			'#file_result' => $output
		];
	}

	public function onSortFiles(){
		parse_str(post('sortOrder'), $output);
		$reorderIds = $output['sortItem'];
		$subfolderId = post('subfolderId');
		$moved = [];
		$position = 0;

		if (is_array($reorderIds) && count($reorderIds)) {
			foreach ($reorderIds as $id) {
				if (in_array($id, $moved) || !$record = File::find($id))
					continue;
				$record->sort_order = $position;
				$record->save();
				$moved[] = $id;
				$position++;
			}
		}

		$subfolderData = Folders::where('parent_id', $subfolderId)->get();

		$this->page['files'] = $subfolderData;
		$this->page['group_id'] = $subfolderId;
	}

	public function onSortFolders(){
		parse_str(post('sortOrder'), $output);

		$sourceNode = Folders::find(post('sourceNode'));
		$targetNode = post('targetNode') ? Folders::find(post('targetNode')) : null;

		if ($sourceNode == $targetNode) {
			return;
		}

		switch (post('position')) {
			case 'left':
			default:
				$sourceNode->moveBefore($targetNode);
				break;

			case 'right':
				$sourceNode->moveAfter($targetNode);
				break;
		}

	}
}
