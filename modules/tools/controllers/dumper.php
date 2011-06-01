<?

class dumper_Controller extends Controller {


  public function __construct(){
		if(!is_writable('application/views/xmldumps/')){
		//	die('application/views/xmldumps/ must be writable');
		}
	}

  private function getObjectFields($object){
    $fields = array();
    $content = $object->getContent();
    foreach($content as $key=>$value){
      if($key=='templateName'){
        continue;
      }
      $field = $this->doc->createElement($key);
      //print_r($value);
      if(is_array($value)){

      } else if(is_object($value)){
        switch(get_class($value)){
        case 'File_Model':
          //or copy to directory and just use filename
          $field->appendChild( $this->doc->createTextNode($value->fullpath));
          break;
        case 'Page_Model':
          foreach($this->getObjectFields($value) as $subField){
            $field->appendChild($subField);
          }
          break;
        }
      } else {
        $field->appendChild( $this->doc->createTextNode($value));
      }
      $fields[] = $field;
    }
    return $fields;
  }

	public function export($xslt=''){
		//get directory listing of application/media

    $this->doc = new DOMDocument('1.0', 'UTF-8');
    $this->doc->formatOutput = true;

    $objects = ORM::Factory('page')
      ->where('activity IS NULL')
      ->where('published', 1)
      ->where('parentid', 0)
      ->find_all();

    foreach($this->exportTier($objects) as $item){
      $this->doc->appendChild($item);
    }
    

    $this->doc->save('application/media/export.xml');

 
	}
   
   private function getObjectFieldsMOPFormat($object){
    $fields = array();
    $content = $object->getContent();
    foreach($content as $key=>$value){
      if($key=='templateName' || $key == 'dateadded'){
        continue;
      }
			if($key=="slug" && $value=""){
				continue;
			}
      $field = $this->doc->createElement('field');
			$fieldAttr = $this->doc->createAttribute('name');
			$fieldValue = $this->doc->createTextNode($key);
			$fieldAttr->appendChild($fieldValue);
			$field->appendChild($fieldAttr);
      //print_r($value);
      if(is_array($value)){

      } else if(is_object($value)){
        switch(get_class($value)){
        case 'File_Model':
          //or copy to directory and just use filename
					if($value->fullpath){
						$field->appendChild( $this->doc->createTextNode($value->fullpath));
					}
          break;
        case 'Page_Model':
          foreach($this->getObjectFields($value) as $subField){
            //$field->appendChild($subField);
						echo "sub objects not yet supported for mop export\n";
          }
          break;
        }
      } else {
        $field->appendChild( $this->doc->createTextNode($value));
      }
      $fields[] = $field;
    }
    return $fields;
  }

  private function exportTier($objects){

    $nodes = array();
    foreach($objects as $object){
      $item = $this->doc->createElement($object->template->templatename);

      foreach($this->getObjectFields($object) as $field){
        $item->appendChild($field);
      }

      //and get the children
      $childObjects = ORM::Factory('page')
        ->where('activity IS NULL')
        ->where('published = 1')
        ->where('parentid', $object->id)
        ->find_all();
      foreach($this->exportTier($childObjects) as $childItem){
        $item->appendChild($childItem);
      }
      $nodes[] = $item;
    }

    return $nodes;
  }

  private function exportTierMOPFormat($objects){

    $nodes = array();
    foreach($objects as $object){
      $item = $this->doc->createElement('item');
			$templateAttr = $this->doc->createAttribute('templateName');
			$templateValue = $this->doc->createTextNode($object->template->templatename);
			$templateAttr->appendChild($templateValue);
			$item->appendChild($templateAttr);

      foreach($this->getObjectFieldsMOPFormat($object) as $field){
        $item->appendChild($field);
      }

      //and get the children
      $childObjects = ORM::Factory('page')
        ->where('activity IS NULL')
        ->where('published = 1')
        ->where('parentid', $object->id)
        ->find_all();
      foreach($this->exportTierMOPFormat($childObjects) as $childItem){
        $item->appendChild($childItem);
      }
      $nodes[] = $item;
    }

    return $nodes;
  }


	public function exportMOPFormat($xslt=''){
		//get directory listing of application/media

    $this->doc = new DOMDocument('1.0', 'UTF-8');
    $this->doc->formatOutput = true;
		$data = $this->doc->createElement('data');

    $objects = ORM::Factory('page')
      ->where('activity IS NULL')
      ->where('published', 1)
      ->where('parentid', 0)
      ->find_all();

    foreach($this->exportTierMOPFormat($objects) as $item){
      $data->appendChild($item);
    }
		$this->doc->appendChild($data);
    

    $this->doc->save('application/media/export.xml');

	}


}
