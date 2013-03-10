var dokimon = require('dokimon'),
    assert = require('assert');

var createUser1 = new dokimon.TestFormPost(
    'createUser1',
    {
        url : '/user',
        method : 'POST',
        write : 'email=user@website.com&nick=Jonny&password=111&meta[country]=Sweden'
    },
    function(res, body) {},
    true
);

var createUser2 = new dokimon.TestFormPost(
    'createUser2',
    {
        url : '/user',
        method : 'POST',
        write : 'email=user2@website.com&nick=Benny&password=111&meta[country]=Russia',
        dependsOn : 'createUser1'
    },
    function(res, body) {},
    true
);

var searchTest = new dokimon.Test(
    'searchTest',
    {
        url :'/user?q[nick]=*nny*',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.matching > 1, true);
        assert.equal(json.objects[0].email, 'user2@website.com');
        assert.equal(json.objects[0].nick, 'Benny');
        assert.equal(json.objects[1].email, 'user@website.com');
        assert.equal(json.objects[1].nick, 'Jonny');
    }
);

var searchWithOffset = new dokimon.Test(
    'searchWithOffset',
    {
        url :'/user?q[nick]=*nny*&offset=1',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 1);
        assert.equal(json.objects[0].email, 'user@website.com');
        assert.equal(json.objects[0].nick, 'Jonny');
    }
);


var searchWithMeta = new dokimon.Test(
    'searchWithMeta',
    {
        url :'/user?q[nick]=*nny*&q[country]=Sweden',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 1);
        assert.equal(json.objects[0].nick, 'Jonny');
    }
);

var searchWithMultipleMeta = new dokimon.Test(
    'searchWithMultipleMeta',
    {
        url :'/user?q[nick]=*nny*&q[country]=Sweden|Russia',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 2);
        assert.equal(json.objects[0].nick, 'Benny');
    }
);

var searchWithMultipleMetaAndWildCard = new dokimon.Test(
    'searchWithMultipleMetaAndWildCard',
    {
        url :'/user?q[nick]=*nny*&q[country]=*eden*|Russia',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 2);
        assert.equal(json.objects[0].nick, 'Benny');
    }
);
module.exports = [
    createUser1,
    createUser2,
    searchTest,
    searchWithOffset,
    searchWithMeta,
    searchWithMultipleMeta,
    searchWithMultipleMetaAndWildCard
];