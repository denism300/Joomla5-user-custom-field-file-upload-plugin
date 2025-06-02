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

namespace Joomla\Plugin\Fields\Upload\Fields;

use Joomla\CMS\Form\Field\FileField;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;
use Joomla\Plugin\Fields\Upload\Helper\UploadHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class UploadField extends FileField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.7.0
     */
    protected $type = 'Upload';

    protected function getInput()
    {
        $file_types    = $this->accept ? str_replace(' ', '', $this->accept) : '.jpg,.jpeg,.png,.gif';
        $file_count    = $this->getAttribute('limit_files', 1);
        $file_folder   = $this->getAttribute('upload_folder', '');
        $file_size     = $this->getAttribute('max_file_size', 5);
        $field_id      = $this->getAttribute('field_id', '');
        $field_value   = $this->prepareValue($this->value);
        $current_count = count($field_value) ?? 0;
        $output        = '';

        if ($this->required) {
            $output .= '<input type="hidden" id="' . str_replace('[]', '', $this->id) . '" required class="required">';
        }

        $output .= '<div id="e24-fuf-upload-' . bin2hex(random_bytes(5)) . '" class="e24-fuf-upload"';
        $output .= ' data-id="' . $field_id . '"';
        $output .= ' data-field="' . $this->name . '"';
        $output .= ' data-disabled="' . ($this->disabled ? '1' : '0') . '"';
        $output .= ' data-required="' . ($this->required ? '1' : '0') . '"';
        $output .= ' data-types="' . $file_types . '"';
        $output .= ' data-count="' . $file_count . '"';
        $output .= ' data-size="' . $file_size . '"';
        $output .= ' data-value="' . (count($field_value) ? htmlspecialchars(json_encode($field_value), ENT_QUOTES, 'UTF-8') : '') . '"';
        $output .= ' data-folder="' . $file_folder . '">';
        $output .= '<div class="dz-message">';
        $output .= '<span>' . Text::_('PLG_FIELDS_UPLOAD_DRAG_AND_DROP_FILES') . '</span>';
        $output .= '<span class="upload-browse">' . Text::_('PLG_FIELDS_UPLOAD_BROWSE') . '</span>';
        $output .= '</div>';
        $output .= '<div class="e24-fuf-upload-count">';
        $output .= '<span>' . Text::sprintf('PLG_FIELDS_UPLOAD_FILES_COUNT', $current_count, $file_count) . '</span>';
        $output .= '</div>';
        $output .= '<div class="e24-fuf-uploades-items"></div>';
        $output .= '</div>';

        return $output;
    }

    protected function prepareValue($values = null)
    {
        if (!$values) {
            return array();
        }

        $files = array();

        if (!is_array($values)) {
            $files[] = $values;
        } else {
            $files = $values;
        }

        $result = array();

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $file_path = Path::clean(JPATH_ROOT . '/' . $file);
            $exists    = is_file($file_path);
            $file_size = $exists ? filesize($file_path) : 0;

            $result[] = [
                'name'    => basename($file_path),
                'path'    => $file,
                'encoded' => base64_encode($file),
                'url'     => UploadHelper::getAbsoluteUrl($file_path),
                'size'    => $file_size,
                'exists'  => $exists
            ];
        }

        return $result;
    }
}
