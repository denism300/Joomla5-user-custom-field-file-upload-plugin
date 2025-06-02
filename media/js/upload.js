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

document.addEventListener('DOMContentLoaded', () => {
    const token = Joomla.getOptions('csrf.token');
    const system_path = Joomla.getOptions('system.paths');
    const upload_url = 'index.php?option=com_ajax&plugin=upload&group=fields&format=raw';

    document.querySelectorAll('.e24-fuf-upload').forEach(fufWrap => {
        const thisId = fufWrap.getAttribute('id');
        const fieldId = fufWrap.dataset.id ?? 0;
        const fileTypes = fufWrap.dataset.types ?? '.jpg,.jpeg,.png,.gif';
        const fileSize = fufWrap.dataset.size ?? 5;
        const fileCount = fufWrap.dataset.count ?? 1;
        const fileFolder = fufWrap.dataset.folder ?? 'media/upload';
        const currentValue = fufWrap.dataset.value ?? '';
        const fieldRequired = fufWrap.dataset.required ?? 0;
        const previewHtml = getPreviewContainerHTML();

        let currentFiles = [];

        if (currentValue) {
            try {
                currentFiles = JSON.parse(currentValue);
            } catch (e) {
                console.error('Invalid JSON in data-value:', currentValue);
            }
        }

        if (thisId) {
            const ec24FufDropzone = new Dropzone(`#${thisId}`, {
                url: system_path.baseFull + upload_url,
                previewTemplate: previewHtml,
                maxFilesize: parseInt(fileSize),
                uploadMultiple: parseInt(fileCount) > 1,
                maxFiles: parseInt(fileCount),
                acceptedFiles: fileTypes,
                autoProcessQueue: true,
                parallelUploads: parseInt(fileCount) <= 5 ? parseInt(fileCount) : 5,
                filesizeBase: 1024,
                createImageThumbnails: false,
                previewsContainer: fufWrap.querySelector('.e24-fuf-uploades-items'),
                timeout: 0,
                dictFallbackMessage: Joomla.JText._("PLG_FIELDS_UPLOAD_FALLBACK_MESSAGE"),
                dictFileTooBig: Joomla.JText._("PLG_FIELDS_UPLOAD_FILE_TOO_BIG"),
                dictInvalidFileType: Joomla.JText._("PLG_FIELDS_UPLOAD_INVALID_FILE"),
                dictResponseError: Joomla.JText._("PLG_FIELDS_UPLOAD_RESPONSE_ERROR"),
                dictCancelUpload: Joomla.JText._("PLG_FIELDS_UPLOAD_CANCEL_UPLOAD"),
                dictCancelUploadConfirmation: Joomla.JText._("PLG_FIELDS_UPLOAD_CANCEL_UPLOAD_CONFIRMATION"),
                dictRemoveFile: Joomla.JText._("PLG_FIELDS_UPLOAD_REMOVE_FILE"),
                dictMaxFilesExceeded: Joomla.JText._("PLG_FIELDS_UPLOAD_MAX_FILES_EXCEEDED"),
                dictRemoveFileConfirmation: Joomla.JText._("PLG_FIELDS_UPLOAD_REMOVE_FILE_CONFIRM"),
                headers: {
                    'X-CSRF-Token': token
                },
                init: function () {
                    const inputName = this.options.previewsContainer.closest('.e24-fuf-upload').dataset.field;
                    let existingFileCount = 0;

                    currentFiles.forEach(file => {
                        const mockFile = {
                            name: file.name,
                            size: file.size,
                            accepted: true
                        };

                        this.emit("addedfile", mockFile);
                        this.emit("complete", mockFile);
                        this.emit("success", mockFile);

                        this.files.push(mockFile);

                        createHiddenFileField(mockFile.previewElement, `${inputName}[${existingFileCount}]`, file.path);

                        existingFileCount++;
                        this.options.maxFiles -= 1;

                        manageRequiredField(fufWrap, 'hide');
                    });
                }
            });

            ec24FufDropzone.on('sending', function (file, xhr, form) {
                form.append('id', fieldId);
                form.append(token, 1);
            });

            ec24FufDropzone.on('complete', function (file) {
                if (file.status === 'success') {
                    try {
                        const decoded = JSON.parse(file.xhr.response);
                        const filesItems = this.options.previewsContainer.querySelectorAll('.e24-fuf-file');
                        const indexItem = Array.prototype.indexOf.call(filesItems, file.previewElement);
                        const inputName = this.options.previewsContainer.closest('.e24-fuf-upload').dataset.field;

                        createHiddenFileField(file.previewElement, `${inputName}[${indexItem}]`, decoded.file);

                        const uploadedFiles = ec24FufDropzone.files.length;
                        customSprintfCount(fufWrap, uploadedFiles, this.options.maxFiles);

                        if (uploadedFiles >= this.options.maxFiles) {
                            ec24FufDropzone.disable();
                        }

                        manageRequiredField(fufWrap, 'hide');
                    } catch (e) {
                        alert("Error! " + e);
                    }
                }
            });

            ec24FufDropzone.on('removedfile', function (file) {
                const uploadedFiles = ec24FufDropzone.files.length;

                if (file.size !== 0 && file.accepted && file.xhr && file.xhr.response) {
                    try {
                        const decoded = JSON.parse(file.xhr.response);
                        const request = new XMLHttpRequest();

                        request.onload = function () {
                            if (!(request.status >= 200 && request.status < 300)) {
                                alert(request.responseText);
                            }
                        };

                        request.open('POST', system_path.baseFull + upload_url);
                        request.setRequestHeader("X-CSRF-Token", token);

                        const formData = new FormData();
                        formData.append('file', decoded.file_encode);
                        formData.append('action', 'DeleteFile');

                        request.send(formData);
                    } catch (e) {
                        alert("Error! " + e);
                    }
                }

                if (uploadedFiles < this.options.maxFiles) {
                    ec24FufDropzone.enable();
                }

                if (uploadedFiles === 0 && fieldRequired !== 0) {
                    manageRequiredField(fufWrap, 'show');
                }

                customSprintfCount(fufWrap, uploadedFiles, this.options.maxFiles);

                if (file.previewElement) {
                    file.previewElement.remove();
                }
            });
        }
    });
});

function getPreviewContainerHTML() {
    return `<div class="e24-fuf-file">
                <div class="e24-fuf-status" data-dz-status></div>
                <div class="e24-fuf-details">
                    <div class="e24-fuf-name dz-filename"><span class="dz-title" data-dz-name></span></div>
                    <div class="e24-fuf-error dz-error-message"><span data-dz-errormessage></span></div>
                    <div class="e24-fuf-progress dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
                </div>
                <div class="e24-fuf-right">
                    <span class="e24-fuf-size dz-size" data-dz-size></span>
                    <span class="e24-fuf-controls">
                        <a href="#" class="e24-fuf_upload_delete" title="${Joomla.JText._('PLG_FIELDS_UPLOAD_DELETE_FILE')}" data-dz-remove>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20">
                                <path d="M13.05 42q-1.2 0-2.1-.9-.9-.9-.9-2.1V10.5H8v-3h9.4V6h13.2v1.5H40v3h-2.05V39q0 1.2-.9 2.1-.9.9-2.1.9Zm21.9-31.5h-21.9V39h21.9Zm-16.6 24.2h3V14.75h-3Zm8.3 0h3V14.75h-3Zm-13.6-24.2V39Z"></path>
                            </svg>
                        </a>
                    </span>
                </div>
            </div>`.trim();
}

function manageRequiredField(container, status = 'hide') {
    const field = container.previousElementSibling;

    if (field) {
        if (status === 'hide') {
            field.removeAttribute('required');
            field.classList.remove('required');
        } else {
            field.setAttribute('required', 'required');
            field.classList.add('required');
        }
    }
}

function customSprintfCount(container, ...args) {
    const countWrap = container.querySelector('.e24-fuf-upload-count');
    if (!countWrap) return;

    let i = 0;
    const string = Joomla.JText._("PLG_FIELDS_UPLOAD_FILES_COUNT").replace(/%d/g, () => args[i++] ?? 0);

    countWrap.querySelector('span').innerText = string;
}

function createHiddenFileField(container = null, name = null, value = null) {
    if (!container || !name || !value) return;

    const hiddenInput = document.createElement('input');
    hiddenInput.setAttribute('type', 'hidden');
    hiddenInput.setAttribute('name', name);
    hiddenInput.setAttribute('value', value);

    container.append(hiddenInput);
}
