# PHP-Rocker

Here you have yet another **framework for writing RESTful web services** in PHP, jay! What sets this framework apart 
from many of the others is that PHP-Rocker is a bundle of [Slim](https://github.com/codeguy/Slim) and an awesome 
[database facade](https://github.com/fridge-project/dbal). Not trying to write everything from scratch makes it
possible for you to focus on what's important when writing your RESTful API and let other projects
take care of things like routing and data storage.

#### Features

- **User management** 
- **CRUD operations** *Use the base classes in PHP-Rocker to extend the API with your own objects and operations*
- **EAV data model**
- **Static file storage** *With support for image manipulation and storage on [Amazon S3](https://github.com/victorjonsson/PHP-Rocker/wiki/File-storage-on-Amazon-S3)*
- **Built in object cache** *With support for APC and file based caching*
- **Interface based** *Easy to extend and to customize*
- **Administer your remote Rocker server from the console**


#### Read more
- [System requirements](#system-requirements)
- [Installation](#installation)
- [API reference](#api-reference)
- [Extending the API with more operations](#extending-the-api-with-more-operations)
- [Manage remote servers via command line](#manage-remote-servers-via-command-line)
- [A note on security](#a-note-on-security)
- [Unit and acceptance testing](#unit-and-acceptance-testing)
- [License](#license)
- [Road map](#road-map)


#### Additional packages
- [Facebook login](https://github.com/victorjonsson/PHP-Rocker-facebook-login) Integrate your PHP-Rocker server with Facebook
- [Google login](https://github.com/victorjonsson/PHP-Rocker-google-login) Enable authenticated request with user credentials from Google

## System requirements

- **PHP v >= 5.3.2**
- **MySQL** *The database layer has support several different databases (oracle, mssql, postgresql...) but PHP-Rocker is so far only tested with MySQL*
- **Web server (apache/nginx)** *If using nginx the .htaccess rewrite rules has to be moved to the rewrite configuration of the server* 


## Installation

**1) Download and unzip** PHP-Rocker into your web folder. Running the following command in the terminal will download the
latest version of PHP-Rocker and unzip it to a directory named rocker.

```
$ wget https://github.com/victorjonsson/PHP-Rocker/archive/master.zip && unzip master.zip -d . && mv PHP-Rocker-master rocker
```

**2) Run composer install** in the rocker directory. Navigate to the directory "rocker" (created on step 1) in the terminal
and run the following command (here's how to [install composer](http://getcomposer.org/doc/00-intro.md#installation-nix) if
you haven't already done so).

```
$ composer install
```

**3) Edit config.php**. There's a lot of things you can configure if you want to but the database parameters is
the only thing you probably must edit. If you want to support file storage you also need to 
edit 'application.files' => 'base' to the URL of your static files directory


**4) Run install.php** in your console which will setup the database tables and create an admin user. You will be
prompted about what credentials you want to give the admin user.

```
$ php -f install.php
```

**- You're done!**

## API reference

The API reference has moved to its own [wiki page](https://github.com/victorjonsson/PHP-Rocker/wiki/API-Reference).

## Extending the API with more operations

Here you can [read more about how to create custom operations](https://github.com/victorjonsson/PHP-Rocker/wiki/Creating-a-custom-operation)

## Manage remote servers via command line

First of move the console program to your bin directory so that you can access it from anywhere.

```
$ sudo ln -s /path/to/your/rocker/installation/console /bin/rocker
```

Having done that you add your server (you'll be prompted for server address and user credentials)

```
$ rocker server
```

Search for users

```
# Find users with nick containing John
$ rocker users -q 'nick=*John*'

# Find users coming from either France or Germany that is not admin
$ rocker users -q 'country=France|Germany&admin!=1'

# Find users that has a score greater than 80 and that has a description
# containing either "hockey" or "soccer"
$ rocker users -q 'score>=80&description=*hockey*|*soccer*'

```

Load user data

```
$ rocker users -l john.doe@website.com
```

You can also create, delete and update the users using the console program. Run `rocker` in the console to get more help.

## A note on security

PHP-Rocker supports basic authentication and RC4 encrypted authentication out of the box. You should always run your web services
on a SSL cert when handling business/user data, especially if you're using basic authentication. The RC4 encrypted authentication
works basically the same as basic authentication except that the user credentials is encrypted on the client and decrypted on the
server using a shared secret. If wanting to run RC4 encrypted requests you'll need to modify the parameter `application.auth` in config.php.

```
'application.auth' => array(
    'class' => '\\Rocker\\REST\\Authenticator',
    'mechanism' => 'RC4 realm="your.service.com"',
    'secret' => 'what-ever-hard-to-guess-string-you-want'
)
```

The *secret* has then to be given to the client communicating with your Rocker server. Pseudo code:

```java
String crypted = RC4.encrypt("the-hard-to-guess-secret", "som.user@gmail.com:some-password");
crypted = Base64.encode(crypted);
request.addHeader("Authorization", "RC4 "+crypted);
```

#### Other authentication packages

- [Facebook login](https://github.com/victorjonsson/PHP-Rocker-facebook-login) Integrate your PHP-Rocker server with Facebook
- [Google login](https://github.com/victorjonsson/PHP-Rocker-google-login) Enable authenticated request with user credentials from Google


## Unit and acceptance testing

To run the unit tests of Rocker navigate to libs/Test and run [phpunit](http://www.phpunit.de/manual/current/en/installation.html#installation.phar). You can
also run acceptance tests on your entire infrastructure using the [dokimon tests](https://github.com/victorjonsson/PHP-Rocker/tree/master/libs/Test/acceptance-test)

## License

[MIT license](http://opensource.org/licenses/MIT)

## Road map

- ~~Support file storage on Amazone S3~~
- Write the authentication method as a Slim middleware
- ~~Make it possible to store blob data using PUT~~
- Look into using another data model for stored objects
- Look into supporting noSQL instead of a relational database
- Rewrite the console program as a phar and move the source code to a separate project


## Changelog

#### 1.1.8
- Trying to update a user with an already registered e-mail address now results in http status 409 instead of 400
- Abstract object factories now implements InstallableInterface
- Improved install script, all classes that should run install when app is installed is now declared in config.php
- Added configuration options for user management and file storage

#### 1.1.7
- General improvements in PHP-Rocker\Object\ObjectMetaFactory
- Fixed bug that made the code to iterate over null variable
- Replaced logic for image manipulation with Gregwar/Image
- Fixed bug that sometimes made the console program to crash

#### 1.1.6
- Added support for file storage
- Added functionality for image manipulation