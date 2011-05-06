<?

$config['displaycontroller'] = 'DisplayAdmin';
$config['authrole'] = 'admin';
/*
 * Config: ['resources']['css']
 */
$config['resources']['css'] = array( 
	);

/*
 * Config: ['resources']['js']
 */
$config['resources']['js'] = array(
	'modules/cms/views/list.js'
);

/*
 * Config: ['passwordchangeemail']['subject']
 * The subject of the password reset email
 */
$config['passwordchangeemail']['subject'] = 'MoPCMS Password Changed';
$config['passwordchangeemail']['from'] = 'deepwinter@winterroot.net';


/*
 * Config: ['managedRoles']
 * The roles that the admin can select from when creating a user. 
 * Array is of format array( {label} => {role unique text key})
 */
$config['managedRoles'] = array();

//array('Call Center'=>'callcenter', 'Admin'=>'admin');

$config['roleFilters'] = array(
	 'admin'
);

$config['addWithRoles'] = array(
	 'login',
	 'admin',
	 'staging'
);