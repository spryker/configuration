/**
 * Copyright (c) 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

'use strict';

(function () {
    const root = document.querySelector('.configuration-management');

    if (!root) {
        return;
    }

    const dataset = root.dataset;

    const configManager = {
        currentScope: dataset.scope,
        currentScopeIdentifier: dataset.scopeIdentifier || null,
        readOnly: dataset.readOnly === '1',
        changes: new Map(),
        originalValues: new Map(),
        dependencyMap: new Map(),
        reverseDependencyMap: new Map(),

        i18n: {
            resetConfirm: dataset.i18nResetConfirm,
            unsavedScopeConfirm: dataset.i18nUnsavedScopeConfirm,
            unsavedTabConfirm: dataset.i18nUnsavedTabConfirm,
            dependentResetConfirmTemplate: dataset.i18nDependentResetConfirmTemplate,
            saveErrorPrefix: dataset.i18nSaveErrorPrefix,
            saveErrorGeneric: dataset.i18nSaveErrorGeneric,
            savingLabel: dataset.i18nSavingLabel,
            saveLabel: dataset.i18nSaveLabel,
        },

        init: function () {
            this.buildDependencyMaps();
            this.evaluateAllDependencies();

            if (this.readOnly) {
                this.bindReadOnlyEvents();

                return;
            }

            this.storeOriginalValues();
            this.bindEvents();
        },

        storeOriginalValues: function () {
            document.querySelectorAll('.config-input').forEach((input) => {
                const settingKey = this.getSettingKey(input);
                const hasCustomValue = input.dataset.hasCustomValue === '1';

                if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                    this.originalValues.set(settingKey, {
                        value: input.dataset.originalValue || '',
                        displayValue: input.checked ? 'true' : 'false',
                        hasCustomValue: hasCustomValue,
                    });

                    return;
                }

                if (input.type === 'radio') {
                    if (input.checked) {
                        this.originalValues.set(settingKey, {
                            value: input.dataset.originalValue || '',
                            displayValue: input.value,
                            hasCustomValue: hasCustomValue,
                        });
                    }

                    return;
                }

                if (input.type === 'select-multiple') {
                    const selectedOptions = Array.from(input.selectedOptions).map((opt) => opt.value);

                    this.originalValues.set(settingKey, {
                        value: input.dataset.originalValue || '',
                        displayValue: JSON.stringify(selectedOptions),
                        hasCustomValue: hasCustomValue,
                    });

                    return;
                }

                this.originalValues.set(settingKey, {
                    value: input.dataset.originalValue || '',
                    displayValue: input.value,
                    hasCustomValue: hasCustomValue,
                });
            });
        },

        buildDependencyMaps: function () {
            document.querySelectorAll('.setting-row').forEach((row) => {
                const settingKey = row.dataset.settingKey;
                const dependenciesJson = row.dataset.dependencies;

                if (!dependenciesJson || dependenciesJson === '[]') {
                    return;
                }

                let dependencies;

                try {
                    dependencies = JSON.parse(dependenciesJson);
                } catch (e) {
                    return;
                }

                this.dependencyMap.set(settingKey, dependencies);

                dependencies.forEach((dep) => {
                    if (!dep.when) {
                        return;
                    }

                    this.extractDependentSettings(dep.when).forEach((requiredKey) => {
                        if (!this.reverseDependencyMap.has(requiredKey)) {
                            this.reverseDependencyMap.set(requiredKey, []);
                        }

                        this.reverseDependencyMap.get(requiredKey).push(settingKey);
                    });
                });
            });
        },

        extractDependentSettings: function (whenClause) {
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
        },

        evaluateAllDependencies: function () {
            document.querySelectorAll('.setting-row').forEach((row) => {
                this.evaluateDependency(row.dataset.settingKey);
            });
        },

        evaluateDependency: function (settingKey) {
            const row = document.querySelector(`.setting-row[data-setting-key="${settingKey}"]`);

            if (!row) {
                return;
            }

            const dependencies = this.dependencyMap.get(settingKey);

            if (!dependencies || dependencies.length === 0) {
                row.style.display = '';

                return;
            }

            const shouldShow = dependencies.some((dep) => dep.when && this.evaluateWhenClause(dep.when));

            row.style.display = shouldShow ? '' : 'none';
        },

        evaluateWhenClause: function (whenClause) {
            if (whenClause.any) {
                return whenClause.any.some((condition) => this.evaluateCondition(condition));
            }

            if (whenClause.all) {
                return whenClause.all.every((condition) => this.evaluateCondition(condition));
            }

            return true;
        },

        evaluateCondition: function (condition) {
            if (!condition.setting || !condition.operator) {
                return true;
            }

            const currentValue = this.getCurrentValue(condition.setting);
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
        },

        getCurrentValue: function (settingKey) {
            const row = document.querySelector(`.setting-row[data-setting-key="${settingKey}"]`);

            if (!row) {
                return null;
            }

            const input = row.querySelector('.config-input');

            if (!input) {
                return null;
            }

            if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                return input.checked;
            }

            if (input.type === 'radio') {
                const checked = row.querySelector('input[type="radio"]:checked');

                return checked ? checked.value : null;
            }

            if (input.type === 'select-multiple') {
                return Array.from(input.selectedOptions).map((opt) => opt.value);
            }

            return input.value;
        },

        checkDependentsBeforeChange: function (settingKey, callback) {
            const dependents = this.reverseDependencyMap.get(settingKey);

            if (!dependents || dependents.length === 0) {
                callback();

                return;
            }

            const dependentsWithChanges = dependents.filter((depKey) => {
                const original = this.originalValues.get(depKey);

                return this.changes.has(depKey) || (original && original.hasCustomValue);
            });

            if (dependentsWithChanges.length === 0) {
                callback();

                return;
            }

            const dependentNames = dependentsWithChanges.map((depKey) => {
                const row = document.querySelector(`.setting-row[data-setting-key="${depKey}"]`);
                const label = row?.querySelector('.setting-label label');

                return label ? label.textContent.trim() : depKey;
            });

            // Template: "Changing this setting will reset the following dependent settings:\n\n%settings%\n\nDo you want to continue?"
            const message = this.i18n.dependentResetConfirmTemplate.replace('%settings%', dependentNames.join('\n'));

            if (!confirm(message)) {
                return;
            }

            dependentsWithChanges.forEach((depKey) => {
                this.handleUseDefault(depKey);

                const original = this.originalValues.get(depKey);

                if (original) {
                    original.hasCustomValue = false;
                    this.originalValues.set(depKey, original);
                }
            });

            callback();
        },

        bindReadOnlyEvents: function () {
            document.querySelectorAll('.feature-header').forEach((header) => {
                header.addEventListener('click', () => {
                    header.closest('.nav-feature').classList.toggle('collapsed');
                });
            });

            document.querySelectorAll('.nav-tab').forEach((tab) => {
                tab.addEventListener('click', () => this.handleTabChange(tab.dataset.tab));
            });

            document.getElementById('scope-combined')?.addEventListener('change', () => this.handleScopeChange());
            document.getElementById('scope-type')?.addEventListener('change', () => this.handleScopeChange());
            document.getElementById('scope-identifier')?.addEventListener('change', () => this.handleScopeChange());

            document
                .getElementById('config-search')
                ?.addEventListener('input', (e) => this.handleSearch(e.target.value));
        },

        bindEvents: function () {
            document.querySelectorAll('.config-input').forEach((input) => {
                if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                    input.addEventListener('change', () => this.handleInputChange(input));

                    return;
                }

                if (input.type === 'radio') {
                    input.addEventListener('change', () => this.handleRadioChange(input));

                    return;
                }

                if (input.type === 'select-multiple') {
                    input.addEventListener('change', () => this.handleInputChange(input));

                    return;
                }

                input.addEventListener('input', () => this.handleInputChange(input));
            });

            document.querySelectorAll('.use-default-link').forEach((link) => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleUseDefault(link.dataset.settingKey);
                });
            });

            document.querySelectorAll('.feature-header').forEach((header) => {
                header.addEventListener('click', () => {
                    header.closest('.nav-feature').classList.toggle('collapsed');
                });
            });

            document.querySelectorAll('.nav-tab').forEach((tab) => {
                tab.addEventListener('click', () => this.handleTabChange(tab.dataset.tab));
            });

            document.getElementById('scope-combined')?.addEventListener('change', () => this.handleScopeChange());
            document.getElementById('scope-type')?.addEventListener('change', () => this.handleScopeChange());
            document.getElementById('scope-identifier')?.addEventListener('change', () => this.handleScopeChange());

            document
                .getElementById('config-search')
                ?.addEventListener('input', (e) => this.handleSearch(e.target.value));

            document.getElementById('save-configuration')?.addEventListener('click', () => this.saveConfiguration());
            document.getElementById('reset-changes')?.addEventListener('click', () => this.resetChanges());
        },

        getSettingKey: function (input) {
            return input.closest('.setting-row')?.dataset.settingKey;
        },

        handleInputChange: function (input) {
            const settingKey = this.getSettingKey(input);
            const settingRow = input.closest('.setting-row');

            this.clearRowError(settingRow);
            const defaultValue = settingRow?.dataset.defaultValue ?? null;
            const originalData = this.originalValues.get(settingKey);

            let currentValue;

            if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                currentValue = input.checked ? 'true' : 'false';
            } else if (input.type === 'select-multiple') {
                currentValue = JSON.stringify(Array.from(input.selectedOptions).map((opt) => opt.value));
            } else {
                currentValue = input.value;
            }

            if (currentValue === originalData.displayValue) {
                this.changes.delete(settingKey);
                settingRow?.classList.remove('changed');

                const overridesDefault =
                    originalData.hasCustomValue && defaultValue !== null && originalData.displayValue !== defaultValue;

                this.updateUseDefaultUI(settingRow, overridesDefault);
                this.evaluateDependenciesForSetting(settingKey);
                this.updateSaveBar();

                return;
            }

            const previousValue = originalData.displayValue;

            this.checkDependentsBeforeChange(settingKey, () => {
                this.changes.set(settingKey, { value: currentValue, useDefault: false });
                settingRow?.classList.add('changed');
                this.updateUseDefaultUI(settingRow, defaultValue !== null && currentValue !== defaultValue);
                this.evaluateDependenciesForSetting(settingKey);
                this.updateSaveBar();
            });

            // Revert input if user cancelled the confirmation dialog
            if (!this.changes.has(settingKey)) {
                this.revertInputValue(input, previousValue);
            }
        },

        revertInputValue: function (input, previousValue) {
            if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                input.checked = previousValue === 'true';

                return;
            }

            if (input.type === 'select-multiple') {
                const previousValues = JSON.parse(previousValue || '[]');
                Array.from(input.options).forEach((option) => {
                    option.selected = previousValues.includes(option.value);
                });

                return;
            }

            input.value = previousValue;
        },

        evaluateDependenciesForSetting: function (changedSettingKey) {
            const dependents = this.reverseDependencyMap.get(changedSettingKey);

            if (!dependents || dependents.length === 0) {
                return;
            }

            dependents.forEach((depKey) => this.evaluateDependency(depKey));
        },

        updateUseDefaultUI: function (settingRow, showLink) {
            if (!settingRow) {
                return;
            }

            const useDefaultLink = settingRow.querySelector('.use-default-link');
            const overridesBadge = settingRow.querySelector('.overrides-default-badge');
            const displayValue = showLink ? 'inline-flex' : 'none';

            if (useDefaultLink) {
                useDefaultLink.style.display = displayValue;
            }

            if (overridesBadge) {
                overridesBadge.style.display = showLink ? 'inline' : 'none';
            }
        },

        handleRadioChange: function (input) {
            if (!input.checked) {
                return;
            }

            const radioGroup = input.closest('.radio-group');

            if (radioGroup) {
                radioGroup.querySelectorAll('.radio-option').forEach((option) => option.classList.remove('checked'));
                input.closest('.radio-option')?.classList.add('checked');
            }

            this.handleInputChange(input);
        },

        handleUseDefault: function (settingKey) {
            const settingRow = document.querySelector(`[data-setting-key="${settingKey}"]`);

            if (!settingRow) {
                return;
            }

            const inheritedValue = settingRow.dataset.inheritedValue || settingRow.dataset.defaultValue || '';
            const input = settingRow.querySelector('.config-input');

            if (input) {
                if (input.type === 'checkbox' && input.classList.contains('boolean-toggle')) {
                    input.checked = inheritedValue === 'true';
                } else if (input.type === 'radio') {
                    settingRow.querySelectorAll('input[type="radio"]').forEach((radio) => {
                        radio.checked = radio.value === inheritedValue;
                        const parentLabel = radio.closest('.radio-option');

                        if (parentLabel) {
                            parentLabel.classList.toggle('checked', radio.checked);
                        }
                    });
                } else if (input.type === 'select-multiple') {
                    try {
                        const selectedValues = JSON.parse(inheritedValue || '[]');
                        Array.from(input.options).forEach((option) => {
                            option.selected = selectedValues.includes(option.value);
                        });
                    } catch (e) {
                        // Malformed stored value — leave selection unchanged
                    }
                } else {
                    input.value = inheritedValue;
                }
            }

            const originalData = this.originalValues.get(settingKey);

            if (originalData && originalData.hasCustomValue) {
                // Queue a delete in the regular save flow
                this.changes.set(settingKey, { value: null, useDefault: true });
                settingRow.classList.add('changed');

                originalData.displayValue = inheritedValue;
                originalData.value = '';
                this.originalValues.set(settingKey, originalData);
            } else {
                // Simply revert the pending unsaved edit
                this.changes.delete(settingKey);
                settingRow.classList.remove('changed');
            }

            this.updateUseDefaultUI(settingRow, false);
            this.evaluateDependenciesForSetting(settingKey);
            this.updateSaveBar();
        },

        updateSaveBar: function () {
            const changeCount = this.changes.size;
            const saveBar = document.getElementById('config-save-bar');
            const countSpan = document.getElementById('changes-count');

            saveBar.style.display = changeCount > 0 ? 'block' : 'none';
            countSpan.textContent = changeCount;
        },

        saveConfiguration: function () {
            if (this.changes.size === 0) {
                return;
            }

            const changesArray = Array.from(this.changes.entries()).map(([key, change]) => ({
                key: key,
                value: change.value,
                use_default: change.useDefault,
                scope: this.currentScope,
                scope_identifier: this.currentScopeIdentifier,
            }));

            const saveButton = document.getElementById('save-configuration');
            saveButton.disabled = true;
            saveButton.innerHTML = this.i18n.savingLabel;

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
                    this.clearValidationErrors();

                    if (data.success) {
                        this.changes.clear();
                        document
                            .querySelectorAll('.setting-row.changed')
                            .forEach((row) => row.classList.remove('changed'));
                        this.updateSaveBar();
                        location.reload();

                        return;
                    }

                    if (data.errors && Object.keys(data.errors).length > 0) {
                        this.showValidationErrors(data.errors);

                        return;
                    }

                    alert(this.i18n.saveErrorPrefix + (data.message || ''));
                })
                .catch(() => alert(this.i18n.saveErrorGeneric))
                .finally(() => {
                    saveButton.disabled = false;
                    saveButton.innerHTML = this.i18n.saveLabel;
                });
        },

        clearValidationErrors: function () {
            document.querySelectorAll('.setting-row.has-error').forEach((row) => row.classList.remove('has-error'));
            document.querySelectorAll('.setting-error-message').forEach((el) => el.remove());
        },

        clearRowError: function (row) {
            if (!row) {
                return;
            }

            row.classList.remove('has-error');
            row.querySelectorAll('.setting-error-message').forEach((el) => el.remove());
        },

        showValidationErrors: function (errors) {
            let firstErrorRow = null;

            Object.entries(errors).forEach(([settingKey, message]) => {
                const row = document.querySelector(`.setting-row[data-setting-key="${settingKey}"]`);

                if (!row) {
                    return;
                }

                row.classList.add('has-error');

                const errorEl = document.createElement('div');
                errorEl.className = 'setting-error-message';
                errorEl.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + this.escapeHtml(message);

                const inputContainer = row.querySelector('.setting-input');

                if (inputContainer) {
                    inputContainer.appendChild(errorEl);
                }

                if (!firstErrorRow) {
                    firstErrorRow = row;
                }
            });

            if (firstErrorRow) {
                firstErrorRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },

        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text;

            return div.innerHTML;
        },

        resetChanges: function () {
            if (!confirm(this.i18n.resetConfirm)) {
                return;
            }

            this.clearValidationErrors();
            this.changes.clear();

            document.querySelectorAll('.config-input').forEach((input) => {
                const settingKey = this.getSettingKey(input);
                const originalData = this.originalValues.get(settingKey);

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
                }

                const settingRow = input.closest('.setting-row');

                if (settingRow) {
                    this.updateUseDefaultUI(settingRow, originalData.hasCustomValue);
                }
            });

            document.querySelectorAll('.setting-row.changed').forEach((row) => row.classList.remove('changed'));

            this.updateSaveBar();
        },

        handleScopeChange: function () {
            if (this.changes.size > 0 && !confirm(this.i18n.unsavedScopeConfirm)) {
                return;
            }

            let scope, scopeIdentifier;
            const combinedEl = document.getElementById('scope-combined');

            if (combinedEl) {
                const [scopePart, ...identifierParts] = combinedEl.value.split(':');
                scope = scopePart;
                scopeIdentifier = identifierParts.join(':');
            } else {
                scope = document.getElementById('scope-type').value;
                scopeIdentifier = document.getElementById('scope-identifier')?.value || '';
            }

            const currentTab = document.querySelector('.nav-tab.active')?.dataset.tab || '';
            const url = new URL(window.location.href);

            url.searchParams.set('scope', scope);

            if (scopeIdentifier) {
                url.searchParams.set('scope_identifier', scopeIdentifier);
            } else {
                url.searchParams.delete('scope_identifier');
            }

            if (currentTab) {
                url.searchParams.set('tab', currentTab);
            }

            window.location.href = url.toString();
        },

        handleTabChange: function (tabKey) {
            if (this.changes.size > 0 && !confirm(this.i18n.unsavedTabConfirm)) {
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabKey);
            window.location.href = url.toString();
        },

        handleSearch: function (searchTerm) {
            const term = searchTerm.toLowerCase().trim();

            document.querySelectorAll('.setting-row').forEach((row) => {
                const settingKey = row.dataset.settingKey;

                // Rows hidden by dependency must stay hidden regardless of search
                if (this.isDependencyHidden(settingKey)) {
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
        },

        isDependencyHidden: function (settingKey) {
            const dependencies = this.dependencyMap.get(settingKey);

            if (!dependencies || dependencies.length === 0) {
                return false;
            }

            return !dependencies.some((dep) => dep.when && this.evaluateWhenClause(dep.when));
        },
    };

    document.addEventListener('DOMContentLoaded', () => configManager.init());

    window.configManager = configManager;
})();
