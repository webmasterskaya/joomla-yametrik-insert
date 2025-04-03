<?php
/**
 * @package    Joomla - Yandex.Metrika insert
 * @version    1.1.3
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2022 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

class JFormFieldMediator extends FormField
{
	protected $type = 'mediator';

	protected function getInput()
	{
		if ((int)$this->element['styles'])
		{
			HTMLHelper::_('stylesheet', 'plg_system_yametrikinsert/adminstyle.css', array('version' => 'auto', 'relative' => true));
		}

		if ((int)$this->element['script'])
		{
			HTMLHelper::_('script', 'plg_system_yametrikinsert/adminscript.js', array('version' => 'auto', 'relative' => true));
		}
	}
}