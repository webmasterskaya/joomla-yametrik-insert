<?php
/**
 * @package    Joomla - Yandex.Metrika insert
 * @version    1.1.3
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2022 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

defined('_JEXEC') or die;

return new class () implements ServiceProviderInterface {

    public function register(Container $container): void
    {
        $container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {

            /**
             * The application object
             *
             * @since  __DEPLOY_VERSION__
             */
            protected AdministratorApplication $app;

            /**
             * The Database object.
             *
             * @since  __DEPLOY_VERSION__
             */
            protected DatabaseDriver $db;

            /**
             * Constructor.
             *
             * @param AdministratorApplication $app The application object.
             *
             * @since __DEPLOY_VERSION__
             */
            public function __construct(AdministratorApplication $app)
            {
                $this->app = $app;
                $this->db = Factory::getContainer()->get('DatabaseDriver');
            }

            public function preflight(string $type, InstallerAdapter $adapter): bool
            {
                $version = new Version();

                if (!$version->isCompatible('4.4')) {
                    Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_YAMETRIKINSERT_WRONG_JOOMLA'), 'error');

                    return false;
                }

                return true;
            }

            public function install(InstallerAdapter $adapter): bool
            {
                $this->enablePlugin($adapter);

                return true;
            }

            public function update(InstallerAdapter $adapter): bool
            {
                $this->convertConfig($adapter);

                return true;
            }

            public function uninstall(InstallerAdapter $adapter): bool
            {
                return true;
            }

            public function postflight(string $type, InstallerAdapter $adapter): bool
            {
                Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_YAMETRIKINSERT_WELCOME_MESSAGE'), 'notice');

                return true;
            }

            /**
             * Enable plugin after installation.
             *
             * @param InstallerAdapter $adapter Parent object calling object.
             *
             * @since  __DEPLOY_VERSION__
             */
            private function enablePlugin(InstallerAdapter $adapter): void
            {
                // Prepare plugin object
                $plugin = new \stdClass();
                $plugin->type = 'plugin';
                $plugin->element = $adapter->getElement();
                $plugin->folder = (string)$adapter->getParent()->manifest->attributes()['group'];
                $plugin->enabled = 1;

                // Update record
                $this->db->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);
            }

            private function convertConfig(InstallerAdapter $adapter): void
            {

            }
        });
    }
};