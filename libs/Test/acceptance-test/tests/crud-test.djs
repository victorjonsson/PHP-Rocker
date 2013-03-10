var dokimon = require('dokimon'),
    assert = require('assert'),
    userEmail = 'user'+( new Date().getTime() )+'@test.com',
    userPass = '123456asdf',
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
        assert.equal(user.email, userEmail);
        assert.equal(user.nick, 'Nicky');
        assert.equal(user.meta ? user.meta.length : false, 0);
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
    canOnlyDeleteSelf,
    deleteUser,
    isDeletedUserGone
];