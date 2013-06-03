<?php
/**
 * PHP Rocker - Install
 * ---------------------------------
 * Load this file in the console to install Rocker (php -f install.php)
 *
 * @package Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */

// Only accessible via command line
if( !empty($_SERVER['REMOTE_ADDR']) ) {
    die('cli only...');
}

// Load cli utilities and vendor libraries
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/vendor/jlogsdon/cli/lib/cli/cli.php';
\cli\register_autoload();

// Shorthand for \cli\line()
function _($str) { \cli\line($str); }

// Load config
$config = require __DIR__.'/config.php';

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