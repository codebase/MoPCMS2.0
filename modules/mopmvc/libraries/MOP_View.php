<?php defined('SYSPATH') or die('No direct script access.');

/*
The idea here is to have the subclassed 'My View' handle all the loading of frontend resources for any module or Controller\View pair.  

*/

class MOP_View_Core extends View_Core{


	public $name;
	protected $resources = array('js'=>array(), 'css'=>array(), 'libraryjs'=>array(), 'librarycss'=>array());


	public function __construct($name = NULL, $data = NULL, $type = NULL){
		parent::__construct($name, $data, $type);
		$this->name = $name;
	}


	public function loadResources($name=NULL){
		if($name==NULL){
			$name = $this->name;
		}

		$this->loadLibraryResources($name);

		//auto load frontend resources
		Kohana::log('debug', 'autoloading frontend resources for ' . $name);
		//somehow log all the assets

		//and load resources of this module
		$this->loadModuleResources($name);
		
	}

	public function loadLibraryResources($name){
		//what to do about extends ?
		//	get_parent_class($this), or parent goes in the config, or resource goes in the config

		Kohana::log('debug', 'autoloading library frontend resources for ' . $name);
		if(($resources = Kohana::config($name.'.resources', FALSE, FALSE)) && is_array($resources)){
			Kohana::log('debug', 'found resources for ' . $name);
			foreach($resources as $type=>$list){
				if(!$list || !is_array($list)){
					continue;
				}
				switch($type){
					case 'css':
						foreach($list as $item){
							$this->addResource('librarycss', $item, TRUE);
						}


						break;
					case 'js':
						foreach($list as $item){
							$this->addResource('libraryjs', $item, TRUE);
						}		


						break;
				}
			}
		}
		
	}


	public function loadModuleResources($name){

		//if it exists then add...
		if($path = Kohana::find_file('views', $name, FALSE, 'js')){
			Kohana::log('info', 'found module resource '.$path);
			
			if(strstr($path, 'application/modules')){
				Kohana::log('info', 'ok');
				$path = substr($path, strpos($path, 'application'));
				$this->addResource('js',$path);
			}	else if(strstr($path, 'modules')){
				$path = substr($path, strpos($path, 'modules'));
				$this->addResource('js', $path);
			} else if(strstr($path, 'application')){
				$path = substr($path, strpos($path, 'application'));
				$this->addResource('js', $path);
			}
		}

		if($path = Kohana::find_file('views', $name, FALSE, 'css')){
			if(strstr($path, 'application/modules')){
				$path = substr($path, strpos($path, 'application'));
				$this->addResource('css', $path);
			} else if(strstr($path, 'modules')){
				$path = substr($path, strpos($path, 'modules'));
				$this->addResource('css', $path);
			} else if(strstr($path, 'application')){
				$path = substr($path, strpos($path, 'application'));
				$this->addResource('css', $path);
			}
		}


	}


	public function set_filename($name, $type = NULL) {

		/*
		 * To support view files in app/frontend/, this is a little hacky but 
		 * gets the job done.
		 */
		if(file_exists('application/views/frontend/'.$name.EXT)){
			$this->kohana_filename = 'application/views/frontend/'.$name.EXT;
			$this->kohana_filetype =  EXT;
		} else if(file_exists('application/views/generated/'.$name.EXT)){
			$this->kohana_filename = 'application/views/generated/'.$name.EXT;
			$this->kohana_filetype =  EXT;
		} else {
			parent::set_filename($name, $type);
		}
		return $this;

	}

	public function render($output=FALSE, $renderer = false){
		Display_Controller::addResources($this->resources);
		return parent::render($output, $renderer);
	}

	public function addResource($type, $resource){
		Kohana::log('info', 'adding resource '.$type.$resource);
		$this->resources[$type][$resource] = $resource;
	}

	public function addResources($type, $resources){
		foreach($resources as $resource){
			$this->resources[$type][$resource] = $resource;
		}
	}

	public function setByArray($values){
		foreach($values as $key=>$value){
			$this->set($key, $value);
		}
	}



}
?>
