/**
 * Copyright (c) 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

var bootstrap = require('bootstrap');

export class FileUploadHandler {
    static #defaultOptions = FileUploadHandler.#createDefaultOptions();

    static #createDefaultOptions() {
        const base = '.js-file-upload-setting';
        return {
            rootSelector: '.configuration-management',
            baseSelector: base,
            triggerSelector: '.js-file-upload-trigger',
            submitSelector: '.js-file-upload-submit',
            formSelector: '.js-file-upload-form',
            spinnerSelector: '.js-upload-spinner',
            errorsSelector: '.js-upload-errors',
            fileInputSelector: '.js-file-input',
            valueInputSelector: '.js-file-setting-value',
            previewSelector: '.js-file-setting-preview',
            previewClass: 'file-setting-preview js-file-setting-preview',
            modalIdAttribute: 'data-modal-id',
            uploadUrlAttribute: 'data-upload-url',
            labelChangeAttribute: 'data-label-change',
        };
    }

    constructor(options = {}) {
        this.options = { ...FileUploadHandler.#defaultOptions, ...options };
        this.init();
    }

    init() {
        if (!document.querySelector(this.options.rootSelector)) {
            return;
        }

        for (const setting of document.querySelectorAll(this.options.baseSelector)) {
            this.attachHandlers(setting);
        }
    }

    attachHandlers(setting) {
        const modalElement = document.getElementById(setting.getAttribute(this.options.modalIdAttribute));

        if (!modalElement) {
            return;
        }

        setting.querySelector(this.options.triggerSelector)?.addEventListener('click', () => {
            new bootstrap.Modal(modalElement).show();
        });

        modalElement.querySelector(this.options.submitSelector)?.addEventListener('click', () => {
            this.handleUpload(setting, modalElement);
        });
    }

    async handleUpload(setting, modalElement) {
        const form = modalElement.querySelector(this.options.formSelector);
        const spinner = modalElement.querySelector(this.options.spinnerSelector);
        const errorsContainer = modalElement.querySelector(this.options.errorsSelector);
        const submitButton = modalElement.querySelector(this.options.submitSelector);
        const fileInput = form.querySelector(this.options.fileInputSelector);

        errorsContainer.style.display = 'none';
        errorsContainer.innerHTML = '';

        if (!fileInput?.files?.length) {
            this.showErrors(errorsContainer, [errorsContainer.dataset.error]);

            return;
        }

        spinner.style.display = 'inline';
        submitButton.disabled = true;

        try {
            const formData = new FormData(form);
            formData.set('file', fileInput.files[0]);

            const response = await fetch(setting.getAttribute(this.options.uploadUrlAttribute), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();

            if (!data.success) {
                this.showErrors(errorsContainer, data.errors);

                return;
            }

            this.onUploadSuccess(setting, modalElement, data.url);
        } catch (error) {
            this.showErrors(errorsContainer, [error.message]);
        } finally {
            spinner.style.display = 'none';
            submitButton.disabled = false;
        }
    }

    onUploadSuccess(setting, modalElement, url) {
        const valueInput = setting.querySelector(this.options.valueInputSelector);
        valueInput.value = url;

        valueInput.dispatchEvent(new Event('input', { bubbles: true }));

        const trigger = setting.querySelector(this.options.triggerSelector);
        let preview = setting.querySelector(this.options.previewSelector);

        if (!preview) {
            preview = document.createElement('div');
            preview.className = this.options.previewClass;
            // Insert after the trigger so both sit side by side in the flex row
            trigger.insertAdjacentElement('afterend', preview);
        }

        preview.innerHTML = `<img src="${this.escapeHtml(url)}" alt="" class="img-thumbnail">`;

        trigger.innerHTML = `<span class="material-symbols-outlined">upload</span> ${this.escapeHtml(trigger.getAttribute(this.options.labelChangeAttribute))}`;

        bootstrap.Modal.getInstance(modalElement)?.hide();
    }

    showErrors(container, errors) {
        container.innerHTML = errors
            .map((msg) => `<div class="alert alert-danger">${this.escapeHtml(msg)}</div>`)
            .join('');
        container.style.display = 'block';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;

        return div.innerHTML;
    }
}
