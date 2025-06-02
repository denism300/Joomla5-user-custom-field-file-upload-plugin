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

namespace Joomla\Plugin\Fields\Upload\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\Fields\Administrator\Plugin\FieldsPlugin;
use Joomla\Plugin\Fields\Upload\Helper\UploadHelper;
use Joomla\CMS\Log\Log;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Fields Upload Plugin
 *
 * @since  3.7.0
 */
final class Upload extends FieldsPlugin implements SubscriberInterface
{
    //use DatabaseAwareTrait;
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onAjaxUpload' => 'onAjaxUpload',
        ]);
    }

    /**
     * Transforms the field into a DOM XML element and appends it as a child on the given parent.
     *
     * @param   \stdClass    $field   The field.
     * @param   \DOMElement  $parent  The field node parent.
     * @param   Form         $form    The form.
     *
     * @return  ?\DOMElement
     *
     * @since   1.0.0
     */
    public function onCustomFieldsPrepareDom($field, \DOMElement $parent, Form $form)
    {
        $fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

        if ($field->type !== 'upload') {
            return;
        }

        if (!$fieldNode) {
            return $fieldNode;
        }

        Text::script('PLG_FIELDS_UPLOAD_FILETOOBIG');
        Text::script('PLG_FIELDS_UPLOAD_INVALID_FILE');
        Text::script('PLG_FIELDS_UPLOAD_FALLBACK_MESSAGE');
        Text::script('PLG_FIELDS_UPLOAD_RESPONSE_ERROR');
        Text::script('PLG_FIELDS_UPLOAD_CANCEL_UPLOAD');
        Text::script('PLG_FIELDS_UPLOAD_CANCEL_UPLOAD_CONFIRMATION');
        Text::script('PLG_FIELDS_UPLOAD_REMOVE_FILE');
        Text::script('PLG_FIELDS_UPLOAD_MAX_FILES_EXCEEDED');
        Text::script('PLG_FIELDS_UPLOAD_FILE_MISSING');
        Text::script('PLG_FIELDS_UPLOAD_REMOVE_FILE_CONFIRM');
        Text::script('PLG_FIELDS_UPLOAD_FILES_COUNT');

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

        $wa->registerAndUseScript('plg.fields.upload.dropzone', 'media/plg_fields_upload/js/dropzone.min.js', ['version' => '6.0.0-beta.2'], [], []);
        $wa->registerAndUseScript('plg.fields.upload.script', 'media/plg_fields_upload/js/upload.js', ['version' => '1.0'], [], ['plg.fields.upload.dropzone']);

        $wa->registerAndUseStyle('plg.fields.upload.dropzone', 'media/plg_fields_upload/css/dropzone.min.css', ['version' => '6.0.0-beta.2'], [], []);
        $wa->registerAndUseStyle('plg.fields.upload.style', 'media/plg_fields_upload/css/upload.css', ['version' => '1.0'], [], ['plg.fields.upload.dropzone']);

        $fieldNode->setAttribute('field_id', $field->id);

        FormHelper::addFieldPrefix('Joomla\Plugin\Fields\Upload\Fields');

        return $fieldNode;
    }

    /**
     * Handle AJAX endpoint
     *
     * @return void
     * 
     * @since   1.0.0
     */
    public function onAjaxUpload(Event $event): void
    {
        if (!Session::checkToken()) {
            UploadHelper::uploadDie(Text::_('JINVALID_TOKEN'));
        }

        $action = 'action' . ucfirst(Factory::getApplication()->input->get('action', 'uploadFile'));

        if (!method_exists(UploadHelper::class, $action)) {
            UploadHelper::uploadDie('Invalid endpoint');
        }

        UploadHelper::$action();
    }
}
