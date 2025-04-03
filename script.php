<?php
/**
 * @package    Joomla - Yandex.Metrika insert
 * @version    1.1.3
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2022 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class plgSystemYaMetrikInsertInstallerScript
{
	function preflight($type, $parent)
	{
		jimport('joomla.version');
		// Check compatible Joomla version
		$jversion = new JVersion();

		if (!$jversion->isCompatible('3.8.1'))
		{
			Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_YAMETRIKINSERT_WRONG_JOOMLA'), 'error');

			return false;
		}

        return true;
	}

	function postflight($type, $parent)
	{
		Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_YAMETRIKINSERT_WELCOME_MESSAGE'), 'notice');
	}
}