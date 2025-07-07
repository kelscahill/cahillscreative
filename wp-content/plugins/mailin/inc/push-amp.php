<?php
if (!defined( 'ABSPATH' )) { http_response_code(403); exit(); }

if ( ! class_exists( 'SIB_Push_Amp' ) ) {
	class SIB_Push_Amp {
		/**
		 * @return string
		 */
		static function snippet() {
			if (!SIB_Push_Utils::is_push_active()) return '';
			try {
				$app = SIB_Push_Utils::get_push_application();
			} catch ( Exception $e ) {
				SIB_Push_Utils::log_warn('Could not get application', $e);
				return '';
			}
			$web_key = $app ? $app->getWebKey() : null;
			if (!$web_key) return '';
			$url = plugin_dir_url( realpath(__DIR__ . '/../wonderpush.min.html')  );
			return <<<EOT
<amp-web-push
		id="amp-web-push"
		layout="nodisplay"
		helper-iframe-url="{$url}wonderpush.min.html?wonderpushWebKey={$web_key}&amp=frame"
		permission-dialog-url="{$url}wonderpush.min.html?wonderpushWebKey={$web_key}&amp=dialog"
		service-worker-url="{$url}wonderpush-worker-loader.min.js?webKey={$web_key}"
	></amp-web-push>
EOT;
		}
		static function widget() {

			$settings = SIB_Push_Settings::getSettings();

			// Subscribe button label
			$subscribeButtonLabel = $settings->getAmpSubscribeButtonLabel();
			if ( !$subscribeButtonLabel ) $subscribeButtonLabel = __( 'Subscribe to news updates', 'mailin' );
			// Unsubscribe button label
			$unsubscribeButtonLabel = $settings->getAmpUnsubscribeButtonLabel();
			if ( !$unsubscribeButtonLabel ) $unsubscribeButtonLabel = __( 'Unsubscribe from news updates', 'mailin' );
			// Button width
			$width = $settings->getAmpButtonWidth();
			if ( !$width ) $width = 250;
			// Button height
			$height = $settings->getAmpButtonHeight();
			if ( !$height ) $height = 45;
			$heightMinus1 = $height - 1;
			$unsubscribe = SIB_Push_Settings::getSettings()->getDisableAmpUnsubscribe() ? '' : <<<EOT
<amp-web-push-widget visibility="subscribed" layout="fixed" width="{$width}" height="{$height}">
	<button class="unsubscribe" style="height:{$heightMinus1}px" on="tap:amp-web-push.unsubscribe">{$unsubscribeButtonLabel}</button>
</amp-web-push-widget>
EOT;
			return <<<EOT
<div class="sib-amp-web-push-container" style="width: {$width}px">
    <amp-web-push-widget visibility="unsubscribed" layout="fixed" width="{$width}" height="{$height}">
        <button class="subscribe" style="height: {$heightMinus1}px" on="tap:amp-web-push.subscribe">
            <amp-img
                    class="subscribe-icon"
                    width="24"
                    height="24"
                    layout="fixed"
                    src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iNjBweCIgaGVpZ2h0PSI2MHB4IiB2aWV3Qm94PSIwIDAgNjAgNjAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8IS0tIEdlbmVyYXRvcjogU2tldGNoIDQ5LjMgKDUxMTY3KSAtIGh0dHA6Ly93d3cuYm9oZW1pYW5jb2RpbmcuY29tL3NrZXRjaCAtLT4KICAgIDx0aXRsZT5MYXllciAxPC90aXRsZT4KICAgIDxkZXNjPkNyZWF0ZWQgd2l0aCBTa2V0Y2guPC9kZXNjPgogICAgPGRlZnM+PC9kZWZzPgogICAgPGcgaWQ9IlBhZ2UtMSIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPGcgaWQ9IkN1c3RvbS1QcmVzZXQtMiIgZmlsbD0iI0ZGRkZGRiIgZmlsbC1ydWxlPSJub256ZXJvIj4KICAgICAgICAgICAgPGcgaWQ9IkxheWVyLTEiPgogICAgICAgICAgICAgICAgPHBhdGggZD0iTTQ5LjY0MDYyNSwyNS4yODg5NzIyIEM0OS42NDA2MjUsMTAuMDQzNzM2MyA0MS40Mzc1LDQuODU3ODU2OTIgMzMuNzY1NjI1LDMuNzAxOTY4MTMgQzMzLjc2NTYyNSwzLjYyMzg2NzU0IDMzLjc4MTI1LDMuNTQ1NzY2OTUgMzMuNzgxMjUsMy40NTIwNDYyNCBDMzMuNzgxMjUsMS41MzA3NzE2MyAzMi4wNzgxMjUsMCAzMCwwIEMyNy45MjE4NzUsMCAyNi4yODEyNSwxLjUzMDc3MTYzIDI2LjI4MTI1LDMuNDUyMDQ2MjQgQzI2LjI4MTI1LDMuNTQ1NzY2OTUgMjYuMjgxMjUsMy42MjM4Njc1NCAyNi4yOTY4NzUsMy43MDE5NjgxMyBDMTguNjA5Mzc1LDQuODczNDc3MDQgMTAuMzU5Mzc1LDEwLjA3NDk3NjYgMTAuMzU5Mzc1LDI1LjMyMDIxMjQgQzEwLjM1OTM3NSw0My4wOTU5MDc1IDUuOTM3NSw0NS4wMDE1NjIgMCw1MCBMNjAsNTAgQzU0LjA5Mzc1LDQ0Ljk4NTk0MTkgNDkuNjQwNjI1LDQzLjA2NDY2NzMgNDkuNjQwNjI1LDI1LjI4ODk3MjIgWiIgaWQ9InN2Z18yIj48L3BhdGg+CiAgICAgICAgICAgICAgICA8cGF0aCBkPSJNMzAuNSw2MCBDMzQuOTA2MTg5Niw2MCAzOC41MjMyMTA4LDU2Ljc2MDQ2NTEgMzksNTMgTDIyLDUzIEMyMi40NjAzNDgyLDU2Ljc2MDQ2NTEgMjYuMDkzODEwNCw2MCAzMC41LDYwIFoiIGlkPSJzdmdfMyI+PC9wYXRoPgogICAgICAgICAgICA8L2c+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4=">
            </amp-img>
          {$subscribeButtonLabel}
        </button>
    </amp-web-push-widget>
    {$unsubscribe}
</div>
EOT;

		}
	}
}
