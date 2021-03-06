<?
class RouteVirtualModulesHook{
	public static function RouteVirtualModules(){

		if(Router::$controller){
			return;
		}

		Kohana::log('info', 'Attempting Virtual Module Routing');

		$virtualModule = Router::$rsegments[0];

		//what we'll try doing is to look up the class in cms.php and create the module
		$modules = array();
		foreach(mop::config('objects', '//template/elements/*') as $module){
			if($module->tagName=='module'){ 
				//there could be other type of virtual modules
				//this is a total hack
				$modules[$module->getAttribute('modulename')] = $block;
			} else if ($module->tagName=='list'){
			//	$modules[$module->getAttribute('class')] = $block;
				$modules[$module->getAttribute('family')]['controllertype'] = 'list';
			}
		}

		if(in_array($virtualModule, array_keys($modules) )){
			$module = $modules[$virtualModule];
			$includeclass = 'class '.$virtualModule.'_Controller extends '.$module['controllertype'].'_Controller { } ';
			eval($includeclass);

			Router::$controller = $virtualModule;
			Router::$controller_path = 'modules/mopmvc/controllers/virtual.php';
			Router::$method = Router::$rsegments[1];
			$segments = array_slice(Router::$rsegments, 2);
			Router::$arguments = $segments;
			Kohana::log('info', 'Routed Virtual Module');

		}

	}

}

//add this event at the end of the routing chain


