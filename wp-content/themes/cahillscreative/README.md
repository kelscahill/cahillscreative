# Sage-9-timber

Sage-9-timber is [Sage theme](https://github.com/roots/sage) version 9 made to work with [Timber](https://www.upstatement.com/timber/).

For easy integrations and later compatibility, this repository aimed at making the minimum of changes possible to the original Sage 9 beta 4 in order to make Timber work. This theme has still blade template language support.

**Sage 9 is in active development and is currently in beta. This initial fork used version Sage 9 beta 4. If you want a stable version of Sage ported to Timber, have a look at the [sage-timber](https://github.com/artifex404/sage-timber) project.**

## Features

* Sass for stylesheets
* ES6 for JavaScript
* [Webpack](https://webpack.github.io/) for compiling assets, optimizing images, and concatenating and minifying files
* [Browsersync](http://www.browsersync.io/) for synchronized browser testing
* [Laravel Blade](https://laravel.com/docs/5.3/blade) as a templating engine
* [Controller](https://github.com/soberwp/controller) for passing data to Blade templates
* CSS framework options:
  * [Bootstrap 4](http://getbootstrap.com/)
  * [Bulma](http://bulma.io/)
  * [Foundation](http://foundation.zurb.com/)
  * [Tachyons](http://tachyons.io/)
  * None (blank slate)
* Font Awesome (optional)

See a working example at [roots-example-project.com](https://roots-example-project.com/).

## Requirements

Make sure all dependencies have been installed before moving on:

* [Timber](https://www.upstatement.com/timber/) as a WordPress plugin
* [WordPress](https://wordpress.org/) >= 4.7
* [PHP](http://php.net/manual/en/install.php) >= 7.0
* [Composer](https://getcomposer.org/download/)
* [Node.js](http://nodejs.org/) >= 6.9.x
* [Yarn](https://yarnpkg.com/en/docs/install)

## Theme installation

Install Sage-9-timber by copying the project into a new folder within your WordPress themes directory.

## Theme structure

```shell
themes/your-theme-name/   # → Root of your Sage based theme
├── app/                  # → Theme PHP
│   ├── controllers/      # → Controller files
│   ├── admin.php         # → Theme customizer setup
│   ├── filters.php       # → Theme filters
│   ├── helpers.php       # → Helper functions
│   └── setup.php         # → Theme setup
├── composer.json         # → Autoloading for `app/` files
├── composer.lock         # → Composer lock file (never edit)
├── dist/                 # → Built theme assets (never edit)
├── node_modules/         # → Node.js packages (never edit)
├── package.json          # → Node.js dependencies and scripts
├── resources/            # → Theme assets and templates
│   ├── acf-json          # → ACF Json files
│   ├── assets/           # → Front-end assets
│   │   ├── config.json   # → Settings for compiled assets
│   │   ├── build/        # → Webpack and ESLint config
│   │   ├── fonts/        # → Theme fonts
│   │   ├── images/       # → Theme images
│   │   ├── scripts/      # → Theme JS
│   │   └── styles/       # → Theme stylesheets
│   ├── functions.php     # → Composer autoloader, theme includes
│   ├── index.php         # → Never manually edit
│   ├── screenshot.png    # → Theme screenshot for WP admin
│   ├── style.css         # → Theme meta information
│   └── views/            # → Theme Timber templates
│       └── _patterns/    # → Timber twig templates
└── vendor/               # → Composer packages (never edit)
```

## Theme setup

Edit `app/setup.php` to enable or disable theme features, setup navigation menus, post thumbnail sizes, and sidebars.

## Theme development

* Update `resources/assets/config.json` settings:
  * `devUrl` should reflect your local development hostname
  * `publicPath` should reflect your WordPress folder structure (`/wp-content/themes/sage-timber`)
* Run `yarn dev` from the theme directory to install development dependencies

### Build commands

* `yarn start` — Compile assets when file changes are made, start Browsersync session
* `yarn build` — Compile and optimize the files in your assets directory
* `yarn build:production` — Compile assets for production

## Documentation

Timber documentation is available at [https://timber.github.io/docs/](https://timber.github.io/docs/).

Twig documentation is available at [http://twig.sensiolabs.org/](http://twig.sensiolabs.org/).

Sage 9 documentation is currently in progress and can be viewed at [https://github.com/roots/docs/tree/sage-9/sage](https://github.com/roots/docs/tree/sage-9/sage).

Controller documentation is available at [https://github.com/soberwp/controller#usage](https://github.com/soberwp/controller#usage).

ACF with Twig documentation is available at [https://github.com/timber/timber/blob/master/docs/guides/acf-cookbook.md](https://github.com/timber/timber/blob/master/docs/guides/acf-cookbook.md).

Twig/Timber Cheatsheeet is available at [https://notlaura.com/the-twig-for-timber-cheatsheet/](https://notlaura.com/the-twig-for-timber-cheatsheet/)

## Contributing

Contributions are welcome from everyone. Just issue a pull request to this repository.

## Sage Community

Keep track of development and community news.

* Participate on the [Roots Discourse](https://discourse.roots.io/)
* Follow [@rootswp on Twitter](https://twitter.com/rootswp)
* Read and subscribe to the [Roots Blog](https://roots.io/blog/)
* Subscribe to the [Roots Newsletter](https://roots.io/subscribe/)
* Listen to the [Roots Radio podcast](https://roots.io/podcast/)
