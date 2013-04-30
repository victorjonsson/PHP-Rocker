var dokimon = require('dokimon'),
    assert = require('assert'),
    userEmail = 'user'+( new Date().getTime() )+'@test.com',
    userPass = '123456asdf',
    userID = null,
    auth = 'Basic '+ (new Buffer(userEmail+':'+userPass).toString('base64'));


var getUnexistingUser = new dokimon.Test(
    'getUnexistingUser',
    {url : '/user/2322323231121239'},
    function(res, body) {
        assert.equal(res.statusCode, 404);
    }
);


var createUserFailing = new dokimon.TestFormPost(
    'createUserFailing',
    {
        url : '/user',
        method : 'POST',
        write : {email : 'some@mail.com'}
    },
    function(res, body) {
        assert.equal(res.statusCode, 400); // should require more parameters
    }
);


var createUser = new dokimon.TestFormPost(
    'createUser',
    {
        url : '/user',
        method : 'POST',
        write : {email : userEmail, nick :'Nicky', password : userPass}
    },
    function(res, body) {
        assert.equal(res.statusCode, 201, 'Wrong status');
        var user = JSON.parse(body);
        userID = user.id;
        assert.equal(user.email, userEmail, 'Wrong email');
        assert.equal(user.nick, 'Nicky', 'Wrong nick');
        assert.equal(user.meta.created !== undefined, true, 'Wrong meta');
    },
    true
);

var userNameCollision = new dokimon.TestFormPost(
    'userNameCollision',
    {
        url : '/user',
        method : 'POST',
        write : {email : userEmail, nick :'Other nicky', password: 'a password'},
        dependsOn : 'createUser'
    },
    function(res, body) {
        assert.equal(res.statusCode, 409);
    },
    true
);

var updateUserWithInvalidAuth = new dokimon.TestFormPost(
    'updateUserWithInvalidAuth',
    {
        url : '/user/'+userEmail,
        method : 'POST',
        write : 'nick=New+Nick&meta[test]=1&meta[other]=Hola',
        dependsOn: 'createUser',
        headers : {
            Authorization : 'Basic sdlkmwlemwfelkmfwlwem'
        }
    },
    function(res, body) {
        assert.equal(res.statusCode, 401);
    },
    true
);

var updateUser = new dokimon.TestFormPost(
    'updateUser',
    {
        url : '/user/'+userEmail,
        method : 'POST',
        write : 'nick=New+Nick&meta[test]=1&meta[other]=Hola',
        dependsOn: 'createUser',
        headers : {
            Authorization : auth
        }
    },
    function(res, body) {
        assert.equal(res.statusCode, 200);
        var user = JSON.parse(body);
        assert.equal(user.nick, 'New Nick');
        assert.equal(user.meta.test, 1);
        assert.equal(user.meta.other, 'Hola');
    },
    true
);

var createFile = new dokimon.Test(
    'createFile',
    {
        url : '/file/myfile.txt',
        method : 'PUT',
        write : 'tjena',
        dependsOn: 'createUser',
        headers : {
            Authorization : auth
        }
    },
    function(res, body) {
        assert.equal(res.statusCode, 201);
        var file = JSON.parse(body);
        assert.equal(file.extension, 'txt');
        assert.equal(file.size, 5);
        assert.equal(file.name, userID+'/myfile.txt');
    },
    true
);

var createImage = new dokimon.Test(
    'createImage',
    {
        url : '/file/my-image.png?base64_decode=1&versions[thumb]=20x20&versions[medium]=50x0',
        method : 'PUT',
        write : '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',
        dependsOn: 'createFile',
        headers : {
            Authorization : auth
        }
    },
    function(res, body) {
        assert.equal(res.statusCode, 201);
        var file = JSON.parse(body);
        assert.equal(72, file.width);
        assert.equal(72, file.height);
        assert.equal(true, file.versions !== undefined);
        assert.equal('my-image-20x20.png', file.versions.thumb);
        assert.equal('my-image-50x0.png', file.versions.medium);
    },
    true
);

var checkFilesAvailable = new dokimon.Test(
    'checkFilesAvailable',
    {
        url : '/me',
        dependsOn : 'createImage',
        headers : {
            Authorization: auth
        }
    },
    function(res, body) {
        var user = JSON.parse(body);
        console.log(userID);
        assert.equal(true, 'files' in user.meta, 'No files?');
        assert.equal(true, 'myfile.txt' in user.meta.files, 'File missing');
        var f = user.meta.files;
        assert.equal('http://localhost/PHP-Rocker/static/'+userID+'/myfile.txt', f['myfile.txt']['location']);
        assert.equal('http://localhost/PHP-Rocker/static/'+userID+'/my-image.png', f['my-image.png']['location']);
        assert.equal('my-image-20x20.png', f['my-image.png'].versions.thumb, 'No thumb');
    }
);

// todo: create tests for removal and creation of image versions
// todo: create test for deleting a file

var canOnlyDeleteSelf = new dokimon.Test(
    'canOnlyDeleteSelf',
    {
        url : '/user/1', // this test requires that you have a user with id 1 in your database
        method : 'DELETE',
        dependsOn: 'createUser',
        headers : {
            Authorization : auth
        }
    },
    function(res, body) {
        assert.equal(res.statusCode, 401);
        var message = JSON.parse(body);
        assert.equal(message.error.indexOf('Only admins can edit') == 0, true);
    },
    true
);

var deleteUser = new dokimon.Test(
    'deleteUser',
    {
        url : '/user/'+userEmail,
        method : 'DELETE',
        dependsOn: 'updateUser',
        headers : {
            Authorization : auth
        }
    },
    function(res, body) {
        assert.equal(res.statusCode, 204);
    },
    true
);


var isDeletedUserGone = new dokimon.Test(
    'isDeletedUserGone',
    {
        url : '/user/'+userEmail,
        dependsOn: 'deleteUser'
    },
    function(res, body) {
        assert.equal(res.statusCode, 404);
    }
);



module.exports = [
    getUnexistingUser,
    createUserFailing,
    createUser,
    userNameCollision,
    updateUserWithInvalidAuth,
    updateUser,
    createFile,
    createImage,
    checkFilesAvailable,
    canOnlyDeleteSelf,
    deleteUser,
    isDeletedUserGone
];