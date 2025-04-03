<?php
/**
 * @package    Joomla - Yandex.Metrika insert
 * @version    1.1.3
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2022 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;

defined('_JEXEC') or die;

class PlgSystemYametrikInsert extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

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
	private $_isAuthorizedAdmin = null;

	/**
	 * Is page PageBuilder.
	 *
	 * @var  boolean
	 *
	 * @since  1.0.0
	 */
	private $_isBuilder = null;

	/**
	 * Constructor.
	 *
	 * @param   object|DispatcherInterface  &$subject
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
		// Check allowing for embed metrika
		if (!$this->allowMetrika())
		{
			return false;
		}

		// Prepare array of params.
		$yaParams = [
			'triggerEvent'        => true,
			'webvisor'            => (bool) $this->params->get('yametrik_webvisor', 0),
			'clickmap'            => (bool) $this->params->get('yametrik_clickmap', 0),
			'trackHash'           => (bool) $this->params->get('yametrik_trackHash', 0),
			'trackLinks'          => (bool) $this->params->get('yametrik_trackLinks', 0),
			'defer'               => !$this->params->get('yametrik_defer', 0),
			'childIframe'         => (bool) $this->params->get('yametrik_childIframe', 0),
		];

		switch ($this->params->get('yametrik_ecommerce', 0))
		{
			case 1:
				$yaParams['ecommerce'] = $this->params->get('yametrik_ecommerce_container', 'dataLayer');
				break;
			default:
				$yaParams['ecommerce'] = false;
		}

		switch ($this->params->get('yametrik_accurateTrackBounce', 1))
		{
			case 0:
				$yaParams['accurateTrackBounce'] = false;
				break;
			case 2:
				$yaParams['accurateTrackBounce'] = $this->params->get('yametrik_accurateTrackBounce_delay', 15000);
				break;
			default:
				$yaParams['accurateTrackBounce'] = true;
		}

		// Bypass
		if ($this->params->get('yametrik_bypass', 1) == 0)
		{
			$yaParams['ut'] = 'noindex';
		}
		else
		{
			if ($this->params->get('yametrik_bypass_admin', 1) == 1 && $this->params->get('yametrik_admin', 1) == 0 && $this->isAuthorizedAdmin())
			{
				$yaParams['ut'] = 'noindex';
			}
			else
			{
				if ($this->params->get('yametrik_bypass_builder', 1) == 1 && $this->params->get('yametrik_builder', 1) == 0 && $this->params->get('yametrik_bypass_admin', 1) == 0)
				{
					if ($this->isBuilder())
					{
						$yaParams['ut'] = 'noindex';
					}
				}
			}
		}

		// Send client IP
		if (!!$this->params->get('yametrik_send_ip', 0))
		{
			$yaParams['params']['ip'] = $_SERVER['REMOTE_ADDR'];
		}

		$counter = trim($this->params->get('yametrik_id'));

		// Prepare full embed code.
		ob_start();
		?>
        <!-- YaMetrikInsert plugin -->
        <script>
			<?php if ($this->params->get('yametrik_js') != "") { ?>
            document.addEventListener("yacounter<?php echo $counter; ?>inited", function () {
                try {
					<?php echo $this->params->get('yametrik_js'); ?>
                } catch (e) {
                    console.error(e)
                }
            });
			<?php } ?>
            (function (m, e, t, r, i, k, a) {
                m[i] = m[i] || function () {
                    (m[i].a = m[i].a || []).push(arguments)
                };
                m[i].l = 1 * new Date();
                k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
            })(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            ym(<?php echo $counter; ?>, "init", <?php echo json_encode($yaParams); ?>);
            window.goalSender = function (t, p, b) {
                p = typeof p !== 'object' ? {} : p;
                b = typeof b !== 'undefined' ? b : undefined;

                <?php if($this->params->get('yametrik_goal_ip', 0) && $this->params->get('yametrik_goal_ip', 0)): ?>
                p['IP'] = '<?php echo $_SERVER['REMOTE_ADDR']; ?>';
                <?php endif; ?>

                <?php if($this->params->get('yametrik_goal_url', 0)): ?>
                p['URL'] = document.location.href;
                <?php endif; ?>

                if (typeof ym == 'function') {
                    window.ym(<?php echo $counter; ?>, "reachGoal", t, p, b)
                } else {
                    window.setTimeout(function () {
                        window.goalSender(t, p, b);
                    }, 300);
                }
            };
            window.hitSender = function (u, o) {
                u = typeof u !== 'undefined' ? u : location.href;
                o = typeof o !== 'undefined' ? o : [];

                if (typeof ym == 'function') {
                    window.ym(<?php echo $counter; ?>, "hit", u, o);
                } else {
                    window.setTimeout(function () {
                        window.hitSender(u, o);
                    }, 300);
                }
            }
        </script>
        <noscript>
            <div><img src="https://mc.yandex.ru/watch/<?php echo $counter; ?>" style="position:absolute; left:-9999px;"
                      alt=""/></div>
        </noscript>
        <!-- /YaMetrikInsert plugin -->
		<?php

		$metrika = ob_get_clean();

		$body = $this->app->getBody();

		switch ($this->params->get('yametrik_position', 0))
		{
			case 1:
				// Вставляет код метрики после открывающего тега <body>
				$body = preg_replace('/(<body[^<]*>)/is', '$1' . PHP_EOL . $metrika, $body, 1);
				break;
			default:
				// Вставляет код метрики перед закрывающим тегом </body>
				$body = str_replace("</body>", $metrika . PHP_EOL . "</body>", $body);
				break;
		}

		$this->app->setBody($body);

		return true;
	}

	/**
	 * Method for checks allowing to embed metrika
	 * @return bool
	 *
	 *
	 * @since 1.0.0
	 */
	protected function allowMetrika()
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

		// Do not embed for admin.
		if ($this->params->get('yametrik_admin', 0) && $this->isAuthorizedAdmin())
		{
			return false;
		}

		// Do not embed on preview page of page builder (supported YooTheme, SP:PB, JD:PB).
		if ($this->params->get('yametrik_builder', 1))
		{
			if ($this->isBuilder())
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to check  is visitor authorized in control panel.
	 * @return  bool True if authorized administrator, False if is not.
	 *
	 *
	 * @since      1.0.0
	 *
	 * @copyright  Copyright (c) 2018 - 2022 Septdir Workshop. All rights reserved.
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

	/**
	 * Method to check  is page PageBuilder
	 * @return  bool True if is PageBuilder, False if is not.
	 *
	 *
	 * @since      1.0.0
	 */
	protected function isBuilder()
	{
		if ($this->_isBuilder === null)
		{
			if (!empty($this->app->input->get('customizer')) || !empty($this->app->input->get('jdb-live-preview')) || ($this->app->input->getCmd('option') == 'com_sppagebuilder' && $this->app->input->getCmd('layout') == 'edit-iframe'))
			{
				$this->_isBuilder = true;
			}
			else
			{
				$this->_isBuilder = false;
			}
		}

		return $this->_isBuilder;
	}

	/**
	 * Method for insert scripts and custom tags to head section.
	 * @return bool
	 *
	 *
	 * @since 1.0.0
	 */
	public function onBeforeCompileHead()
	{
		// Check allowing for embed metrika
		if (!$this->allowMetrika())
		{
			return false;
		}

		// Speed up script loading
		/** @var \Joomla\CMS\Document\Document $document */
		$document = Factory::getDocument();
		$document->addCustomTag('<link rel="preconnect" href="https://mc.yandex.ru/">');

		// Set dataLayer container for ecommerce
		if ($this->params->get('yametrik_ecommerce', 0))
		{
			$document->addScriptDeclaration('window.' . $this->params->get('yametrik_ecommerce_container', 'dataLayer') . ' = window.' . $this->params->get('yametrik_ecommerce_container', 'dataLayer') . ' || [];');
		}

		return true;
	}
}