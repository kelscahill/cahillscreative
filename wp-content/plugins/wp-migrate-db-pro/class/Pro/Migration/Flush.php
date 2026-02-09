<?php
namespace DeliciousBrains\WPMDB\Pro\Migration;

use DeliciousBrains\WPMDB\Common\Migration\Flush as Common_Flush;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;

/**
 * Pro Migration Flush Handler
 *
 * Extends base flush functionality to support unauthenticated flush endpoints
 * for pull migrations with user tables.
 */
class Flush extends Common_Flush
{
    /**
     * Register Pro-specific flush actions.
     * Adds the unauthenticated endpoint needed for pull migrations with user tables.
     */
    public function register()
    {
        parent::register();
        add_action('wp_ajax_nopriv_wpmdb_flush', array($this, 'ajax_nopriv_flush'));
    }

    /**
     * Handles the request to flush caches and cleanup migration when pulling with user tables being migrated.
     *
     * @return bool|null
     */
    function ajax_nopriv_flush()
    {
        Persistence::cleanupStateOptions();

        return $this->http->end_ajax($this->flush());
    }
}
