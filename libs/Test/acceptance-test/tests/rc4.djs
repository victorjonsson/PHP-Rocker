
var auth = 'RC4 sIPHreAGTrTnn7oOkCWBCEneNmeZoQZZwHGP', // This test requires that test server has auth secret "some hard to guess string"
    dokimon = require('dokimon'),
    assert = require('assert'),
    userEmail = 'someuser@website.com',
    userPass = 'secret';


var createRc4User = new dokimon.TestFormPost(
    'createRc4User',
    {
        url : '/user',
        method : 'POST',
        write : 'email='+userEmail+'&nick=Cool+dude&password='+userPass
    },
    function(res, body) {},
    true
);


var tryRC4Auth = new dokimon.Test(
    'tryRC4Auth',
    {
        url :'/me',
        method : 'GET',
        dependsOn: 'createRc4User',
        headers : {
            Authorization : auth
        }
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal('Cool dude', json.nick);
        assert.equal(userEmail, json.email);
    }
);

module.exports = [createRc4User, tryRC4Auth];