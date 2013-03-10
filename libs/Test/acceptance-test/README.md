# Acceptance test

This a description of how to run the acceptance test for your Rocker Server. The configuration (config.json) takes
for granted that your server is running on localhost and port 80, if not you will have to edit the configuration.

### Setup

- Install node and npm
- Navigate to this directory in your console
- Run `$ npm install dokimon && npm install -g dokimon` in your console. This will install the test tools necessary
to run the acceptance test.
- Run `$ dokimon -r`