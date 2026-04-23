/**
 * Copyright (c) 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

'use strict';

class JsonEditor {
    #editors = [];

    constructor() {
        document.querySelectorAll('[data-json-editor]').forEach((container) => {
            this.#initEditor(container);
        });
    }

    #initEditor(container) {
        const source = container.querySelector('[data-json-source]');
        const code = container.querySelector('[data-json-code]');
        const status = container.querySelector('[data-json-status]');
        const formatButton = container.querySelector('[data-json-format]');
        const collapseButton = container.querySelector('[data-json-collapse]');

        if (!source || !code) {
            return;
        }

        this.#renderHighlighted(code, source.value);
        this.#validateAndUpdateStatus(source.value, status);

        source.addEventListener('input', () => {
            this.#renderHighlighted(code, source.value);
            this.#validateAndUpdateStatus(source.value, status);
        });

        code.addEventListener('input', () => {
            const raw = code.textContent || '';

            source.value = raw;
            source.dispatchEvent(new Event('change', { bubbles: true }));

            this.#validateAndUpdateStatus(raw, status);
        });

        code.addEventListener('keydown', (event) => {
            if (event.key === 'Tab') {
                event.preventDefault();

                document.execCommand('insertText', false, '  ');
            }
        });

        formatButton?.addEventListener('click', () => {
            this.#format(source, code, status);
        });

        collapseButton?.addEventListener('click', () => {
            this.#minify(source, code, status);
        });

        this.#editors.push({ container, source, code, status });
    }

    #format(source, code, status) {
        const raw = source.value;

        try {
            const parsed = JSON.parse(raw);
            const formatted = JSON.stringify(parsed, null, 2);

            source.value = formatted;
            source.dispatchEvent(new Event('change', { bubbles: true }));

            this.#renderHighlighted(code, formatted);
            this.#setStatus(status, 'valid');
        } catch (error) {
            this.#setStatus(status, 'invalid', error.message);
        }
    }

    #minify(source, code, status) {
        const raw = source.value;

        try {
            const parsed = JSON.parse(raw);
            const minified = JSON.stringify(parsed);

            source.value = minified;
            source.dispatchEvent(new Event('change', { bubbles: true }));

            this.#renderHighlighted(code, minified);
            this.#setStatus(status, 'valid');
        } catch (error) {
            this.#setStatus(status, 'invalid', error.message);
        }
    }

    #validateAndUpdateStatus(raw, status) {
        if (!status || raw.trim() === '') {
            this.#setStatus(status, 'empty');

            return;
        }

        try {
            JSON.parse(raw);
            this.#setStatus(status, 'valid');
        } catch (error) {
            this.#setStatus(status, 'invalid', error.message);
        }
    }

    #setStatus(status, state, message) {
        if (!status) {
            return;
        }

        status.className = 'json-editor__status';

        if (state === 'valid') {
            status.classList.add('json-editor__status--valid');
            status.textContent = 'Valid JSON';

            return;
        }

        if (state === 'invalid') {
            status.classList.add('json-editor__status--invalid');
            status.textContent = message || 'Invalid JSON';

            return;
        }

        status.textContent = '';
    }

    #renderHighlighted(code, raw) {
        const escaped = this.#escapeHtml(raw);

        code.innerHTML = escaped
            .replace(/"([^"\\]|\\.)*"\s*:/g, '<span class="json-key">$&</span>')
            .replace(/:\s*"([^"\\]|\\.)*"/g, (match) => {
                return ': <span class="json-string">' + match.slice(2) + '</span>';
            })
            .replace(/:\s*(-?\d+\.?\d*)/g, ': <span class="json-number">$1</span>')
            .replace(/:\s*(true|false)/g, ': <span class="json-boolean">$1</span>')
            .replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
    }

    #escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;

        return div.innerHTML;
    }
}
document.addEventListener('DOMContentLoaded', () => {
    new JsonEditor();
});
