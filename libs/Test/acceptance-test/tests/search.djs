var dokimon = require('dokimon'),
    assert = require('assert');

var createUser1 = new dokimon.TestFormPost(
    'createUser1',
    {
        url : '/user',
        method : 'POST',
        write : 'email=user@website.biz&nick=HejjaSulla&password=111&meta[country]=Sweden'
    },
    function(res, body) {},
    true
);

var createUser2 = new dokimon.TestFormPost(
    'createUser2',
    {
        url : '/user',
        method : 'POST',
        write : 'email=user2@website.biz&nick=HajjaBulla&password=111&meta[country]=Russia',
        dependsOn : 'createUser1'
    },
    function(res, body) {},
    true
);

var searchTest = new dokimon.Test(
    'searchTest',
    {
        url :'/user?q[nick]=*jja*',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.matching > 1, true);
        assert.equal(json.objects[0].email, 'user2@website.biz');
        assert.equal(json.objects[0].nick, 'HajjaBulla');
        assert.equal(json.objects[1].email, 'user@website.biz');
        assert.equal(json.objects[1].nick, 'HejjaSulla');
    }
);

var searchTestWithNot = new dokimon.Test(
    'searchTestWithNot',
    {
        url :'/user?q[nick]=HejjaSulla|HajjaBulla&q[country!]=Russia',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.matching, 1);
        assert.equal(json.objects[0].nick, 'HejjaSulla');
    }
);

var searchWithOffset = new dokimon.Test(
    'searchWithOffset',
    {
        url :'/user?q[nick]=*jja*&offset=1',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 1);
        assert.equal(json.objects[0].email, 'user@website.biz');
        assert.equal(json.objects[0].nick, 'HejjaSulla');
    }
);


var searchWithMeta = new dokimon.Test(
    'searchWithMeta',
    {
        url :'/user?q[nick]=*jja*&q[country]=Sweden',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 1);
        assert.equal(json.objects[0].nick, 'HejjaSulla');
    }
);

var searchWithMultipleMeta = new dokimon.Test(
    'searchWithMultipleMeta',
    {
        url :'/user?q[nick]=*jja*&q[country]=Sweden|Russia',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 2);
        assert.equal(json.objects[0].nick, 'HajjaBulla');
    }
);

var searchWithMultipleMetaAndWildCard = new dokimon.Test(
    'searchWithMultipleMetaAndWildCard',
    {
        url :'/user?q[nick]=*jja*&q[country]=*eden*|Russia',
        method : 'GET',
        dependsOn: 'createUser2'
    },
    function(res, body) {
        var json = JSON.parse(body);
        assert.equal(json.objects.length, 2);
        assert.equal(json.objects[0].nick, 'HajjaBulla');
    }
);
module.exports = [
    createUser1,
    createUser2,
    searchTest,
    searchWithOffset,
    searchWithMeta,
    searchWithMultipleMeta,
    searchWithMultipleMetaAndWildCard,
    searchTestWithNot
];