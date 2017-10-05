#!/bin/bash
echo Installing dependencies...
npm config set registry http://registry.npmjs.org/
npm config set ca ""
sudo npm install -g grunt
sudo npm install -g grunt-cli
sudo npm install
grunt start 