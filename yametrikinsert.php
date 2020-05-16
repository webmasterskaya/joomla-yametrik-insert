<?php
/**
 * @package    Joomla - Yandex.Metrika insert
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class PlgSystemYametrikInsert extends CMSPlugin
{
	/**
	 * Loads the application object.
	 *
	 * @var  CMSApplication
	 *
	 * @since  1.0.0
	 */
	protected $app = null;

	/**
	 * Loads the database object.
	 *
	 * @var  JDatabaseDriver
	 *
	 * @since  1.0.0
	 */
	protected $db = null;

	/**
	 * Is visitor authorized in control panel.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	protected $_isAuthorizedAdmin = null;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject
	 * @param   array    $config
	 *
	 *
	 * @since 1.0.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Embed the Yandex.Metrika on page.
	 * @return bool
	 *
	 *
	 * @since 1.0.0
	 */
	public function onAfterRender()
	{
		// Do not embed in admin panel.
		if ($this->app->isClient('administrator'))
		{
			return false;
		}

		// Do not embed in com_ajax.
		if ($this->app->input->getCmd('option') == 'com_ajax')
		{
			return false;
		}

		// Do not embed  if the metrika identifier is not specified.
		if (empty($this->params->get('yametrik_id', '')))
		{
			return false;
		}

		// Do not embed on preview page of page builder (supported YooTheme, SP:PB, JD:PB).
		if (!empty($this->app->input->get('customizer')) || !empty($this->app->input->get('jdb-live-preview')) || ($this->app->input->getCmd('option') == 'com_sppagebuilder' && $this->app->input->getCmd('layout') == 'edit-iframe'))
		{
			return false;
		}

		// Do not embed for admin.
		if ($this->params->get('yametrik_admin', 0) && $this->isAuthorizedAdmin())
		{
			return false;
		}

		// Prepare array of params.
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

		/** @var \Joomla\CMS\Document\Document $document */
		$document = Factory::getDocument();
		$document->addCustomTag('<link rel="preconnect" href="https://mc.yandex.ru/">');

		$counter = trim($this->params->get('yametrik_id'));

		// Prepare full embed code.
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

		// Embed the code before the closing tag </body>.
		$body = $this->app->getBody();
		$body = str_replace("</body>", $metrika . "</body>", $body);

		$this->app->setBody($body);

		return true;
	}

	/**
	 * Method to check  is visitor authorized in control panel.
	 *
	 * @return  bool True if authorized administrator, False if is not.
	 *
	 *
	 * @since      1.0.0
	 *
	 * @copyright  Copyright (c) 2018 - 2020 Septdir Workshop. All rights reserved.
	 * @author     Septdir Workshop - www.septdir.com
	 * @link       https://www.septdir.com/
	 */
	protected function isAuthorizedAdmin()
	{
		if ($this->_isAuthorizedAdmin === null)
		{
			$db    = $this->db;
			$admin = false;

			// Check on site
			if ($this->app->isClient('site'))
			{
				// Get sessions
				$sessions = array();
				foreach ($this->app->input->cookie->getArray() as $key => $value)
				{
					if (strlen($key) === 32 && strlen($value) === 32)
					{
						$sessions[] = $db->quote(trim($value));
					}
				}

				// Find administrator session
				if (!empty($sessions))
				{
					$query = $db->getQuery(true)
						->select('userid')
						->from($db->quoteName('#__session'))
						->where($db->quoteName('session_id') . ' IN (' . implode(',', $sessions) . ')')
						->where('time > '
							. Factory::getDate('- ' . Factory::getConfig()->get('lifetime', 15) . 'minute')->toUnix())
						->where('client_id = 1')
						->where('guest = 0');
					$admin = (!empty($db->setQuery($query)->loadResult()));
				}
			}

			// Check on control panel
            elseif ($this->app->isClient('administrator') && !Factory::getUser()->guest)
			{
				$admin = true;
			}

			$this->_isAuthorizedAdmin = $admin;
		}

		return $this->_isAuthorizedAdmin;
	}
}