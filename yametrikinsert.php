<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class PlgSystemYametrikInsert extends CMSPlugin
{

	protected $app;

	public function onAfterRender()
	{
		if ($this->app->isClient('administrator'))
		{
			return false;
		}

		if ($this->app->input->getCmd('option') == 'com_ajax')
		{
			return false;
		}

		if (empty($this->params->get('yametrik_id', '')))
		{
			return false;
		}

		if ($this->params->get('yametrik_admin', 0))
		{
			$user = Factory::getUser();
			if (
				$user->authorise('core.login.admin')
				|| !empty($this->app->input->get('customizer'))
				|| !empty($this->app->input->get('jdb-live-preview'))
				|| ($this->app->input->getCmd('option') == 'com_sppagebuilder' && $this->app->input->getCmd('layout') == 'edit-iframe')
			)
			{
				return false;
			}
		}

		$yaParams = [
			'triggerEvent'        => true,
			'webvisor'            => $this->params->get('yametrik_webvisor', 0) ? true : false,
			'clickmap'            => $this->params->get('yametrik_clickmap', 0) ? true : false,
			'trackHash'           => $this->params->get('yametrik_trackHash', 0) ? true : false,
			'trackLinks'          => $this->params->get('yametrik_trackLinks', 0) ? true : false,
			'ecommerce'           => $this->params->get('yametrik_ecommerce',
				0) ? $this->params->get('yametrik_ecommerce',
				'dataLayer') : false,
			'defer'               => $this->params->get('yametrik_defer', 0) ? true : false,
			'accurateTrackBounce' => $this->params->get('yametrik_yametrik_accurateTrackBounce',
				0) ? $this->params->get('yametrik_accurateTrackBounce_delay', 15000) : false,
			'childIframe'         => $this->params->get('yametrik_childIframe', 0) ? true : false,
		];

		$document = Factory::getDocument();
		$document->addCustomTag('<link rel="preconnect" href="https://mc.yandex.ru/">');

		$counter = trim($this->params->get('yametrik_id'));

		ob_start();
		?>
        <!-- YaMetrikInsert plugin -->
        <script>
            document.addEventListener("yacounter<?php echo $counter; ?>inited", function () {
                try {
					<?php echo $this->params->get('yametrik_js'); ?>
                } catch (e) {
                    console.log(String(e))
                }
            });
            (function (m, e, t, r, i, k, a) {
                m[i] = m[i] || function () {
                    (m[i].a = m[i].a || []).push(arguments)
                };
                m[i].l = 1 * new Date();
                k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
            })(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            ym(<?php echo $counter; ?>, "init", <?php echo json_encode($yaParams); ?>);
            window.goalSender = function (t, p, b) {
                p = typeof p !== 'undefined' ? p : undefined;
                b = typeof b !== 'undefined' ? b : undefined;
                if (typeof ym == 'function') {
                    window.ym(<?php echo $counter; ?>, "reachGoal", target, params, callback, ctx)
                } else {
                    window.setTimeout(function () {
                        window.goalSender(t, p, b);
                    }, 300);
                }
            }
        </script>
        <noscript>
            <div><img src="https://mc.yandex.ru/watch/48641792" style="position:absolute; left:-9999px;" alt=""
                      no-handler=""/></div>
        </noscript>
        <!-- /YaMetrikInsert plugin -->
		<?php

		$metrika = ob_get_clean();

		$body = $this->app->getBody();
		$body = str_replace("</body>", $metrika . "</body>", $body);

		$this->app->setBody($body);

		return true;
	}
}