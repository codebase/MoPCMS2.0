<?

/* Class: CMS helper
 * Contains utility function for CMS
 */

class CMS {

	private static $mediapath;

	/*
	 * Variable: createSlug($title, $forPageId)
	 * Creates a unique slug to identify a page
	 * Parameters:
	 * $title - optional title for the slug
	 * $forPageId - optionally indicate the id of the page this slug is for to avoid false positive slug collisions
	 * Returns: The new, unique slug
	 */
	public static function createSlug($title=NULL, $forPageId=NULL){
		//create slug
		if($title!=NULL){
			$removeChars = array(
				"'",
				'"',
				'&',
				'?',
				'/',
				'\\',
				')',
				'(',
			);
			Kohana::log('info', $title);
			$slug = str_replace($removeChars, '', strtolower($title));
			$slug = str_replace(' ', '-', strtolower($slug));
			Kohana::log('info', $slug);
			$checkSlug = ORM::Factory('page')
				->regex('slug', '^'.$slug.'[0-9]*$')
				->orderby("slug");
			if($forPageId != NULL){
				$checkSlug->where('pages.id != '.$forPageId);
			}
			$checkSlug = $checkSlug->find_all();
			if(count($checkSlug)){
				$idents = array();
				foreach($checkSlug as $ident){
					$idents[] = $ident->slug;
				}
				natsort($idents);
				$idents = array_values($idents);
				$maxslug = $idents[count($idents)-1];
				if($maxslug){
					$curindex = substr($maxslug, strlen($slug));
					$newindex = $curindex+1;
					$slug .= $newindex;
				}
			}
			return $slug;
		} else {
			return 'No_Title_'.microtime(); //try something else
		}
	}

	public static function convertNewlines($value){
		$value = preg_replace('/(<.*>)[ ]*\n/', "$1------MOP_NEWLINE------", $value);
		$value = preg_replace('/[ ]*\n/', '<br />', $value);
		$value = preg_replace('/------MOP_NEWLINE------/', "\n", $value);
		return $value;
	}

	public static function mediapath(){
		if(self::$mediapath){
			return self::$mediapath;
		}
		if(Kohana::config('mop.staging')){
			self::$mediapath = Kohana::config('cms.stagingmediapath');
		} else {
			self::$mediapath = Kohana::config('cms.basemediapath');
		}
		return self::$mediapath;
	}

	/*
	 *
	 */
	public static function resizeImage($originalFilename, $newFilename, $width, $height, $forceDimension='width', $crop='false'){
		//set up dimenion to key off of
		switch($forceDimension){
		case 'width':
			$keydimension = Image::WIDTH;
			break;
		case 'height':
			$keydimension = Image::HEIGHT;
			break;
		default:
			$keydimension = Image::AUTO;
			break;
		}

		$image = new Image(cms::mediapath().$originalFilename);
		if($crop) {
			//resample with crop
			//set up sizes, and crop
			if( ($image->width / $image->height) > ($image->height / $image->width) ){
				$cropKeyDimension = Image::HEIGHT;
			} else {
				$cropKeyDimension = Image::WIDTH;
			}
			$image->resize($width, $height, $cropKeyDimension)->crop($width, $height);
      $quality = Kohana::config('cms.imagequality');
			$image->quality($quality);
			$image->save(cms::mediapath().$newFilename);

		} else {
			//just do the resample
			//set up sizes
			$resizewidth = $width;
			$resizeheight = $height;

			if(isset($resize['aspectfollowsorientation']) && $resize['aspectfollowsorientation']){
				$osize = getimagesize(cms::mediapath().$imageFilename);
				$horizontal = false;
				if($osize[0] > $osize[1]){
					//horizontal
					$horizontal = true;	
				}
				$newsize = array($resizewidth, $resizeheight);
				sort($newsize);
				if($horizontal){
					$resizewidth = $newsize[1];
					$resizeheight = $newsize[0];
				} else {
					$resizewidth = $newsize[0];
					$resizeheight = $newsize[1];
				}
			}

			//maintain aspect ratio
			//use the forcing when it applied
			//forcing with aspectfolloworientation is gonna give weird results!
			$image->resize($resizewidth, $resizeheight, $keydimension);

			$image->quality($quality);
			$image->save(cms::mediapath .$newFilename);

		}

	}

	/*
	 * Function: buildUIHtmlChunks
	 * This function builds the html for the UI elements specified in an object type's paramters
	 * Parameters:
	 * $parameters - the parameters array from an object type configuration
	 * Returns: Associative array of html, one entry for each ui element
	 */
	public static function buildUIHtmlChunks($parameters, $object = null){
		$view = new View();
		$htmlChunks = array();
		if(is_array($parameters)){
			foreach($parameters as $module){
				switch($module['type']){
				case 'module':
					if(isset($module['arguments'])){
						$html = mop::buildModule($module, $module['modulename'], $module['arguments']);
					} else {
						$html = mop::buildModule($module, $module['modulename']);
					}
					$htmlChunks[$module['modulename']] = $html;
					break;
				case 'list':
					if(isset($module['display']) && $module['display'] != 'inline'){
						break; //module is being displayed via navi, skip
					}
					$module['modulename'] = $module['family'];
					$module['controllertype'] = 'list';
					$lt = ORM::Factory('template', $module['family']);
					$containerObject = ORM::Factory('page')
						->where('parentid', $object->id)
						->where('template_id', $lt->id)
						->where('activity IS NULL')
						->find();
          if(!$containerObject->loaded){
            throw new Kohana_User_Exception('Did not find list container', 'List object is missing container: '.$lt->id);
          }
					$arguments = array(
						'containerObject'=>$containerObject
					);
					$htmlChunks[$module['family']] = mop::buildModule($module, $arguments);
					break;
				case 'associator':
					$module['pool'] = array();
					$selection = array();
					$module['modulename'] = $module['field'];
					$module['controllertype'] = 'associator';
					$arguments = array(
						'module'=>$module,
						'selection'=>$selection
					);
					$element = mop::buildModule($module, $arguments); 
					$htmlChunks[$module['type'].'_'.$module['field']] = $element;
					break;
				default:
					$element = false;
					//deal with html template elements
					if(!isset($module['field'])){
						$element = mopui::buildUIElement($module, null);
						$module['field'] = CMS_Controller::$unique++;
					} else if(!$element = mopui::buildUIElement($module, $object->contenttable->$module['field'])){
						throw new Kohana_User_Exception('bad config in cms', 'bad ui element');
					}
					$htmlChunks[$module['type'].'_'.$module['field']] = $element;
					break;
				}
			}
		}
		return $htmlChunks;
	}

  
	public static function buildUIHtmlChunksForObject($object){
		$elements = mop::config('objects', sprintf('//template[@name="%s"]/elements/*', $object->template->templatename));
    $elementsConfig = array();
		foreach($elements as $element){
			$entry = array();
      $entry['type'] = $element->tagName;
			for($i=0; $i<$element->attributes->length; $i++){
				$entry[$element->attributes->item($i)->name] = $element->attributes->item($i)->value;
			}	
      //make sure defaults load
      $entry['tag'] = $element->getAttribute('tag');
      $entry['rows'] = $element->getAttribute('rows');
      //any special xml reading that is necessary
      switch($entry['type']){
      case 'singleFile':
      case 'singleImage':
        $ext = array();
        //echo sprintf('/template[@name="%s"]/elements/singleImage[@field="%s]"/ext', $object->template->templatename, $element->getAttribute('field'));
        $children = mop::config('objects', 'ext', $element);
        foreach($children as $child){
          if($child->tagName == 'ext'){
            $ext[] = $child->nodeValue; 
          }
        }
        $entry['extensions'] = implode(',', $ext);
        break;
      case 'radioGroup':
        $children = mop::config('objects', 'radio', $element);
        $radios = array();
        foreach($children as $child){
          $label = $child->getAttribute('label');
          $value = $child->getAttribute('value');
          $radios[$label] =$value;
        }
        $entry['radios'] = $radios;
        break;

      default:
        break;
      }
			$elementsConfig[] = $entry;
		}

    return cms::buildUIHtmlChunks($elementsConfig, $object);
  }

	public static function regenerateImages(){
		//find all images

		foreach(mop::config('objects', '//template') as $template){
			foreach(mop::config('objects', 'elements/*', $template) as $element){
				if($element->tagName == 'singleImage'){
					$objects = ORM::Factory('template', $template->getAttribute('name'))->getActiveMembers();
					$fieldname = $element->getAttribute('field');
					foreach($objects as $object){
						if(is_object($object->contenttable->$fieldname) && $object->contenttable->$fieldname->filename && file_exists(cms::mediapath() . $object->contenttable->$fieldname->filename)){
							$object->processImage($object->contenttable->$fieldname->filename, $fieldname);
						}
					}
				}
			}
		}
	}

	public static function generateNewImages($objectIds){
		foreach($objectIds as $id){
			$object = ORM::Factory('page', $id);
			foreach(mop::config('objects', sprintf('//template[@name="%s"]/elements/*', $object->template->templatename)) as $element){
				if($element->tagName == 'singleImage'){
					$fieldname = $element->getAttribute('field');
					if(is_object($object->contenttable->$fieldname) && $object->contenttable->$fieldname->filename && file_exists(cms::mediapath() . $object->contenttable->$fieldname->filename)){
						$object->processImage($object->contenttable->$fieldname->filename, $fieldname);
					}
				}
			}	
		}
	}


	/*
	 * Function: checkForValidPageId($id) 
	 * Validates the current page id 
	 * Parameters:
	 * $id  - the id to check 
	 * Returns: throws exception on invalid page id
	 */
	public static function checkForValidPageId($id){
		if(!ORM::Factory('page')->where('id', $id)->find()->loaded){
			throw new Kohana_User_Exception('Bad Page Id', 'The id '.$id.' is not a key in for an object in the pages table');
		}
	}

	/*
	Function: addObject($id)
	Private function for adding an object to the cms data
	Parameters:
	id - the id of the parent category
	template_id - the type of object to add
	$data - possible array of keys and values to initialize with
	Returns: the new page id
	*/
	public static function addObject($parent_id, $template_ident, $data = array() ){
		$template_id = ORM::Factory('template', $template_ident)->id;
    if(!$template_id){
      //we're trying to add an object of template that doesn't exist in db yet
      //check objects.xml for configuration
      if($templateConfig = mop::config('objects', sprintf('//template[@name="%s"]', $template_ident))->item(0)){
        //there's a config for this template
        //go ahead and configure it
        cms::configureTemplate($templateConfig);
      }
      $template_id = ORM::Factory('template', $template_ident)->id;
    } 


		if($parent_id!=='0' && $parent_id!==0){
			cms::checkForValidPageId($parent_id);
		}
		$newpage = ORM::Factory('page');
		$newpage->template_id = $template_id;

		//create slug
		if(isset($data['title'])){
			$newpage->slug = cms::createSlug($data['title'], $newpage->id);
		} else {
			$newpage->slug = cms::createSlug();
		}
		$newpage->parentid = $parent_id;

		//calculate sort order
		$sort = ORM::Factory('page')
		->select('max(sortorder)+1 as newsort')
		->where('parentid', $parent_id)
		->find();
		$newpage->sortorder = $sort->newsort;

		$newpage->save();
	

		//check for enabled publish/unpublish. 
		//if not enabled, insert as published
		$template = ORM::Factory('template', $template_id);
		$tSettings = mop::config('objects', sprintf('//template[@name="%s"]', $template->templatename) ); 
		$tSettings = $tSettings->item(0);
		$newpage->published = 1;
		if($tSettings){ //entry won't exist for Container objects
			if($tSettings->getAttribute('allowTogglePublish') == 'true' ) {
				$newpage->published = 0;
			}
		}
		if(isset($data['published']) && $data['published'] ){
			$newpage->published = 1;
			unset($data['published']);
		}

		$newpage->save();

		//Add defaults to content table
		$newtemplate = ORM::Factory('template', $newpage->template_id);

		//add submitted data to content table
		foreach($data as $field=>$value){
			//need to switch here on type of field
			
			switch($field){
			case 'slug':
					$newpage->$field = $data[$field];
					continue(2);
			case 'title':
					$newpage->contenttable->$field = $data[$field];
					continue(2);
			}

			$fieldInfo = mop::config('objects', sprintf('//template[@name="%s"]/elements/*[@field="%s"]', $template->templatename, $field))->item(0);
			if(!$fieldInfo){
				die("Bad field!\n". sprintf('//template[@name="%s"]/elements/*[@field="%s"]', $template->templatename, $field));
			}
			switch($fieldInfo->tagName){
			case 'singleFile':
			case 'singleImage':
				$file = ORM::Factory('file');
				$file->filename = $value;			
				$file->save();
				$newpage->contenttable->$field = $file->id;
				break;
			case 'object':
				//not sure how to handle this!
				//probably just assume that $value is an array to put into object
				//well actually it's just another call to cms::addObject
				$oTemplate = ORM::Factory('template', $field);
				cms::addObject($newpage->id, $oTemplate->id ,$value);
				break;

			default:
				$newpage->contenttable->$field = $data[$field];
				break;
			}
		}
		$newpage->contenttable->save();
		$newpage->save();

		//look up any components and add them as well

		//configured components
		$components = mop::config('objects', sprintf('//template[@name="%s"]/components/component',$newtemplate->templatename));
		foreach($components as $c){
			$template = ORM::Factory('template', $c->getAttribute('templateName'));
			$arguments = array();
			if($label = $c->getAttribute('label')){
				$arguments['title'] = $label;
			}
			if($c->hasChildNodes()){
				foreach($c->childNodes as $data){
					$arguments[$data->tagName] = $data->value;
				}
			}
			cms::addObject($newpage->id, $template->id, $arguments);
		}

		//containers (list)
		$containers = mop::config('objects', sprintf('//template[@name="%s"]/elements/list',$newtemplate->templatename));
		foreach($containers as $c){
			$template = ORM::Factory('template', $c->getAttribute('family'));
			$arguments['title'] = $c->getAttribute('label');
			cms::addObject($newpage->id, $template->id, $arguments);
		}

		return $newpage->id;
	}

	public static function makeFileSaveName($filename){
		$xarray = explode('.', $filename);
		$nr = count($xarray);
		$ext = $xarray[$nr-1];
		$name = array_slice($xarray, 0, $nr-1);
		$name = implode('.', $name);
		$i=1;
		if(!file_exists(cms::mediapath()."$name".'.'.$ext)){
			$i='';
		} else {
			for(; file_exists(cms::mediapath()."$name".$i.'.'.$ext); $i++){}
		}

		//clean up extension
		$ext = strtolower($ext);
		if($ext=='jpeg'){ $ext = 'jpg'; }

		return $name.$i.'.'.$ext;
	}

	public static function saveHttpPostFile($objectid, $field, $postFileVars){

		$object = ORM::Factory('page', $objectid);
		//check the file extension
		$filename = $postFileVars['name'];
		$ext = substr(strrchr($filename, '.'), 1);
		switch($ext){
		case 'jpeg':
		case 'jpg':
		case 'gif':
		case 'png':
		case 'JPEG':
		case 'JPG':
		case 'GIF':
		case 'PNG':
		case 'tif':
		case 'tiff':
		case 'TIF':
		case 'TIFF':
			return $object->saveUploadedImage($field, $postFileVars['name'], 
																				$postFileVars['type'], $postFileVars['tmp_name']);
			break;

		default:
			return $object->saveUploadedFile($field, $postFileVars['name'], 
				$postFileVars['type'], $postFileVars['tmp_name']);
		}

	}

	public static function configureTemplate($template){
		//validation
		//
		foreach(mop::config('objects', '//template[@name="'.$template->getAttribute('name').'"]/elements/*') as $item){
			if($item->getAttribute('field')=='title'){
				die('Title is a reserved field name');
			}
		}


		//find or create template record
		$tRecord = ORM::Factory('template', $template->getAttribute('name') );
		if(!$tRecord->loaded){
			echo "\ncreating for ".$template->getAttribute('name')."\n";
			$tRecord = ORM::Factory('template');
			$tRecord->templatename = $template->getAttribute('name');
			$tRecord->nodeType = 'object';
			$tRecord->save();
		}
		Kohana::log('info', 'configureTemplate: using '.$tRecord->id."\n");

		//create title field
		$checkMap = ORM::Factory('objectmap')->where('template_id', $tRecord->id)->where('column', 'title')->find();
		if(!$checkMap->loaded){
			$index = 'field';
			$count = ORM::Factory('objectmap')
				->select('max(`index`) as maxIndex')
				->where('type', $index)
				->where('template_id', $tRecord->id)
				->find();
			$nextIndex = $count->maxIndex+1;

			$newmap = ORM::Factory('objectmap');
			$newmap->template_id = $tRecord->id;
			$newmap->type = $index;
			$newmap->index = $nextIndex;
			$newmap->column = 'title';
			$newmap->save();
		}


		foreach(mop::config('objects', '//template[@name="'.$template->getAttribute('name').'"]/elements/*') as $item){
			cms::configureField($tRecord->id, $item);
		}
	}

	public static function configureField($templateId, $item){
		switch($item->tagName){

		case 'list':
			$ltRecord = ORM::Factory('template');
			$ltRecord->templatename = $item->getAttribute('family');
			$ltRecord->nodeType = 'container';
			$ltRecord->save();
			break;

		default:
			//handle dbmap
			$index = null;
			switch($item->tagName){
			case 'ipe':
				case 'radioGroup':
					case 'pulldown':
						case 'time':
							case 'date':
								case 'multiSelect':
									$index = 'field';
									break;
								case 'singleImage':
									case 'singleFile':
										$index = 'file';
										break;
									case 'checkbox':
										$index = 'flag';
										break;
									default:
										continue(2);
										break;
			}	

			//and right here it'll be 'if doesn't already exist in the array'
			//or we'll check the database and just insert a new/next one
			//and this is where the ALTER statements could come in

			//safeguard that this field isn't already configured!
			$objectmap = ORM::Factory('objectmap')
				->where('template_id', $templateId)
				->where('column', $item->getAttribute('field'))
				->find();
			if(!$objectmap->loaded){
				//compute new dbmapindex
				$count = ORM::Factory('objectmap')
					->select('max(`index`) as maxIndex')
					->where('type', $index)
					->where('template_id', $templateId)
					->find();
				$nextIndex = $count->maxIndex+1;

				$newmap = ORM::Factory('objectmap');
				$newmap->template_id = $templateId;
				$newmap->type = $index;
				$newmap->index = $nextIndex;
				$newmap->column = $item->getAttribute('field');
				$newmap->save();
			}

		}
	}

}


