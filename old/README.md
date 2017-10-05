# Cahill's Creative

### Grunt Workflow

## Installation

First, download and install Node.js from http://nodejs.org/

We use Node version 10.40 (go to the 'prior releases page to download). Bleeding edge versions of Node may have compatibility issues with some of the modules we are using, so we recommend using the same node version. If you need to run multiple versions of Node, you can use NVM (Node version manager).

## In terminal (on a mac):

**Go to your root folder** where you have your gruntfile.js. If grunt is not yet installed, go to the folder where you have your gruntfile.js and type:

    sudo ./install.sh

Otherwise, go to the same folder and type:

    sudo npm install

Then update node modules by typing:

    npm update

## On a PC

You may be able to run install.bat from the command line (this is not currently tested).
Otherwise, the important things to run are:

	npm install -g grunt
	npm install -g grunt-cli
	npm install

Again, **run this from the root folder where your gruntfile.js is**.

now from the commandline you can do (from your root folder)

    grunt start

to start harp and watch for changes to your JS files and SASS files.  If you ran the install script, 'grunt start' was run for you. Hit CTRL-C to quit it at any time.

you can also run individual tasks, such as:

    grunt cssmin

or

  grunt concat:js

See the grunt docs for more info on running tasks or peruse the gruntfile.js.

## Tasks

* `grunt` - full build - compile all css / js / png assets
* `grunt start` - starts harp, begins monitoring build files for changes
* `grunt compile` - compiles harp files (Use node v0.12.7 for development builds and v6.9.4 to run `harp compile`)

Once the site is build, final files will be placed in the /static/ folder.

If you want to check your static files before putting them up on UAT, you can run a local web server such as [http-server](https://github.com/indexzero/http-server). Just install that and type:

	http-server -o .

from the `static` folder. This will be necessary to check out the locked article functionality and test links to locked articles.

## What it does

- Runs Harp.js static web server
- Combines CSS and JS files in the build folder
- Minfies CSS and JS files
- Compiles SASS files in the build folder
- Minifies PNG’s in the build
- Uses Livereload to automatically reload your pages in the browser as you edit
