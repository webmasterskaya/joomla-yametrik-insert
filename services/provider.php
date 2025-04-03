<?php

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Webmasterskaya\Plugin\System\YandexMetrika\Extension\Plugin;

\defined('_JEXEC') or die;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = PluginHelper::getPlugin('system', 'yametrikinsert');
                $subject = $container->get(DispatcherInterface::class);

                $plugin = new Plugin($subject, (array)$plugin);
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseDriver::class));

                return $plugin;
            }
        );
    }
};