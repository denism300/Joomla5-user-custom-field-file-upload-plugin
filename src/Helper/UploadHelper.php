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

namespace Joomla\Plugin\Fields\Upload\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class UploadHelper
{
    /**
     * Temp folder where files are uploaded
     * prior to them being saved in the final directory.
     * 
     * @var             string
     * 
     * @since           1.0
     */
    public static $temp_folder = 'media/upload/tmp';

    /**
     * The Upload action called by the AJAX hanler
     *
     * @return          void
     * 
     * @since           1.0
     */
    public static function actionUploadFile()
    {
        $input = Factory::getApplication()->input;

        if (!$field_id = $input->getInt('id')) {
            self::uploadDie('PLG_FIELDS_UPLOAD_ERROR');
        }

        $field_settings = self::getCustomFieldData($field_id);
        $allowed_types  = $field_settings->get('accept');
        $destination    = $field_settings->get('upload_folder') ?: UploadHelper::$temp_folder;

        if ($allowed_types) {
            $allowed_types = explode(',', str_replace(' ', '', $allowed_types));
        } else {
            $allowed_types = ['.jpg', '.jpeg', '.png', '.gif'];
        }

        if ($field_settings) {
            $file = $input->files->get('file', null, 'cmd');

            if ($file) {
                $first_property = array_pop($file);

                if (is_array($first_property)) {
                    $file = $first_property;
                }

                if (!in_array('.' . pathinfo($file['name'], PATHINFO_EXTENSION), $allowed_types)) {
                    self::uploadDie('PLG_FIELDS_UPLOAD_INVALID_FILE');
                }

                try {
                    if (!file_exists($destination)) {
                        mkdir($destination, 0755, true);
                    }

                    $random_prefix     = bin2hex(random_bytes(3));
                    $file_name         = $random_prefix . '_' . File::makeSafe($file['name']);
                    $upload_folder     = implode(DIRECTORY_SEPARATOR, [JPATH_ROOT, $destination, $file_name]);

                    if (File::upload($file['tmp_name'], $upload_folder, true, false, [])) {
                        $uploaded_filename = str_replace([JPATH_SITE, JPATH_ROOT], '', $upload_folder);

                        $response = [
                            'file'        => $uploaded_filename,
                            'file_encode' => base64_encode($uploaded_filename),
                            'url'         => UploadHelper::getAbsoluteUrl($uploaded_filename)
                        ];

                        header('Content-Type: application/json');

                        echo json_encode($response);

                        jexit();
                    } else {
                        self::uploadDie('PLG_FIELDS_UPLOAD_ERROR_CANNOT_UPLOAD_FILE');
                    }
                } catch (\Throwable $th) {
                    self::uploadDie($th->getMessage());
                }
            } else {
                self::uploadDie('PLG_FIELDS_UPLOAD_ERROR_INVALID_FILE');
            }
        } else {
            self::uploadDie('PLG_FIELDS_UPLOAD_ERROR_INVALID_FIELD');
        }
    }

    /**
     * The delete action called by the AJAX hanlder
     *
     * @return          void
     * 
     * @since           1.0
     */
    public static function actionDeleteFile()
    {
        $input  = Factory::getApplication()->input;
        $result = false;

        if (!$filename = base64_decode($input->get('file', '', 'BASE64'))) {
            self::uploadDie('PLG_FIELDS_UPLOAD_ERROR_INVALID_FILE');
        }

        $file = Path::clean(implode(DIRECTORY_SEPARATOR, [JPATH_ROOT, $filename]));

        if (!is_file($file)) {
            $result = false;
        }

        $result = File::delete($file);

        // Delete the uploaded file
        echo json_encode([
            'success' => $result
        ]);
    }

    /**
     * Pull Custom Field Data
     *
     * @param           integer $id The Custom Field primary key
     *
     * @return          object
     * 
     * @since           1.0
     */
    public static function getCustomFieldData($id)
    {
        $db    = Factory::getDbo();
        $query = $db->getQuery(true);

        $query
            ->select($db->quoteName(['fieldparams']))
            ->from($db->quoteName('#__fields'))
            ->where($db->quoteName('id') . ' = ' . $id)
            ->where($db->quoteName('type') . ' = ' . $db->quote('upload'))
            ->where($db->quoteName('state') . ' = 1');

        $db->setQuery($query);

        if (!$result = $db->loadResult()) {
            return;
        }

        return new Registry($result);
    }

    /**
     * Return absolute full URL of a path
     *
     * @param	string	$path
     *
     * @return	string
     */
    public static function getAbsoluteUrl($path)
    {
        $path = str_replace([JPATH_SITE, JPATH_ROOT, URI::root()], '', $path);
        $path = Path::clean($path);

        // Convert Windows Path to Unix
        $path = str_replace('\\', '/', $path);

        $path = ltrim($path, '/');
        $path = URI::root() . $path;

        return $path;
    }


    /**
     * DropzoneJS detects errors based on the response error code.
     *
     * @param           string $error_message
     *
     * @return          void
     * 
     * @since           1.0
     */
    public static function uploadDie($error_message)
    {
        http_response_code('500');

        die(Text::_($error_message));
    }
}
