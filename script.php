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

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class() implements ServiceProviderInterface {

    public function register(Container $container): void
    {
        $container->set(InstallerScriptInterface::class, new class($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
            /**
             * The application object
             *
             * @var  AdministratorApplication
             *
             * @since  1.0.0
             */
            protected AdministratorApplication $app;

            /**
             * The Database object.
             *
             * @var   DatabaseDriver
             *
             * @since  1.0.0
             */
            protected DatabaseDriver $db;

            /**
             * Minimum Joomla version required to install the extension.
             *
             * @var  string
             *
             * @since  1.0.0
             */
            protected string $minimumJoomla = '4.2';

            /**
             * Minimum PHP version required to install the extension.
             *
             * @var  string
             *
             * @since  1.0.0
             */
            protected string $minimumPhp = '7.4';

            /**
             * @var array $providersInstallationMessageQueue
             * @since 2.0.3
             */
            protected $providersInstallationMessageQueue = [];

            /**
             * Constructor.
             *
             * @param   AdministratorApplication  $app  The application object.
             *
             * @since 1.0.0
             */
            public function __construct(AdministratorApplication $app)
            {
                $this->app = $app;
                $this->db  = Factory::getContainer()->get('DatabaseDriver');
            }

            /**
             * Function called after the extension is installed.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function install(InstallerAdapter $adapter): bool
            {
                $this->enablePlugin($adapter);

                return true;
            }

            /**
             * Function called after the extension is updated.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function update(InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Function called after the extension is uninstalled.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function uninstall(InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Function called before extension installation/update/removal procedure commences.
             *
             * @param   string            $type     The type of change (install or discover_install, update, uninstall)
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function preflight(string $type, InstallerAdapter $adapter): bool
            {
                // Check compatible
                if (!$this->checkCompatible($adapter->getElement())) {
                    return false;
                }

                return true;
            }

            /**
             * Function called after extension installation/update/removal procedure commences.
             *
             * @param   string            $type     The type of change (install or discover_install, update, uninstall)
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   1.0.0
             */
            public function postflight(string $type, InstallerAdapter $adapter): bool
            {
                return true;
            }

            /**
             * Enable plugin after installation.
             *
             * @param   InstallerAdapter  $adapter  Parent object calling object.
             *
             * @since  1.0.0
             */
            protected function enablePlugin(InstallerAdapter $adapter)
            {
                // Prepare plugin object
                $plugin          = new \stdClass();
                $plugin->type    = 'plugin';
                $plugin->element = $adapter->getElement();
                $plugin->folder  = (string) $adapter->getParent()->manifest->attributes()['group'];
                $plugin->enabled = 1;

                // Update record
                $this->db->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);
            }

            /**
             * Method to check compatible.
             *
             * @return  boolean True on success, False on failure.
             *
             * @throws  Exception
             *
             * @since  1.0.0
             */
            protected function checkCompatible(string $element): bool
            {
                $element = 'PLG_' . strtoupper($element);
                // Check joomla version
                if (!(new Version)->isCompatible($this->minimumJoomla)) {
                    $this->app->enqueueMessage(
                        Text::sprintf($element . '_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
                        'error'
                    );

                    return false;
                }

                // Check PHP
                if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0)) {
                    $this->app->enqueueMessage(
                        Text::sprintf($element . '_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
                        'error'
                    );

                    return false;
                }

                return true;
            }
        });
    }
};
