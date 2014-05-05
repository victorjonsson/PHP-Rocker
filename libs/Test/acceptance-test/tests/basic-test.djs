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
        assert.equal(res.headers['content-type'].indexOf('/json') > -1, true);
        assert.equal(res.statusCode, 200, 'Invalid status code...');
    }
);

var checkOperationsXML = new dokimon.Test(
    'checkOperationsXML',
    {
        url : '/system/operations.xml'
    },
    function(res, body) {
        assert.equal(res.headers['content-type'].indexOf('/xml') > -1, true);
        assert.equal(res.statusCode, 200, 'Invalid status code...');
    }
);

module.exports = [checkVersion, checkOperations, checkOperationsXML];