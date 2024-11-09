/**
 * Compiler configuration
 *
 * @see {@link https://roots.io/sage/docs sage documentation}
 * @see {@link https://bud.js.org/learn/config bud.js configuration guide}
 *
 * @type {import('@roots/bud').Config}
 */
export default async (app) => {
  /**
   * Register common paths
   */
  app
    .setPath("@scripts", "resources/scripts")
    .setPath("@styles", "resources/styles")
    .setPath("@patterns", "resources/views/patterns");

  /**
   * Application assets & entrypoints
   *
   * @see {@link https://bud.js.org/reference/bud.entry}
   * @see {@link https://bud.js.org/reference/bud.assets}
   */
  app
    .entry(
      "app",
      await app.glob([
        "@scripts/app.js",
        "@styles/app.scss",
        "@patterns/**/*.{scss, css}",
      ]),
    )
    .entry("editor", ["@scripts/editor", "@styles/editor"])
    .assets(["images", "fonts"]);

  /**
   * Set public path
   *
   * @see {@link https://bud.js.org/reference/bud.setPublicPath}
   */
  app.setPublicPath("../");

  /**
   * Development server settings
   *
   * @see {@link https://bud.js.org/reference/bud.setUrl}
   * @see {@link https://bud.js.org/reference/bud.setProxyUrl}
   * @see {@link https://bud.js.org/reference/bud.watch}
   */
  app
    .setUrl("http://0.0.0.0:3005")
    .setProxyUrl("https://thehappyglamper.local.host")
    .watch(app.globSync(["resources/**/*", "app/**/*"]));

  /**
   * Set global styles
   */
  app.sass.importGlobal([
    "@styles/_variables",
    "@styles/_breakpoints",
    "@styles/_mixins",
    "@styles/_grid",
  ]);
};
