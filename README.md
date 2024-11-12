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
### Initialize a "pantheon" recipe
1. Run `lando init` from the command line in the root of this repo.
   1. **From where should we get your app's codebase?**
      -> **current working directory**.
   1. **What recipe do you want to use?**
      -> **pantheon**
   1. **Select a Pantheon account**
      -> Choose the email address that you used when logging into your Pantheon account.
   1. **Which site?**
      -> Choose the site you wish to sync your local development with.
2. Run `lando start` to start the app.

### List information about this app (not necessary for installation)
```sh
lando info
```

## Remote Setup
### Setup
  1. Run `lando remote add pantheon ssh://codeserver.dev.051742c9-8e99-4aa2-bda5-f0aacb0ac53b@codeserver.dev.051742c9-8e99-4aa2-bda5-f0aacb0ac53b.drush.in:2222/~/repository.git` to add the pantheon remote.

## Theme Setup
Edit `app/setup.php` to enable or disable theme features, setup navigation menus, post thumbnail sizes, and sidebars.

### Theme development
Navigate to the theme directory, then run `yarn setup` to install dependencies.
*Make sure your node version matches whats in the `.nvmrc` file.

### Build commands
- `yarn start` — Compile assets when file changes are made, start Browsersync session.
- `yarn build` — Compile assets for production.

### Deploying
  1. Run `git push` to trigger an automated deployment to push changes to Pantheon dev environment of the Norhtright parent site.
  2. Run `git push origin master` to trigger the automated deployment to build all sites.
  3. Deploy all sites to dev: Go to [Github Actions](https://github.com/southleft/northright/actions/workflows/pantheon-deploy-dev.yml) and click the `Run workflow` button to deploy to the `dev` environment.
  4. Deploy all sites to test: Go to [Github Actions](https://github.com/southleft/northright/actions/workflows/pantheon-deploy-test.yml) and click the `Run workflow` button to deploy to the `test` environment.
  5. Deploy all sites to live: Go to [Github Actions](https://github.com/southleft/northright/actions/workflows/pantheon-deploy-live.yml) and click the `Run workflow` button to deploy to the `live` environment.

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

# WordPress

This is a WordPress repository configured to run on the [Pantheon platform](https://pantheon.io).

Pantheon is website platform optimized and configured to run high performance sites with an amazing developer workflow. There is built-in support for features such as Varnish, Redis, Apache Solr, New Relic, Nginx, PHP-FPM, MySQL, PhantomJS and more. 

## Getting Started

### 1. Spin-up a site

If you do not yet have a Pantheon account, you can create one for free. Once you've verified your email address, you will be able to add sites from your dashboard. Choose "WordPress" to use this distribution.

### 2. Load up the site

When the spin-up process is complete, you will be redirected to the site's dashboard. Click on the link under the site's name to access the Dev environment.

![alt](http://i.imgur.com/2wjCj9j.png?1, '')

### 3. Run the WordPress installer

How about the WordPress database config screen? No need to worry about database connection information as that is taken care of in the background. The only step that you need to complete is the site information and the installation process will be complete.

We will post more information about how this works but we recommend developers take a look at `wp-config.php` to get an understanding.

![alt](http://i.imgur.com/4EOcqYN.png, '')

If you would like to keep a separate set of configuration for local development, you can use a file called `wp-config-local.php`, which is already in our .gitignore file.

### 4. Enjoy!

![alt](http://i.imgur.com/fzIeQBP.png, '')

## Branches

The `default` branch of this repository is where PRs are merged, and has [CI](https://github.com/pantheon-systems/WordPress/tree/default/.circleci) that copies `default` to `master` after removing the CI directories. This allows customers to clone from `master` and implement their own CI without needing to worry about potential merge conflicts.

## Custom Upstreams

If you are using this repository as a starting point for a custom upstream, be sure to review the [documentation](https://pantheon.io/docs/create-custom-upstream#pull-in-core-from-pantheons-upstream) and pull the core files from the `master` branch.
