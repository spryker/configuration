/**
 * Copyright (c) 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

'use strict';

class ConfigurationManagement {
    #currentScope;
    #currentScopeIdentifier;
    #readOnly;
    #changes = new Map();
    #originalValues = new Map();
    #dependencyMap = new Map();
    #reverseDependencyMap = new Map();
    #visibilityCache = new Map();
    #rowCache = new Map();
    #searchDebounceTimer = null;
    #initialized = false;
    #i18n = {};

    constructor(root) {
        const dataset = root.dataset;

        this.#currentScope = dataset.scope;
        this.#currentScopeIdentifier = dataset.scopeIdentifier || null;
        this.#readOnly = dataset.readOnly === '1';
        this.#i18n = {
            resetConfirm: dataset.i18nResetConfirm,
            unsavedScopeConfirm: dataset.i18nUnsavedScopeConfirm,
            unsavedTabConfirm: dataset.i18nUnsavedTabConfirm,
            dependentResetConfirmTemplate: dataset.i18nDependentResetConfirmTemplate,
            saveErrorPrefix: dataset.i18nSaveErrorPrefix,
            saveErrorGeneric: dataset.i18nSaveErrorGeneric,
            savingLabel: dataset.i18nSavingLabel,
            saveLabel: dataset.i18nSaveLabel,
        };

        this.#init();
    }

    #init() {
        this.#buildRowCache();
        this.#moveScopeSelectors();
        this.#buildDependencyMaps();
        this.#evaluateAllDependencies();

        if (this.#readOnly) {
            this.#bindReadOnlyEvents();

            return;
        }

        this.#storeOriginalValues();
        this.#initialized = true;
        this.#bindEvents();
    }

    destroy() {
        if (this.#searchDebounceTimer) {
            clearTimeout(this.#searchDebounceTimer);
            this.#searchDebounceTimer = null;
        }
    }

    // Build a cache of { row, input } by settingKey to avoid repeated DOM queries.
    #buildRowCache() {
        document.querySelectorAll('.setting-row').forEach((row) => {
            const settingKey = row.dataset.settingKey;

            if (!settingKey) {
                return;
            }

            this.#rowCache.set(settingKey, {
                row,
                input: row.querySelector('.config-input'),
            });
        });
    }

    #moveScopeSelectors() {
        if (window.innerWidth > 768) {
            return;
        }

        const scopeSelector = document.querySelector('.js-scope-selector');
        scopeSelector.classList.add('scope-selector--alt');
        const scopeSelectorMobilPlaceholder = document.querySelector('.js-scope-selector-placeholder');
        scopeSelectorMobilPlaceholder.append(scopeSelector);
    }

    #storeOriginalValues() {
        document.querySelectorAll('.config-input').forEach((input) => {
            if (input.type === 'radio' && !input.checked) {
                return;
            }

            const settingKey = this.#getSettingKey(input);
            const hasCustomValue = input.dataset.hasCustomValue === '1';

            this.#originalValues.set(settingKey, {
                value: input.dataset.originalValue || '',
                displayValue: this.#readValueFromInput(input),
                hasCustomValue,
            });
        });
    }

    #buildDependencyMaps() {
        this.#rowCache.forEach(({ row }, settingKey) => {
            const dependenciesJson = row.dataset.dependencies;

            if (!dependenciesJson || dependenciesJson === '[]') {
                return;
            }

            let dependencies;

            try {
                dependencies = JSON.parse(dependenciesJson);
            } catch {
                return;
            }

            this.#dependencyMap.set(settingKey, dependencies);

            dependencies.forEach((dependency) => {
                if (!dependency.when) {
                    return;
                }

                this.#extractDependentSettings(dependency.when).forEach((requiredKey) => {
                    if (!this.#reverseDependencyMap.has(requiredKey)) {
                        this.#reverseDependencyMap.set(requiredKey, []);
                    }

                    this.#reverseDependencyMap.get(requiredKey).push(settingKey);
                });
            });
        });
    }

    #extractDependentSettings(whenClause) {
        const settings = new Set();

        (whenClause.any || []).forEach((condition) => {
            if (condition.setting) {
                settings.add(condition.setting);
            }
        });

        (whenClause.all || []).forEach((condition) => {
            if (condition.setting) {
                settings.add(condition.setting);
            }
        });

        return Array.from(settings);
    }

    #evaluateAllDependencies() {
        this.#rowCache.forEach((_, settingKey) => this.#evaluateDependency(settingKey));
    }

    #evaluateDependency(settingKey) {
        const cached = this.#rowCache.get(settingKey);

        if (!cached) {
            return;
        }

        const dependencies = this.#dependencyMap.get(settingKey);

        if (!dependencies || dependencies.length === 0) {
            cached.row.style.display = '';
            this.#visibilityCache.set(settingKey, true);

            return;
        }

        const wasVisible = this.#visibilityCache.get(settingKey) ?? false;
        const shouldShow = dependencies.some(
            (dependency) => dependency.when && this.#evaluateWhenClause(dependency.when),
        );

        cached.row.style.display = shouldShow ? '' : 'none';
        this.#visibilityCache.set(settingKey, shouldShow);

        if (!this.#initialized) {
            return;
        }

        if (shouldShow && !wasVisible) {
            this.#markDependentAsChanged(settingKey, cached);
        }

        if (!shouldShow && wasVisible) {
            this.#unmarkDependentChange(settingKey, cached);
        }
    }

    #markDependentAsChanged(settingKey, cached) {
        const { row, input } = cached;
        const currentValue = input ? this.#readValueFromInput(input) : null;

        if (currentValue !== null) {
            this.#changes.set(settingKey, { value: currentValue, useDefault: false });
        }

        row.classList.add('changed', 'changed-by-dependency');
        this.#updateUseDefaultUI(row, false);
        this.#updateSaveBar();
    }

    #unmarkDependentChange(settingKey, cached) {
        if (!cached.row.classList.contains('changed-by-dependency')) {
            return;
        }

        this.#changes.delete(settingKey);
        cached.row.classList.remove('changed', 'changed-by-dependency');
        this.#updateSaveBar();
    }

    #evaluateWhenClause(whenClause) {
        if (whenClause.any) {
            return whenClause.any.some((condition) => this.#evaluateCondition(condition));
        }

        if (whenClause.all) {
            return whenClause.all.every((condition) => this.#evaluateCondition(condition));
        }

        return true;
    }

    #evaluateCondition(condition) {
        if (!condition.setting || !condition.operator) {
            return true;
        }

        const currentValue = this.#getCurrentValue(condition.setting);
        const expectedValue = condition.value;

        switch (condition.operator) {
            case 'equals':
                return String(currentValue) === String(expectedValue);
            case 'not_equals':
                return String(currentValue) !== String(expectedValue);
            case 'greater_than':
                return parseFloat(currentValue) > parseFloat(expectedValue);
            case 'less_than':
                return parseFloat(currentValue) < parseFloat(expectedValue);
            case 'contains':
                return String(currentValue).includes(String(expectedValue));
            case 'in':
                return Array.isArray(expectedValue) && expectedValue.includes(String(currentValue));
            default:
                return true;
        }
    }

    #getCurrentValue(settingKey) {
        const cached = this.#rowCache.get(settingKey);

        if (!cached || !cached.input) {
            return null;
        }

        if (cached.input.type === 'radio') {
            const checkedRadio = cached.row.querySelector('.config-input:checked');

            return checkedRadio ? checkedRadio.value : null;
        }

        return this.#readValueFromInput(cached.input);
    }

    #readValueFromInput(input) {
        if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
            return input.checked ? 'true' : 'false';
        }

        if (input.type === 'radio') {
            if (!input.checked) {
                // For radio we need the checked sibling; caller handles per-group reads via #getCurrentValue
                return input.value;
            }

            return input.value;
        }

        if (input.type === 'select-multiple') {
            return JSON.stringify(Array.from(input.selectedOptions).map((option) => option.value));
        }

        return input.value;
    }

    #applyValueToInput(input, value, settingRow) {
        if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
            input.checked = value === 'true';

            return;
        }

        if (input.type === 'radio') {
            const radios = (settingRow || input.closest('.setting-row')).querySelectorAll('input[type="radio"]');

            radios.forEach((radio) => {
                radio.checked = radio.value === value;
                radio.closest('.radio-option')?.classList.toggle('checked', radio.checked);
            });

            return;
        }

        if (input.type === 'select-multiple') {
            const selectedValues = this.#parseJsonArray(value);

            Array.from(input.options).forEach((option) => {
                option.selected = selectedValues.includes(option.value);
            });

            return;
        }

        input.value = value;
    }

    #parseJsonArray(value) {
        try {
            return JSON.parse(value || '[]');
        } catch {
            return [];
        }
    }

    #checkDependentsBeforeChange(settingKey, callback) {
        const dependents = this.#reverseDependencyMap.get(settingKey);

        if (!dependents || dependents.length === 0) {
            callback();

            return;
        }

        const dependentsWithChanges = dependents.filter((dependentKey) => {
            if (!this.#visibilityCache.get(dependentKey)) {
                return false;
            }

            const original = this.#originalValues.get(dependentKey);
            const change = this.#changes.get(dependentKey);

            // A "changed-by-dependency" row is auto-staged on activation with its default value.
            // It only represents user intent when the staged value differs from the original.
            const hasUserChange = change ? change.useDefault || change.value !== original?.displayValue : false;

            return hasUserChange || (original && original.hasCustomValue);
        });

        if (dependentsWithChanges.length === 0) {
            callback();

            return;
        }

        const dependentNames = dependentsWithChanges.map((dependentKey) => {
            const cached = this.#rowCache.get(dependentKey);
            const header = cached?.row.querySelector('.setting-row__item-header');
            const firstTextNode =
                header &&
                Array.from(header.childNodes).find(
                    (node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '',
                );

            return firstTextNode ? firstTextNode.textContent.trim() : dependentKey;
        });

        const message = this.#i18n.dependentResetConfirmTemplate
            .replace('%settings%', dependentNames.join('\n'))
            .replaceAll('%newline%', '\n');

        if (!confirm(message)) {
            return;
        }

        dependentsWithChanges.forEach((dependentKey) => {
            this.#handleUseDefault(dependentKey);

            const original = this.#originalValues.get(dependentKey);

            if (original) {
                original.hasCustomValue = false;
                this.#originalValues.set(dependentKey, original);
            }
        });

        callback();
    }

    #bindReadOnlyEvents() {
        document.querySelectorAll('.js-nav-tab').forEach((tab) => {
            tab.addEventListener('click', () => this.#handleTabChange(tab.dataset.feature, tab.dataset.tab));
        });

        document.querySelector('.js-scope-combined')?.addEventListener('change', () => this.#handleScopeChange());
        document.querySelector('.js-scope-type')?.addEventListener('change', () => this.#handleScopeChange());
        document.querySelector('.js-scope-identifier')?.addEventListener('change', () => this.#handleScopeChange());

        document
            .querySelector('.js-config-search')
            ?.addEventListener('input', (event) => this.#handleSearch(event.target.value));
    }

    #bindEvents() {
        // Delegate input changes to the document instead of binding per-element listeners.
        document.addEventListener('change', (event) => {
            const input = event.target.closest('.config-input');

            if (!input) {
                return;
            }

            if (input.type === 'radio') {
                this.#handleRadioChange(input);

                return;
            }

            this.#handleInputChange(input);
        });

        document.addEventListener('input', (event) => {
            const input = event.target.closest('.config-input');

            if (!input || input.type === 'checkbox' || input.type === 'radio' || input.type === 'select-multiple') {
                return;
            }

            this.#handleInputChange(input);
        });

        document.querySelectorAll('.js-color-preview').forEach((preview) => {
            const colorInput = preview.querySelector('.js-color-swatch-input');
            const hexPreview = preview.querySelector('.js-color-hex-preview');

            colorInput?.addEventListener('input', () => {
                preview.style.setProperty('--input-color', colorInput.value);

                if (hexPreview) {
                    hexPreview.textContent = colorInput.value;
                }
            });
        });

        // Delegate use-default clicks.
        document.addEventListener('click', (event) => {
            const link = event.target.closest('.use-default-link');

            if (!link) {
                return;
            }

            event.preventDefault();
            this.#handleUseDefault(link.dataset.settingKey);
        });

        document.getElementById('save-configuration')?.addEventListener('click', () => this.#saveConfiguration());
        document.getElementById('reset-changes')?.addEventListener('click', () => this.#resetChanges());

        this.#bindReadOnlyEvents();
    }

    #getSettingKey(input) {
        return input.closest('.setting-row')?.dataset.settingKey;
    }

    #handleInputChange(input) {
        const settingKey = this.#getSettingKey(input);
        const settingRow = input.closest('.setting-row');
        const isDependencyActivated = settingRow?.classList.contains('changed-by-dependency');

        this.#clearRowError(settingRow);

        const defaultValue = settingRow?.dataset.defaultValue ?? null;
        const originalData = this.#originalValues.get(settingKey);
        const currentValue = this.#readValueFromInput(input);

        if (currentValue === originalData.displayValue && !isDependencyActivated) {
            this.#changes.delete(settingKey);
            settingRow?.classList.remove('changed');

            const overridesDefault =
                originalData.hasCustomValue && defaultValue !== null && originalData.displayValue !== defaultValue;

            this.#updateUseDefaultUI(settingRow, overridesDefault);
            this.#evaluateDependenciesForSetting(settingKey);
            this.#updateSaveBar();

            return;
        }

        const previousValue = originalData.displayValue;

        this.#checkDependentsBeforeChange(settingKey, () => {
            this.#changes.set(settingKey, { value: currentValue, useDefault: false });
            settingRow?.classList.add('changed');

            // Dependency-activated settings cannot be independently reset
            const showUseDefault = !isDependencyActivated && defaultValue !== null && currentValue !== defaultValue;
            this.#updateUseDefaultUI(settingRow, showUseDefault);
            this.#evaluateDependenciesForSetting(settingKey);
            this.#updateSaveBar();
        });

        // Revert input if user cancelled the confirmation dialog
        if (!this.#changes.has(settingKey) && !isDependencyActivated) {
            this.#applyValueToInput(input, previousValue, settingRow);
        }
    }

    #evaluateDependenciesForSetting(changedSettingKey) {
        const dependents = this.#reverseDependencyMap.get(changedSettingKey);

        if (!dependents || dependents.length === 0) {
            return;
        }

        dependents.forEach((dependentKey) => this.#evaluateDependency(dependentKey));
    }

    #updateUseDefaultUI(settingRow, showLink) {
        if (!settingRow) {
            return;
        }

        const useDefaultLink = settingRow.querySelector('.use-default-link');
        const overridesBadge = settingRow.querySelector('.js-overrides-default-badge');
        const displayValue = showLink ? 'inline-flex' : 'none';

        if (useDefaultLink) {
            useDefaultLink.style.display = displayValue;
        }

        if (overridesBadge) {
            overridesBadge.style.display = showLink ? 'inline' : 'none';
        }
    }

    #updateFilePreview(settingRow, input) {
        const fileContainer = settingRow.querySelector('.js-file-upload-setting');

        if (!fileContainer) {
            return;
        }

        let preview = fileContainer.querySelector('.js-file-setting-preview');
        const trigger = fileContainer.querySelector('.js-file-upload-trigger');
        const url = input.value;

        if (url) {
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'file-setting-preview js-file-setting-preview';
                trigger.insertAdjacentElement('afterend', preview);
            }

            const img = document.createElement('img');
            img.src = url;
            img.alt = '';
            img.className = 'img-thumbnail';
            img.style.maxHeight = '40px';
            preview.replaceChildren(img);

            if (trigger) {
                this.#setTriggerLabel(trigger, trigger.dataset.labelChange);
            }
        } else {
            if (preview) {
                preview.remove();
            }

            if (trigger) {
                this.#setTriggerLabel(trigger, trigger.dataset.labelUpload);
            }
        }
    }

    #setTriggerLabel(trigger, label) {
        const icon = document.createElement('span');
        icon.className = 'material-symbols-outlined';
        icon.textContent = 'upload';

        trigger.replaceChildren(icon, ' ' + (label || ''));
    }

    #handleRadioChange(input) {
        if (!input.checked) {
            return;
        }

        const radioGroup = input.closest('.radio-group');

        if (radioGroup) {
            radioGroup.querySelectorAll('.radio-option').forEach((option) => option.classList.remove('checked'));
            input.closest('.radio-option')?.classList.add('checked');
        }

        this.#handleInputChange(input);
    }

    #handleUseDefault(settingKey) {
        const cached = this.#rowCache.get(settingKey);

        if (!cached) {
            return;
        }

        const { row: settingRow, input } = cached;
        const inheritedValue = settingRow.dataset.inheritedValue || settingRow.dataset.defaultValue || '';

        if (input) {
            this.#applyValueToInput(input, inheritedValue, settingRow);
            input.dispatchEvent(new Event('input'));
        }

        const originalData = this.#originalValues.get(settingKey);

        if (originalData && originalData.hasCustomValue) {
            // Queue a delete in the regular save flow
            this.#changes.set(settingKey, { value: null, useDefault: true });
            settingRow.classList.add('changed');

            originalData.displayValue = inheritedValue;
            originalData.value = '';
            this.#originalValues.set(settingKey, originalData);
        } else {
            // Simply revert the pending unsaved edit
            this.#changes.delete(settingKey);
            settingRow.classList.remove('changed');
        }

        this.#updateUseDefaultUI(settingRow, false);
        this.#evaluateDependenciesForSetting(settingKey);
        this.#updateSaveBar();
    }

    #updateSaveBar() {
        const changeCount = this.#changes.size;
        const saveBar = document.getElementById('config-save-bar');
        const countSpan = document.getElementById('changes-count');

        saveBar.style.display = changeCount > 0 ? 'block' : 'none';
        countSpan.textContent = changeCount;
    }

    #saveConfiguration() {
        if (this.#changes.size === 0) {
            return;
        }

        const changesArray = Array.from(this.#changes.entries()).map(([key, change]) => ({
            key,
            value: change.value,
            use_default: change.useDefault,
            scope: this.#currentScope,
            scope_identifier: this.#currentScopeIdentifier,
        }));

        const saveButton = document.getElementById('save-configuration');
        saveButton.disabled = true;
        saveButton.innerHTML = this.#i18n.savingLabel;

        fetch('/configuration/manage/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ changes: changesArray }),
        })
            .then((response) => response.json())
            .then((data) => {
                this.#clearValidationErrors();

                if (data.success) {
                    this.#changes.clear();
                    document.querySelectorAll('.setting-row.changed').forEach((row) => row.classList.remove('changed'));
                    this.#updateSaveBar();
                    location.reload();

                    return;
                }

                if (data.errors && Object.keys(data.errors).length > 0) {
                    this.#showValidationErrors(data.errors);

                    return;
                }

                alert(this.#i18n.saveErrorPrefix + (data.message || ''));
            })
            .catch(() => alert(this.#i18n.saveErrorGeneric))
            .finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = this.#i18n.saveLabel;
            });
    }

    #clearValidationErrors() {
        document.querySelectorAll('.setting-row.is-invalid').forEach((row) => row.classList.remove('is-invalid'));
    }

    #clearRowError(row) {
        if (!row) {
            return;
        }

        row.classList.remove('is-invalid');
        row.querySelectorAll('.invalid-feedback').forEach((element) => (element.innerText = ''));
    }

    #showValidationErrors(errors) {
        let firstErrorRow = null;

        Object.entries(errors).forEach(([settingKey, message]) => {
            const cached = this.#rowCache.get(settingKey);

            if (!cached) {
                return;
            }

            cached.row.classList.add('is-invalid');

            const errorElement = cached.row.querySelector('.invalid-feedback');
            errorElement.textContent = message;

            if (!firstErrorRow) {
                firstErrorRow = cached.row;
            }
        });

        if (firstErrorRow) {
            firstErrorRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    #resetChanges() {
        if (!confirm(this.#i18n.resetConfirm)) {
            return;
        }

        this.#clearValidationErrors();
        this.#changes.clear();

        document.querySelectorAll('.config-input').forEach((input) => {
            const settingKey = this.#getSettingKey(input);
            const originalData = this.#originalValues.get(settingKey);

            if (!originalData) {
                return;
            }

            if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                input.checked = originalData.displayValue === 'true';
            } else if (input.type === 'radio') {
                input.checked = input.value === originalData.displayValue;
                input.closest('.radio-option')?.classList.toggle('checked', input.checked);
            } else if (input.type === 'select-multiple') {
                try {
                    const selectedValues = JSON.parse(originalData.displayValue || '[]');
                    Array.from(input.options).forEach((option) => {
                        option.selected = selectedValues.includes(option.value);
                    });
                } catch (e) {
                    // Malformed stored value — leave selection unchanged
                }
            } else {
                input.value = originalData.displayValue;
                input.dispatchEvent(new Event('input'));
            }

            const settingRow = input.closest('.setting-row');

            if (settingRow) {
                this.#updateUseDefaultUI(settingRow, originalData.hasCustomValue);
                this.#updateFilePreview(settingRow, input);
            }
        });

        document.querySelectorAll('.setting-row.changed').forEach((row) => {
            row.classList.remove('changed', 'changed-by-dependency');
        });

        this.#evaluateAllDependencies();
        this.#updateSaveBar();
    }

    #handleScopeChange() {
        if (this.#changes.size > 0 && !confirm(this.#i18n.unsavedScopeConfirm)) {
            return;
        }

        let scope, scopeIdentifier;
        const combinedElement = document.getElementById('scope-combined');

        if (combinedElement) {
            const [scopePart, ...identifierParts] = combinedElement.value.split(':');
            scope = scopePart;
            scopeIdentifier = identifierParts.join(':');
        } else {
            scope = document.getElementById('scope-type').value;
            scopeIdentifier = document.getElementById('scope-identifier')?.value || '';
        }

        const activeTab = document.querySelector('.js-nav-tab.active');
        const currentTab = activeTab?.dataset.tab || '';
        const currentFeature = activeTab?.dataset.feature || '';
        const url = new URL(window.location.href);

        url.searchParams.set('scope', scope);

        if (scopeIdentifier) {
            url.searchParams.set('scope_identifier', scopeIdentifier);
        } else {
            url.searchParams.delete('scope_identifier');
        }

        if (currentFeature) {
            url.searchParams.set('feature', currentFeature);
        }

        if (currentTab) {
            url.searchParams.set('tab', currentTab);
        }

        window.location.href = url.toString();
    }

    #handleTabChange(featureKey, tabKey) {
        if (this.#changes.size > 0 && !confirm(this.#i18n.unsavedTabConfirm)) {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('feature', featureKey);
        url.searchParams.set('tab', tabKey);
        window.location.href = url.toString();
    }

    #handleSearch(searchTerm) {
        const term = searchTerm.toLowerCase().trim();

        this.#filterContentArea(term);

        if (this.#searchDebounceTimer) {
            clearTimeout(this.#searchDebounceTimer);
            this.#searchDebounceTimer = null;
        }

        if (!term) {
            this.#resetSidebarFilter();

            return;
        }

        // Debounce AJAX call for sidebar filtering
        this.#searchDebounceTimer = setTimeout(() => {
            this.#searchSidebar(term);
        }, 300);
    }

    #filterContentArea(term) {
        document.querySelectorAll('.setting-row').forEach((row) => {
            const settingKey = row.dataset.settingKey;

            // Use cached visibility state instead of re-evaluating the full dependency graph.
            if (this.#visibilityCache.get(settingKey) === false) {
                row.style.display = 'none';

                return;
            }

            if (!term) {
                row.style.display = '';

                return;
            }

            const settingName = row.querySelector('.setting-label label')?.textContent.toLowerCase() || '';
            const settingDesc = row.querySelector('.setting-description')?.textContent.toLowerCase() || '';
            const keyText = settingKey?.toLowerCase() || '';

            row.style.display =
                settingName.includes(term) || settingDesc.includes(term) || keyText.includes(term) ? '' : 'none';
        });

        document.querySelectorAll('.setting-group').forEach((group) => {
            const hasVisibleRow = Array.from(group.querySelectorAll('.setting-row')).some(
                (row) => row.style.display !== 'none',
            );

            group.style.display = hasVisibleRow ? '' : 'none';
        });
    }

    #searchSidebar(term) {
        const scopeElement = document.querySelector('.js-scope-combined') || document.querySelector('.js-scope-type');
        const scope = scopeElement?.value?.split(':')[0] || 'global';

        fetch('/configuration/manage/search?term=' + encodeURIComponent(term) + '&scope=' + encodeURIComponent(scope))
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Search request failed');
                }

                return response.json();
            })
            .then((data) => {
                this.#applySidebarFilter(data.matches || {});
            })
            .catch(() => {
                this.#resetSidebarFilter();
            });
    }

    #applySidebarFilter(matches) {
        const matchedFeatures = Object.keys(matches);

        document.querySelectorAll('.nav-feature').forEach((featureElement) => {
            const featureHeader = featureElement.querySelector('.feature-header');
            const featureKey = featureHeader?.dataset.feature;

            if (!featureKey || !matchedFeatures.includes(featureKey)) {
                featureElement.style.display = 'none';

                return;
            }

            featureElement.style.display = '';
            featureElement.classList.remove('collapsed');

            const matchedTabs = matches[featureKey] || [];

            featureElement.querySelectorAll('.js-nav-tab').forEach((tabElement) => {
                tabElement.style.display = matchedTabs.includes(tabElement.dataset.tab) ? '' : 'none';
            });
        });
    }

    #resetSidebarFilter() {
        document.querySelectorAll('.nav-feature').forEach((featureElement) => {
            featureElement.style.display = '';
        });

        document.querySelectorAll('.js-nav-tab').forEach((tabElement) => {
            tabElement.style.display = '';
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.configuration-management');

    if (!root) {
        return;
    }

    new ConfigurationManagement(root);
});
