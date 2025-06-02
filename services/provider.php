<?php

/**
 * @package         File Upload Field
 * @version         1.0
 * 
 * @author          Denis Mukhin - info@e-commerce24.ru
 * @link            https://e-commerce24.ru/
 * @copyright       Copyright (c) 2025 Denis Mukhin. All rights reserved.
 * @license         GNU GPLv3 http://www.gnu.org/licenses/gpl.html or later
 * @since           1.0
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Fields\Upload\Extension\Upload;

defined('_JEXEC') or die;

return new class() implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Upload(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('fields', 'upload')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
