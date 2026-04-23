<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Configuration\Communication\Controller;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueDeletionTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Shared\Configuration\ConfigurationConstants;
use Spryker\Shared\Configuration\ConfigurationSchemaConstants;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface getFacade()
 * @method \Spryker\Zed\Configuration\Communication\ConfigurationCommunicationFactory getFactory()
 */
class ManageController extends AbstractController
{
    protected const string REQUEST_PARAM_SCOPE = 'scope';

    protected const string REQUEST_PARAM_SCOPE_IDENTIFIER = 'scope_identifier';

    protected const string REQUEST_PARAM_FEATURE = 'feature';

    protected const string REQUEST_PARAM_TAB = 'tab';

    protected const string REQUEST_PARAM_TERM = 'term';

    protected const int SEARCH_TERM_MAX_LENGTH = 255;

    protected const string REQUEST_BODY_CHANGES = 'changes';

    protected const string REQUEST_BODY_KEY = 'key';

    protected const string REQUEST_BODY_VALUE = 'value';

    protected const string REQUEST_BODY_USE_DEFAULT = 'use_default';

    protected const string RESPONSE_KEY_SUCCESS = 'success';

    protected const string RESPONSE_KEY_COUNT = 'count';

    protected const string RESPONSE_KEY_ERRORS = 'errors';

    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string RESPONSE_KEY_SETTINGS = 'settings';

    protected const string RESPONSE_KEY_MATCHES = 'matches';

    protected const string FALLBACK_SETTING_KEY = 'unknown';

    protected const string ACL_BUNDLE_NAME = 'configuration';

    protected const string ACL_CONTROLLER_NAME = 'manage';

    protected const string ACL_ACTION_SAVE = 'save';

    /**
     * @return array<string, mixed>
     */
    public function indexAction(Request $request): array
    {
        /** @var string $scope */
        $scope = $request->query->get(static::REQUEST_PARAM_SCOPE, ConfigurationConstants::SCOPE_GLOBAL);
        /** @var string $scopeIdentifier */
        $scopeIdentifier = $request->query->get(static::REQUEST_PARAM_SCOPE_IDENTIFIER);

        if ($scopeIdentifier === '') {
            $scopeIdentifier = null;
        }

        $selectedFeature = $request->query->get(static::REQUEST_PARAM_FEATURE);
        $selectedTab = $request->query->get(static::REQUEST_PARAM_TAB);

        $navigationTree = $this->getFactory()
            ->createConfigurationNavigationBuilder()
            ->buildNavigationTree($scope);

        if ((!$selectedTab || !$selectedFeature) && $navigationTree) {
            $firstFeature = reset($navigationTree);
            $firstTab = reset($firstFeature[ConfigurationSchemaConstants::SCHEMA_KEY_TABS]);
            $selectedFeature = $selectedFeature ?: ($firstFeature[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null);
            $selectedTab = $selectedTab ?: ($firstTab[ConfigurationSchemaConstants::SCHEMA_KEY_KEY] ?? null);
        }

        $availableScopes = $this->getFactory()->getConfig()->getAvailableScopes();

        $scopeIdentifiers = $this->getFacade()->getScopeIdentifiers($scope);

        if ($scopeIdentifier === null && $scopeIdentifiers) {
            $scopeIdentifier = reset($scopeIdentifiers);
        }

        // Build a full map of identifiers for every non-global scope so the template
        // can populate the combined selector even when global scope is currently active.
        $allScopeIdentifiers = [];

        foreach ($availableScopes as $availableScope) {
            if ($availableScope === ConfigurationConstants::SCOPE_GLOBAL) {
                continue;
            }

            $allScopeIdentifiers[$availableScope] = $this->getFacade()->getScopeIdentifiers($availableScope);
        }

        $tabSettings = [];
        if ($selectedFeature && $selectedTab) {
            $tabSettings = $this->getFactory()
                ->createConfigurationSettingsLoader()
                ->loadSettingsForTab($selectedFeature, $selectedTab, $scope, $scopeIdentifier);
        }

        return $this->viewResponse([
            'navigationTree' => $navigationTree,
            'selectedFeature' => $selectedFeature,
            'selectedTab' => $selectedTab,
            'tabSettings' => $tabSettings,
            'currentScope' => $scope,
            'currentScopeIdentifier' => $scopeIdentifier,
            'scopeIdentifiers' => $scopeIdentifiers,
            'allScopeIdentifiers' => $allScopeIdentifiers,
            'availableScopes' => $availableScopes,
            'isEditable' => $this->isSaveActionAccessible(),
            'fileUploadForms' => $this->buildFileUploadForms($tabSettings),
        ]);
    }

    public function saveAction(Request $request): JsonResponse
    {
        $data = $this->getFactory()->getUtilEncodingService()->decodeJson($request->getContent(), true);
        $changedValues = is_array($data) ? ($data[static::REQUEST_BODY_CHANGES] ?? []) : [];

        $collectionRequest = $this->buildCollectionRequest($changedValues);
        $responseTransfer = $this->getFacade()->saveConfigurationValues($collectionRequest);

        if ($responseTransfer->getIsSuccess()) {
            $this->addSuccessMessage(
                'Configuration saved successfully (%count% settings updated)',
                ['%count%' => $responseTransfer->getSavedCount()],
            );
        }

        $errors = [];

        foreach ($responseTransfer->getErrors() as $error) {
            $settingKey = $error->getSettingKey() ?? static::FALLBACK_SETTING_KEY;
            $errors[$settingKey] = $error->getMessage();
        }

        return $this->jsonResponse([
            static::RESPONSE_KEY_SUCCESS => $responseTransfer->getIsSuccess(),
            static::RESPONSE_KEY_COUNT => $responseTransfer->getSavedCount(),
            static::RESPONSE_KEY_ERRORS => $errors,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $changedValues
     */
    protected function buildCollectionRequest(array $changedValues): ConfigurationValueCollectionRequestTransfer
    {
        $collectionRequest = new ConfigurationValueCollectionRequestTransfer();

        foreach ($changedValues as $change) {
            $settingKey = $change[static::REQUEST_BODY_KEY] ?? null;

            if (!$settingKey) {
                continue;
            }

            $scope = $change[static::REQUEST_PARAM_SCOPE] ?? ConfigurationConstants::SCOPE_GLOBAL;
            $scopeIdentifier = $change[static::REQUEST_PARAM_SCOPE_IDENTIFIER] ?? null;

            if ($scopeIdentifier === '') {
                $scopeIdentifier = null;
            }

            if (isset($change[static::REQUEST_BODY_USE_DEFAULT]) && $change[static::REQUEST_BODY_USE_DEFAULT] === true) {
                $collectionRequest->addDeletionKey(
                    (new ConfigurationValueDeletionTransfer())
                        ->setSettingKey($settingKey)
                        ->setScope($scope)
                        ->setScopeIdentifier($scopeIdentifier),
                );

                continue;
            }

            $collectionRequest->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey($settingKey)
                    ->setScope($scope)
                    ->setScopeIdentifier($scopeIdentifier)
                    ->setValue($change[static::REQUEST_BODY_VALUE]),
            );
        }

        return $collectionRequest;
    }

    public function searchAction(Request $request): JsonResponse
    {
        /** @var string $term */
        $term = $request->query->get(static::REQUEST_PARAM_TERM, '');
        /** @var string $scope */
        $scope = $request->query->get(static::REQUEST_PARAM_SCOPE, ConfigurationConstants::SCOPE_GLOBAL);

        if (mb_strlen($term) > static::SEARCH_TERM_MAX_LENGTH) {
            $term = mb_substr($term, 0, static::SEARCH_TERM_MAX_LENGTH);
        }

        $matches = $this->getFacade()->searchConfigurationSchema($term, $scope);

        return $this->jsonResponse([
            static::RESPONSE_KEY_MATCHES => $matches,
        ]);
    }

    public function loadTabAction(Request $request): JsonResponse
    {
        /** @var string $featureKey */
        $featureKey = $request->query->get(static::REQUEST_PARAM_FEATURE);
        /** @var string $tabKey */
        $tabKey = $request->query->get(static::REQUEST_PARAM_TAB);
        /** @var string $scope */
        $scope = $request->query->get(static::REQUEST_PARAM_SCOPE, ConfigurationConstants::SCOPE_GLOBAL);
        /** @var string $scopeIdentifier */
        $scopeIdentifier = $request->query->get(static::REQUEST_PARAM_SCOPE_IDENTIFIER);

        if ($scopeIdentifier === '') {
            $scopeIdentifier = null;
        }

        if (!$featureKey || !$tabKey) {
            return $this->jsonResponse([
                static::RESPONSE_KEY_SUCCESS => false,
                static::RESPONSE_KEY_ERROR => 'Feature key and tab key are required',
            ]);
        }

        $tabSettings = $this->getFactory()
            ->createConfigurationSettingsLoader()
            ->loadSettingsForTab($featureKey, $tabKey, $scope, $scopeIdentifier);

        return $this->jsonResponse([
            static::RESPONSE_KEY_SUCCESS => true,
            static::RESPONSE_KEY_SETTINGS => $tabSettings,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $tabSettings
     *
     * @return array<string, \Symfony\Component\Form\FormView>
     */
    protected function buildFileUploadForms(array $tabSettings): array
    {
        $forms = [];

        foreach ($tabSettings as $group) {
            foreach ($group[ConfigurationSchemaConstants::SCHEMA_KEY_SETTINGS] as $setting) {
                if ($setting[ConfigurationSchemaConstants::SCHEMA_KEY_TYPE] !== ConfigurationSchemaConstants::VALUE_TYPE_FILE) {
                    continue;
                }

                $forms[$setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY]] = $this->getFactory()
                    ->createFileUploadForm(
                        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_FILE_UPLOAD] ?? [],
                        $setting[ConfigurationSchemaConstants::SCHEMA_KEY_KEY],
                    )
                    ->createView();
            }
        }

        return $forms;
    }

    protected function isSaveActionAccessible(): bool
    {
        $aclFacade = $this->getFactory()->getAclFacade();

        if (!$aclFacade->hasCurrentUser()) {
            return false;
        }

        return $aclFacade->checkAccess(
            $aclFacade->getCurrentUser(),
            static::ACL_BUNDLE_NAME,
            static::ACL_CONTROLLER_NAME,
            static::ACL_ACTION_SAVE,
        );
    }
}
