<?

/*
 * Class: Display_Controller
 * Base class for complete webpage assembly in mop system
 */
class Display_Controller{

	/*
	 * Variable: modules
	 * The other modules to build and supply to the webpage layout template
	 */
	protected $modules = array();

	/*
	 * Variable: view 
	 * The base name of the layout template to use
	 */
	protected $view = 'default';

	/*
	 * Variable: resources
	 * This array contains all js and css that is needed by the rendered modules.  As a 
	 * rule 'library' files load before non-library files
	 */
	public static $resources = array(
		'js'=>array(),
		'libraryjs'=>array(),
		'css'=>array(),
		'librarycss'=>array(),
	);

	/*
	 * Variable: primaryId
	 * Private variable containing the id of the main object (page) being view
	 */
	private static $primaryId;

	/*
	 * Variable: jsblocks
	 * Private variable used for global tracking of dynamically generated js
	 */
	private static $jsblocks;


	/*
	 * Function: __construct()
	 * Constructor sets up webpage layout DisplayView object, initializes primaryId, 
	 * and turns profiler on if requested in config
	 */
	public function __construct(){
		$this->view = new DisplayView($this->view);
		$this->view->loadResources();

		if(file_exists('application/config/local.php') && Kohana::config('local.profiler')){
			$this->profiler = new Profiler;
		}
	}

	/*
		Function: addResources
		adds all resources passed in array to global resource array
		Parameters:
		$resources - an array of resources organized by type 
		array('js'=>array(), 'css'=>array(), 'libraryjs'=> ... etc)
	*/
	public static function addResources($resources){
		foreach($resources as $type => $paths){
			if(!is_array($paths) || !count($paths)){
				continue;
			}
			//$paths should be checked for valid files
			if(count(self::$resources[$type])){
				$flip1 = array_flip(array_values($paths));
				$flip2 = array_flip(array_values(self::$resources[$type]));
				$allResources = $flip1 + $flip2;
				self::$resources[$type] = array_keys($allResources);
			} else {
				self::$resources[$type] = array_values($paths);
			}
		}
	}

	/*
	 * Function addJSBlock($block)
	 * This function can be used to add to a global text variable of dynamically generated js
	 * Parameters:
	 * $block - js text
	 * Returns: nothing
	 */
	public static function addJSBlock($block){
		self::$jsblocks .= $block;
		self::$jsblocks .= "\n";
	}

	/*
	 * Function: getJSBlock 
	 * Returns the global string of jsblocks
	 * Parameters: none
	 * Returns: The value of local variable $jsblocks
	 */
	public static function getJSBlock(){
		return self::$jsblocks;
	}

	/*
	 * Function: getJS()
	 * Returns assembled of array of javascript files requested by modules
	 * over the course of the building the page.  'libraryjs' is prepended to the array, followed by 
	 * 'js' resources.
	 * Parameters: none
	 * Returns: Array of javascript files
	 */
	public static function getJS(){
		Kohana::log('info', var_export(self::$resources, true));
		$js = array_merge(self::$resources['libraryjs'], self::$resources['js']);
		$sendjs = array();
		foreach($js as $ajs){
			$sendjs[] = $ajs;
		}
		return $sendjs;
	}


	/*
	 * Function: getCSS()
	 * Returns assembled of array of css files requested by modules
	 * over the course of the building the page.  'librarycss' is prepended to the array, followed by 
	 * 'css' resources.
	 * Parameters: none
	 * Returns: Array of javascript files
	 */
	public static function getCSS(){
		$css = array_merge(self::$resources['librarycss'], self::$resources['css']);
		$sendcss = array();
		foreach($css as $acss){
			$sendcss[] = $acss;
		}
		return $sendcss;
	}

	/*
	 * Function: buildModules()
	 * Build all sub-modules and set variables in template via buildModule()
	 * Parameters: none, uses $this->modules
	 * Returns: nothing, sets variables on $this->view
	*/
	public function buildModules(){
		foreach($this->modules as $templatevar => $module){
			if(is_int($templatevar)){
				$templatevar = $module;
			}
			$this->view->$templatevar = mop::buildModule(array('modulename'=>$module));
		}
	}

	/*
	 * Function: outputPage($mainview) 
	 * Final call to convert displayview to html webpage
	 * Parameters:
	 * $mainview - the view of the content area of the rendered webpage
	 * Returns: nothing, renders webpage to browser
	 */
	public function outputPage($mainview){
		$this->view->content = $mainview->render();
		//set the overall ID for this view (defines what data we are looking at)
		$this->view->render(TRUE);
	}

	/*
	* Function: buildModule
	* Private function to build a single submodule of this module
	* Parameters:
	* $modulename - The name of the module to build
	* $templatevar - The variable to assign the rendered output to in the webpage layout
	* Returns: nothing, assigns rendered output to webpage layout template variable
	*/
	private function buildModule($modulename, $templatevar=NULL){
		Kohana::log('debug', 'Loading module: ' . $modulename);
		if((Kohana::find_file('controllers', $modulename)) !== FALSE){
			Kohana::log('debug', 'Loading controller: ' . $modulename);
			$fullname = $modulename.'_Controller';
			$module = new $fullname();
			$module->createIndexView();
			if($templatevar==NULL){
				$this->view->$modulename = $module->view->render();
			} else {
				$this->view->$templatevar = $module->view->render();
			}
		} else {
			//just load the view
			Kohana::log('debug', 'No Controller, just Loading View: ' . $modulename);
			$view = new View($modulename);	
			if($templatevar==NULL){
				$this->view->$modulename = $view->render();
			} else {
				$this->view->$templatevar = $view->render();
			}
		}

	}



}


?>
