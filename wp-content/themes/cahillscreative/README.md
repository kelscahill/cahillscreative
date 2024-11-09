## Theme Setup

Edit `app/setup.php` to enable or disable theme features, setup navigation menus, post thumbnail sizes, and sidebars.

### Theme development
- Run `yarn setup`
*Make sure your node version matches whats in the `.nvmrc` file

### Build commands
- `yarn start` — Compile assets when file changes are made, start Browsersync session
- `yarn build` — Compile assets for production

## Theme structure

```sh
themes/your-site/         # → Root of your theme
├── acf-json/             # → Where the ACF fields save to
├── app/                  # → Theme PHP
│   ├── Providers/        # → Service providers
│   ├── View/             # → View models
│   ├── filters.php       # → Theme filters
│   └── setup.php         # → Theme setup
├── composer.json         # → Autoloading for `app/` files
├── public/               # → Built theme assets (never edit)
├── functions.php         # → Theme bootloader
├── index.php             # → Theme template wrapper
├── node_modules/         # → Node.js packages (never edit)
├── package.json          # → Node.js dependencies and scripts
├── resources/            # → Theme assets and templates
│   ├── fonts/            # → Theme fonts
│   ├── functions/        # → Theme functions
│   ├── images/           # → Theme images
│   ├── scripts/          # → Theme javascript
│   ├── styles/           # → Main theme stylesheets & tokens
│   └── views/            # → Theme templates
│       ├── 00-base/      # → Base scss styles
│       ├── 01-atoms/     # → Atoms components
│       ├── 02-molecules/ # → Molecule components
│       ├── 03-organisms/ # → Organism components
│       │── 04-templates/ # → Layouts
│       └── 05-pages/     # → Main WP templates
├── screenshot.png        # → Theme screenshot for WP admin
├── style.css             # → Theme meta information
├── vendor/               # → Composer packages (never edit)
└── bud.config.js         # → Bud configuration
```

## Troubleshooting

## Kinsta Hosting

For Kinsta hosting, if your blocks are not displaying, you'll need to add a fallback for the block template path. See the example below and make sure to update `your-site` to your theme name.

```
$templates = array(
  '/wp-content/themes/your-site/resources/views/patterns/02-molecules/components/accordion/accordion.twig',
  get_stylesheet_directory() . '/resources/resources/views/patterns/02-molecules/components/accordion/accordion.twig',
);
```

### PHP Version

If you get a notice about the php versions not aligning, you'll need to add the following to your `.lando.yml` in the root folder. Once the php version is set in the lando config, run `lando rebuild`. Then you should be able to run `composer install` without any issues.

```
config:
  webroot: .
  php: '8.1'
```
