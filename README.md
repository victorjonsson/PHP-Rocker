# PHP Rocker

Here you have yet another **framework for writing RESTful web services** in PHP, jay! What sets this framework apart from many
of the others is that Rocker is a bundle of Slim and an awesome [database facade](https://github.com/fridge-project/dbal). Not trying to write everything
from scratch makes it possible to focus on what's important when writing a RESTful API and let other projects take care of
things like routing and data storage.

- [System requirements](#system-requirements)
- [Installation](#installation)
- [API reference](#api-reference)
- [Manage remote servers via command line](#manage-remote-servers-via-command-line)
- [Extending the API with more operations](#extending-the-api-with-more-operations)
- [A note on security](#a-note-on-security)
- [License](#license)
- [Road map](#road-map)

## System requirements

- PHP v >= 5.3.2
- MySQL (or postgreSQL, MsSQL)


## Installation

**1) Download and unzip** rocker.zip in your web folder. Running the following command in the terminal will download the
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

**3) Edit config.php**. There's a lot of things you can configure if you want to. But the database parameters is the only one
you must edit.


**4) Run install.php** in your console which will setup the database tables and create an admin user. You will be
prompted about what credentials you want to give the admin user

```
$ php -f install.php
```

**- You're done!**

## API reference

Lorem te ipsum...


## Manage remote servers via command line

First of move the console program to your bin directory so that you can access it from anywhere on your computer.
`$ sudo ln -s /path/to/your/rocker/installation/console /bin/rocker`


## Extending the API with more operations

Lorem te ipusm del tara

## A note on security

At the moment PHP-Rocker only supports Basic authentication and RC4 encrypted authentication. You should always run your web services
on a SSL cert when handling business/user data, especially if you're using basic authentication. The RC4 encrypted authentication
works basically the same as basic authentication except that the user credentials is encrypted on the client and decrypted on the
server using a shared secret. If wanting to run RC4 encrypted requests you'll need to modify parameter `application.auth` in config.php.

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

## License

[General Public License v2](http://www.gnu.org/licenses/gpl-2.0.html)

## Road map

- Change from POST to PUT when creating objects
- Write the authentication method as a Slim middleware
- Make it possible to store blob data using PUT
- Look into using another data model for stored objects