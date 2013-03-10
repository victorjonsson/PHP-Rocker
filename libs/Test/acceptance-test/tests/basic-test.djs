var dokimon = require('dokimon'),
    assert = require('assert');

var checkVersion = new dokimon.Test(
    'checkVersion',
    {url : '/system/version'},
    function(res, body) {
        assert.equal(res.statusCode, 200);
        assert.equal(JSON.parse(body).version !== undefined, true);
    }
);

var checkOperations = new dokimon.Test(
    'checkOperations',
    {
        url : '/operations'
    },
    function(res, body) {
        assert.equal(res.statusCode, 200);
        var ops = JSON.parse(body);
        assert.equal(ops['system/version'] !== undefined, true, 'Not containing version operation');
    }
);

module.exports = [checkVersion, checkOperations];