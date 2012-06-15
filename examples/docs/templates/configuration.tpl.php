@body>
<div class="section_title">
Configuration over Convention
</div>
<p>While some frameworks are based around a "naming" convention to work, <?php echo Content::$page->sitename;?> opts for configuration over convention. 
There is no naming convention to learn where things magically work, but only if named properly.
<?php echo Content::$page->sitename;?> relies on a base configuration to get started, 
and then other configuration files are loaded as needed, 
based on a defined inheritance hierarchy and what is requested.
</p>
<p>
Before initializing the framework, certain directories need to be defined in constants so the framework knows where to look for the required files.
By configuring specific locations, the overhead of checking multiple include directories is avoided.
These directories contain the files you create, external to the framework, that the framework loads and processes.
These files include routes, templates and layouts.
Before loading <code>init.php</code>, define the following constants:
<ul>
<li>BC_CONFIGS - configuration directory</li>
<li>BC_ROUTES - files that map to specific URL requests</li>
<li>BC_TEMPLATES - template files used by routes to merge data with for output</li>
<li>BC_LAYOUTS - common page layouts that templates are merge with for output</li>
</ul>

Below is a typical example of how to define the required constants for <?php echo Content::$page->sitename;?> to use.
This configuration is valid for the directory structure example from the Home page documentation, and would typically be place in your site index file.
<pre>
// define current working directory as the site directory
define('SITE_DIR', realpath('../').'/');

define('BC_CONFIGS', SITE_DIR . 'config/');
define('BC_ROUTES', SITE_DIR . 'routes/');
define('BC_TEMPLATES', SITE_DIR . 'templates/');
define('BC_LAYOUTS', BC_TEMPLATES . 'layouts/');

// Load core initialization
include_once( '../../../basecoat/init.php');
</pre>
</p>
That is all that is required to load and initialize the <?php echo Content::$page->sitename;?> framework.
Next we will explain how to create the configuration files and routes to be used by the framework.


<div class="section_title">
Configuration Files
</div>
<p>
A fully configured <?php echo Content::$page->sitename;?> installation requires a total of 2 configuration files.
One file is the base configuration (config.php) and the other is the web specific configuration (config.web.php).
</p>
<div class="subsection_title">
config.php
</div>
<p>
This file is the common configuration file that is loaded regardless of the type of running mode (web, cli).
<code>config.php</code> will load common classes, like database and services configurations.
This is an example of what a typical <code>config.php</code> may look like, including configuration for the optional database class bundled with the framework:
</p>
<pre>
// Set default timezone
date_default_timezone_set('UTC');

// Configure database connections
Config::$settings->db = array(
	0 => array(
		'host' => 'localhost', 'db' => 'default_db', 
		'username' => 'db_user', 'password' => 'db_password', 
		'label'=>'master'),
	1 => array(
		'host' => 'localhost', 'db' => 'default_db', 
		'username' => 'db_user', 'password' => 'db_password', 
		'label'=>'slave1'),
	2 => array(
		'host' => 'localhost', 'db' => 'default_db', 
		'username' => 'db_user', 'password' => 'db_password', 
		'label'=>'slave2'),
);
// Specify which config is the master and which slave db server to use.
// This example show how to use a random slave when more than one is available
Config::$settings->dbmaster_id	= 0;
Config::$settings->dbslave_id	= mt_rand(1,2);

/***
Once all standard configurations are set, load any overrides based on environment
***/

//
// Check what mode we are in, load dev config overrides
if ( Config::$run_env=='dev' ) {
	Core::$profiling_enabled	= true;
	if ( file_exists(BC_CONFIGS . 'config.dev.php') ) {
		require_once(BC_CONFIGS . 'config.dev.php');
	}
}

// Load and initialize the database class
require_once(BASECOATDIR . 'classes/db.pdo.php');

DB::setServerConfig(Config::$settings->db, Config::$settings->dbmaster_id);
Core::$bc->db 	= DB::getServerInstance(Config::$settings->dbslave_id);
</pre>

<div class="subsection_title">
config.web.php
</div>
This is the core web configuration file which contains web specific settings, most importantly the routing map.
This file is loaded by the <code>router.php</code> file and defines the request routing paths.
Default routes, layouts and includes are defined here.
The "routes" configuration array defines which files handle which requests and what layout and templates should be used for the route.
</p>
<p>
Refer to the section on Routes for a detailed explanation on how to setup this file.
</p>

<div class="section_title">
URL Structure &amp; Mapping
</div>
<p>
Keeping flexibility in mind, parameter based URLs or server side URL rewrite rules can be used. 
You don't have to choose one over the other, the framework can toggle between them by just changing the value of a variable.
This allows development using URL parameters and production using URL rewrite rules.
</p>
<p>
<div class="subsection_title">
Parameter Based URLs
</div>
The default URL parameter that specifies the routes is called "page". 
This is configureable in <code>config.web.php</code> by the variable <code>Config::$route_param</code>.
Multiple routes/subroutes can be specified by separating the route names with periods.
<br />
For example, to specify the configuration route and the settings subroute:
<pre>
http://hostname.com/?page=configuration.settings
</pre>
</p>

<p>
<div class="subsection_title">
Rewrite Based URLs
</div>
To specify the same configuration route and settings subroute, use the / character in the URL.
<br />
For example:
<pre>
http://hostname.com/configuration/settings
</pre>
</p>
<div class="subsection_title">
URL Mapping
</div>
<p>
Once the URL is parsed, and the requested route is determined, the route list is checked. 
If there is no matching route found, the /templates/static directory will be checked for a matching file.
The .html suffix will be appended to the requested route, and a file check will be performed.
If a matching file is found, it will be loaded under the default html route and framed in the layout specified for the html route.
This behavior allows you to open up the /templates/static directory to designers, marketing or other groups 
for creating basic, static html pages without granting access to code and possibly "breaking the site".
</p>
<p>
All of the code for parsing the URL and setting up the routes is contained in the <code>url_parser.php</code> file.
If you require your own special URL setup, you can modify it, or load your own file, to fit your needs without having to
make extensive changes to the framework code.
</p>

<div class="section_title">
Processing Hooks
</div>
<p>
There are 3 processing hooks available for including files at various points of processing.
Hooks are available for before routes are run, templates to include after all routes are run, and after page output.
</p>
<div class="subsection_title">
Config::$include_before
</div>
<p>
This array contains a list of files that should be included before any routes are run.
Typically some logic would be placed in the config.web.php file to determine what additional files should be included.
For example, if running and A/B test, a user's bucket can be determined the appropriate files an be included that would override default route settings, templates, etc.
</p>

<div class="subsection_title">
Config::$include_before
</div>
<p>
Template files to load after all routes have been run.
Typically these will be common header, footer, navigation, etc.
Routes can modified and/or override this list to alter final output.
</p>

<div class="subsection_title">
Config::$include_after_output
</div>
<p>
This array contains a list of files that should be included after page output.
Since the page output has been completely, this can be longer running processes that would normal delay page delivery.
Typically and logging would be registered to occur here.
</p>

