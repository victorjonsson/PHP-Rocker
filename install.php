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

// Check that tables don't already exists
$userFactory = new \Rocker\Object\User\UserFactory($db);
$table = $config['application.db']['prefix'].$userFactory->objectTypeName();
$foundTable = false;
foreach($tables as $t) {
    if( $t[0] == $table ) {
        $foundTable = true;
        break;
    }
}
if( !$foundTable ) {
    _('- Creating user tables');
    $userFactory->install();
} else {
    _('* Database tables already exists');
}

// todo: check if it exists an admin

// Ask for user credentials
_('## Create admin user');
$email = \cli\prompt('E-mail');
while( filter_var($email, FILTER_VALIDATE_EMAIL) === false ) {
    _('%rNot a valid e-mail%n');
    $email = \cli\prompt('E-mail');
}
$nick = \cli\prompt('Nick name');
$password = \Rocker\Console\Utils::promptPassword('Password: ');

// Create admin user
$user = $userFactory->createUser($email, $nick, $password);
$userFactory->setAdminPrivileges($user, true);

_('%gRocker Server v'.\Rocker\Server::VERSION.' was successfully installed :) %n');