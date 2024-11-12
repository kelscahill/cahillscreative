## Overview

Cahill's Creative website. Built with [Sage](https://roots.io/sage/) and [Timber](https://timber.github.io/docs/getting-started/setup/).

## Requirements
- [Lando](https://docs.lando.dev/basics/installation.html#macos)
- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)
- [PHP](https://secure.php.net/manual/en/install.php) >= 7.4.0
- [Node.js](http://nodejs.org/) >= 16
- [Yarn](https://yarnpkg.com/en/docs/install)

## Foundations
This start kit is built upon the following frameworks:

- [Sage](https://roots.io/sage/)
- [Timber](https://timber.github.io/docs/getting-started/setup/)

## Lando/Docker Setup
### Initialize a "wordpress" recipe
1. Run `lando start` to start the app.

### List information about this app (not necessary for installation)
```sh
lando info
```

## Theme Setup
Edit `app/setup.php` to enable or disable theme features, setup navigation menus, post thumbnail sizes, and sidebars.

### Theme development
Navigate to the theme directory, then run `yarn setup` to install dependencies.
*Make sure your node version matches whats in the `.nvmrc` file.

### Build commands
- `yarn start` — Compile assets when file changes are made, start Browsersync session.
- `yarn build` — Compile assets for production.

### Migrate a Database
#### Lando Way
To sync the live site's database with your local site use the following command:
```shell script
  lando pull --database=dev --files=none --code=none
```

#### WP Migrate Pro Way
1. Go to the plugins and activate the WP Migrate Pro plugin.
2. Enter the license key. It can be found pinned in the `#licenses` channel in Slack.
3. Go to `Tools > WP Migrate Pro` and copy the secret key in `Settings` from the url where you would like to pull the DB from.
4. On your local, go to WP Migrate Pro and click `Migrate > Pull` and paste in the secret key from production/staging. Check Media Files to pull the images and verify all the settings are correct and pull.

### Update and activate plugins

1. Update the `style.css` file to Cahill's Creative's settings (feel free to update the `screenshot.png` too).
2. Navigate to https://cahillscreative.local.host/wp-admin/themes.php and update the theme to the `your-site` theme.
3. Navigate to https://cahillscreative.local.host/wp-admin/update-core.php and update any plugins that need updating.

This starter kit comes packed with the following plugins:
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- [WP Migrate Pro](https://deliciousbrains.com/wp-migrate-db-pro/)
- [Perfmatters](https://perfmatters.io/)
- [Yoast SEO](https://yoast.com/wordpress/plugins/seo/)

### WordPress multisite setup
1. Open `wp-config.php` and add this line to the file and save:
```php
define( 'WP_ALLOW_MULTISITE', true );
```
2. In your browser, navigate to [Tools > Network Setup](https://cahillscreative.local.host/wp-admin/network.php) and click "Install".
3. Follow the instructions on the Network Setup page to complete the setup.
4. Refresh the page and log back in.

## SCSS structure
A `bem_classes()` function to makes sure the SCSS is properly written with the BEM methodology along with prefixing all the class names with one of the following namespaces:
* `.l-` — Layouts
* `.o-` — Objects
* `.c-` — Components
* `.js` — JavaScript hooks
* `.is-`|`.has-` — State Classes
* `.u-` — Utility Classes

## Documentation

- [Timber](https://timber.github.io/docs/)
- [Sage](https://roots.io/sage/docs/installation/)
- [Twig](http://twig.sensiolabs.org/)
- [ACF with Twig](https://github.com/timber/timber/blob/master/docs/guides/acf-cookbook.md)
- [Twig/Timber Cheatsheet](https://notlaura.com/the-twig-for-timber-cheatsheet/)
- [BEM Methodology](https://www.smashingmagazine.com/2018/06/bem-for-beginners/)