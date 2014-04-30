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
        url : '/system/operations'
    },
    function(res, body) {
        assert.equal(res.statusCode, 200, 'Invalid status code...');
    }
);

module.exports = [checkVersion, checkOperations];