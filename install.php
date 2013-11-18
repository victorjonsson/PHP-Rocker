<?php
/**
 * PHP Rocker - Install
 * ---------------------------------
 * Load this file in the console to install Rocker (php -f install.php)
 *
 * @package rocker/server
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */

// Only accessible via command line
if( !empty($_SERVER['REMOTE_ADDR']) ) {
    die('cli only...');
}

$app_path = getcwd().'/';
$rocker_path = __DIR__.'/';

// Copy files into place
$files = array('index.php file'=>'index.php', 'config file'=>'config.php', 'console file'=>'console');
foreach($files as $desc => $file) {
    if( file_exists($app_path . $file) ) {
        fwrite(STDOUT, '* '.$desc.' file already exist in app path, you may want to copy vendor/rocker/server/'.$file.' manually to your application'.PHP_EOL);
    } else {
        copy($rocker_path.$file, $app_path.$file) or die('Unable to copy vendor/rocker/server/'.$file.' to '.$app_path.$file);
    }
}

// Load and validate config
$config = require $app_path.'config.php';
if( !is_array($config) ) {
    fwrite(STDOUT, 'config.php seems corrupt, Rocker expects the file to return an array, but it does not'.PHP_EOL);
    die;
}
else {
    // Check that we have edited the config
    $db_params = array('host', 'dbname', 'username');
    foreach($db_params as $param) {
        if( empty($config['application.db'][$param]) ) {
            fwrite(STDOUT, "\033[31m! Config paramater [application.db][".$param.'] is empty.'.PHP_EOL.'Have you updated '.$app_path."config.php with your own database settings?\033[0m".PHP_EOL);
            die;
        }
    }
}

// Load cli utilities and vendor libraries
require $app_path.'vendor/autoload.php';
//require $app_path.'vendor/jlogsdon/cli/lib/cli/cli.php';
\cli\register_autoload();

// Shorthand for \cli\line()
if( !function_exists('_') ) {
    function _($str) { \cli\line($str); }
}

// Check that we can connect
$db = \Rocker\Object\DB::instance($config['application.db']);
try {
    $tables = $db->executeQuery('SHOW TABLES')->fetchAll();
} catch(\Exception $e) {
    _('%rERROR: Database connection failed%n');
    _($e->getMessage());
    return;
}

// Run class installations
foreach($config['application.install'] as $class) {
    /* @var \Rocker\Utils\InstallableInterface $obj */
    $obj = new $class($db);
    if( $obj->isInstalled() ) {
        _('* '.$class.' already installed');
    } else {
        $obj->install();
        _('- installed '.$class);
    }
}

$userFactory = new \Rocker\Object\User\UserFactory($db);

// todo: check if it exists an admin
$hasAdmin = $userFactory->metaSearch(array('admin'=>1))->getNumMatching() > 0;

// Ask for e-mail
_('## Create admin user');
while( empty($email) ) {
    $email = Rocker\Console\Utils::promptAllowingEmpty('E-mail');
    if( empty($email) && !$hasAdmin ) {
        _('%rYou must create an admin user%n');
    }
    elseif( empty($email) && $hasAdmin ) {
        $email = 'skip';
    }
    else {
        if( filter_var($email, FILTER_VALIDATE_EMAIL) === false ) {
            _('%rNot a valid e-mail%n');
            $email = null;
        }
    }
}

// Create admin user
if( $email != 'skip' ) {
    $nick = \cli\prompt('Nick name');
    $password = \Rocker\Console\Utils::promptPassword('Password: ');
    $user = $userFactory->createUser($email, $nick, $password);
    $userFactory->setAdminPrivileges($user, true);
}

_('%gRocker Server v'.\Rocker\Server::VERSION.' was successfully installed :) %n');