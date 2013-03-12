# PHP Rocker

Here you have yet another **framework for writing RESTful web services** in PHP, jay! What sets this framework apart 
from many of the others is that Rocker is a bundle of [Slim](https://github.com/codeguy/Slim) and an awesome 
[database facade](https://github.com/fridge-project/dbal). Not trying to write everything from scratch makes it
possible for you to focus on what's important when writing your RESTful API and let other projects 
take care of things like routing and data storage.

#### Features

- User management 
- CRUD operations (use the template classes in Rocker framework to extend the API with your own objects)
- EAV data model
- Built in object cache (supporting both APC and file based caching)
- Interface based (easy to extend and customize)
- Administer your remote Rocker server from the console


#### Read more
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
- MySQL (the database layer supports many other databases as well but Rocker is so far only tested with MySQL)
- Web server (apache/nginx)


## Installation

Here you can see [a screencast](http://www.screenr.com/6ct7) going through the install procedure and how to manage
your Rocker server remotely using the console.

**1) Download and unzip** Rocker into your web folder. Running the following command in the terminal will download the
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
the only ones you probably must edit.


**4) Run install.php** in your console which will setup the database tables and create an admin user. You will be
prompted about what credentials you want to give the admin user.

```
$ php -f install.php
```

**- You're done!**

## API reference

**Get version of Rocker**

```
$ curl http://website.com/api/system/version

{
    "version" : "0.9.1"
}
```

**List available operations**

```
$ curl http://website.com/api/operations

{
    "operations" : {
        "methods":"GET,HEAD",
        "class":"\\Rocker\\API\\ListOperations"
    },
    "system/version" : {
        "methods":"GET,HEAD",
        "class" : "\\Rocker\\API\\Version"
    }
    ...
}
```

**Clear object cache** on remote server

```
$ curl -u 'admin.user@website.com' -X POST http://website.com/api/clear/cache

HTTP/1.1 204 No Content
Date: Tue, 12 Mar 2013 06:18:59 GMT
...
```

**Try to authenticate**, will return information about the authenticated user on success

```
$ curl -u 'admin.user@website.com' http://website.com/api/auth

{
    "id" : 1,
    "email" : "admin.user@website.com",
    "nick" : "Admin",
    "meta" : {
        "admin" : 1
    }
}
```

**Create a new user**. The parameters email, nick and password is mandatory. The meta parameter should be of type array. 
Setting a meta value to `true` or `false` will turn the value into corresponding boolean value.

```
$ curl -X POST http://website.com/api/user -d 'email=some.user@website.com&nick=Nicky&password=secretstuff&meta[country]=Germany'

HTTP/1.1 201 Created
Date: Tue, 12 Mar 2013 06:27:44 GMT
...

{
    "id" : 3,
    "email" : "some.user@website.com",
    "nick" : "Nicky",
    "meta" : {
        "country" : "Germany"
    }
}
```

**Update user data**. Any of the parameters email, nick, password and meta can be given. Meta should be of type array. 
Setting a meta value to `null` will remove the meta data. Setting a meta value to `true` or `false` will turn the 
value into corresponding boolean value. To update a user the client has to authenticate as the user in question or as 
a user that has admin privileges.

```
$ curl -X POST http://website.com/api/user/some.user@website.com -d 'nick=Johnny&meta[country]=Norway&meta[adult]=true'

{
    "id" : 3,
    "email" : "some.user@website.com",
    "nick" : "Johnny",
    "meta" : {
        "country" : "Norway",
        "adult" : true
    }
}
```

**Delete a user**. To delete a user the client has to authenticate as the user in question or as a user that has admin 
privileges. To remove a user that has admin privileges you first have to remove the admin privileges (operation below).

```
$ curl -X DELETE http://website.com/api/user/some.user@website.com 

HTTP/1.1 204 No Content
Date: Tue, 12 Mar 2013 06:18:59 GMT
...
```

**Add or remove admin privileges**. The client has to authenticate as a user that has admin privileges to manage privileges
for other users. An admin user can how ever not remove admin privileges from him self.

```
$ curl -X POST http://website.com/api/admin -d 'user=3&admin=1'

HTTP/1.1 204 No Content
Date: Tue, 12 Mar 2013 06:18:59 GMT
...
```

**Search for users**. Query string examples (parameters needs to be url encoded):

Search for users that has a nick containing John and that comes from France

```
?q[nick]=*john*&q[country]=France
```

Search for users that has a nick containing John and that comes from either France or Norway

```
?q[nick]=*john*&q[country]=France|Norway
```

Search for users that has a nick containing John and that comes from either France or Norway and that has 
a hobby interest containing the word Hockey or Soccer

```
?q[nick]=*john*&q[country]=France|Norway&q[interests]=*hockey*|*soccer*
```

You can also add the parameters `offset` and `limit`

```
$ curl http://website.com/api/users?q[nick]=*john*&q[country]=France|Norway&offset=50&limit=100

{
    "matching" : 234,
    "objects" : [
        {...},
        ...
    ]
}
```

## Manage remote servers via command line

First of move the console program to your bin directory so that you can access it from anywhere on your computer.

`$ sudo ln -s /path/to/your/rocker/installation/console /bin/rocker`

Having done that you add your server (you will be prompted for server address and user credentials)

`$ rocker server`


## Extending the API with more operations

Lorem te ipusm del tara

## A note on security

At the moment PHP-Rocker only supports Basic authentication and RC4 encrypted authentication. You should always run your web services
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

## License

[General Public License v2](http://www.gnu.org/licenses/gpl-2.0.html)

## Road map

- Change from POST to PUT when creating objects
- Write the authentication method as a Slim middleware
- Make it possible to store blob data using PUT
- Look into using another data model for stored objects
