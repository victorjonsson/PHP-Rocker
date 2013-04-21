
var Rocker = require('./rocker.js');

var server = new Rocker('http://localhost/PHP-Rocker/api/');
server.getServerVersion(function(version) {
    console.log(version);
});

server.getAvailableOperations(function(operations) {
    console.log(operations);
});

server.setUser('kontakt@victorjonsson.se', 'rogger');
server.me(function(data) {
    console.log(data);
});

