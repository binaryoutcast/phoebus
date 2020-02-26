<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ========================================================================================================

// Serve the Adminsitration landing page
if ($gaRuntime['requestPath'] == URI_ADMIN && !$gaRuntime['requestPanelTask']) {
  $moduleGenerateContent->addonSite('admin-frontpage.xhtml', 'Administration');
}

switch ($gaRuntime['requestPanelTask']) {
  case 'list':
    if (!$gaRuntime['requestPanelWhat']) {
      gfError('You did not specify what you want to list');
    }

    switch ($gaRuntime['requestPanelWhat']) {
      case 'langpacks':
        if ($gaRuntime['authentication']['level'] < 4) {
          gfError('You are not allowed to list language packs!');
        }
      case 'extensions':
      case 'externals':
      case 'themes':
        $addons = $moduleReadManifest->getAddons('panel-addons-by-type',
                                                 substr($gaRuntime['requestPanelWhat'], 0, -1));

        $moduleGenerateContent->addonSite('admin-list-' . $gaRuntime['requestPanelWhat'],
                                          ucfirst($gaRuntime['requestPanelWhat']) . ' - Administration',
                                          $addons);
        break;
      case 'users':
        $users = $moduleAccount->getUsers();
        $moduleGenerateContent->addonSite('admin-list-users', 'Users - Administration', $users);
      case 'user-addons':
        if (!$gaRuntime['requestPanelSlug']) {
          gfError('You did not specify a slug (username)');
        }

        $userManifest = $moduleAccount->getSingleUser($gaRuntime['requestPanelSlug'], true);

        // Check if manifest is valid
        if (!$userManifest) {
          gfError('User Manifest is null');
        }

        $addons = $moduleReadManifest->getAddons('panel-user-addons', $userManifest['addons']) ?? [];
        $moduleGenerateContent->addonSite('admin-user-addons-list',
                                          ($userManifest['displayName'] ?? $userManifest['username']) . '\'s Add-ons',
                                          $addons);
      break;
      default:
        gfError('Invalid list request');
    }
    break;
  case 'submit':
    if (!$gaRuntime['requestPanelWhat']) {
      gfError('You did not specify what you want to submit');
    }

    switch ($gaRuntime['requestPanelWhat']) {
      case 'addon':
      case 'langpack':
        $isLangPack = (bool)($gaRuntime['requestPanelWhat'] == 'langpack');
        $strTitle = $isLangPack ? 'Pale Moon Language Pack' : 'Add-on';
        if ($boolHasPostData) {
          $finalSlug = $moduleWriteManifest->submitNewAddon($isLangPack);

          // If an error happened stop.
          if (!$finalSlug) {
            gfError('Something has gone horribly wrong');
          }

          // Add-on Submitted go to edit metadata
          funcRedirect(URI_ADMIN . '?task=update&what=metadata' . '&slug=' . $finalSlug);
        }

        // Generate the submit page
        $moduleGenerateContent->addonSite('panel-submit-' . $gaRuntime['requestPanelWhat'],
                                          'Submit new ' . $strTitle);
        break;
      case 'external':
        if ($boolHasPostData) {
          $finalSlug = $moduleWriteManifest->submitNewExternal();

          // If an error happened stop.
          if (!$finalSlug) {
            gfError('Something has gone horribly wrong');
          }

          // External Submitted go to edit metadata
          funcRedirect(URI_ADMIN . '?task=update&what=metadata' . '&slug=' . $finalSlug);
        }

        // Generate the submit page
        $moduleGenerateContent->addonSite('panel-submit-external', 'Submit new External');
        break;
      default:
        gfError('Invalid submit request');
    }
    break;
  case 'update':
    if (!$gaRuntime['requestPanelWhat'] || !$gaRuntime['requestPanelSlug']) {
      gfError('You did not specify what you want to update');
    }

    switch ($gaRuntime['requestPanelWhat']) {
      case 'release':
        // Check for valid slug
        if (!$gaRuntime['requestPanelSlug']) {
          gfError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $gaRuntime['requestPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          gfError('Add-on Manifest is null');
        }

        $isLangPack = (bool)($addonManifest['type'] == 'langpack');

        if ($isLangPack && $gaRuntime['authentication']['level'] < 4) {
          gfError('You are not allowed to update language packs!');
        }

        if ($addonManifest['type'] == 'external') {
          gfError('Externals do not physically exist here.. Are you a moron?');
        }

        if ($boolHasPostData) {
          $finalType = $moduleWriteManifest->updateAddonRelease($addonManifest, $isLangPack);

          // If an error happened stop.
          if (!$finalType) {
            gfError('Something has gone horribly wrong');
          }

          // Add-on Submitted go to edit metadata
          funcRedirect(URI_ADMIN . '?task=list&what=' . $finalType);
        }

        $moduleGenerateContent->addonSite('panel-update-release', 'Release new version', $addonManifest['slug']);
        break;
      case 'metadata':
        // Check for valid slug
        if (!$gaRuntime['requestPanelSlug']) {
          gfError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $gaRuntime['requestPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          gfError('Add-on Manifest is null');
        }

        if ($addonManifest['type'] == 'langpack' && $gaRuntime['authentication']['level'] < 4) {
          gfError('You are not allowed to edit language packs!');
        }

        // We have post data so we should update the manifest data via classWriteManifest
        if ($boolHasPostData) {
          // Extenrals need special handling
          if ($addonManifest['type'] == 'external') {
            $boolUpdate = $moduleWriteManifest->updateExternalMetadata($addonManifest);
          }
          else {
            $boolUpdate = $moduleWriteManifest->updateAddonMetadata($addonManifest);
          }

          // If an error happened stop.
          if (!$boolUpdate) {
            gfError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          funcRedirect(URI_ADMIN . '?task=list&what=' . $addonManifest['type'] . 's');
        }

        // Create an array to hold extra data to send to smarty
        // Such as the list of licenses
        $arrayExtraData = array('licenses' => array_keys($moduleReadManifest::LICENSES));

        // Extensions need the associative array of extension categories as well
        if ($addonManifest['type'] != 'theme') {
          $arrayExtraData['categories'] = $moduleReadManifest::EXTENSION_CATEGORY_SLUGS;
        }

        $strMetadataType = 'addon';

        // Extenrals need special handling
        if ($addonManifest['type'] == 'external') {
          $strMetadataType = 'external';
        }

        // Generate the edit add-on metadata page
        $moduleGenerateContent->addonSite('admin-edit-' . $strMetadataType . '-metadata',
                                           'Editing Metadata for ' . ($addonManifest['name'] ?? $addonManifest['slug']),
                                           $addonManifest,
                                           $arrayExtraData);
        break;
      case 'user':
        // Check for valid slug
        if (!$gaRuntime['requestPanelSlug']) {
          gfError('You did not specify a slug (username)');
        }

        $userManifest = $moduleAccount->getSingleUser($gaRuntime['requestPanelSlug'], true);

        // Check if manifest is valid
        if (!$userManifest) {
          gfError('User Manifest is null');
        }

        // Do not allow editing of users at or above a user level unless they are you or you are level 5
        if ($gaRuntime['authentication']['level'] != 5 &&
            $userManifest['level'] >= $gaRuntime['authentication']['level'] &&
            $userManifest['username'] != $gaRuntime['authentication']['username']) {
          gfError('You attempted to alter a user account that is the same or higher rank as you but not you. You\'re in trouble!');
        }

        // Deal with writing the updated user manifest
        if ($boolHasPostData) {
          $boolUpdate = $moduleAccount->updateUserManifest($userManifest);

          // If an error happened stop.
          if (!$boolUpdate) {
            gfError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          funcRedirect(URI_ADMIN . '?task=list&what=users');
        }

        $moduleGenerateContent->addonSite('admin-edit-account-metadata',
                                          'Editing Account ' . ($userManifest['displayName'] ?? $userManifest['username']),
                                          $userManifest);
        break;
      default:
        gfError('Invalid update request');
    }
    break;
  case 'bulk-upload':
    if (!$gaRuntime['requestPanelWhat']) {
      gfError('You did not specify what you want to bulk upload');
    }
    switch ($gaRuntime['requestPanelWhat']) {
      case 'langpacks':
        if ($boolHasPostData) {
          $finalResult = $moduleWriteManifest->bulkUploader('langpack');

          if (!$finalResult) {
            gfError('Something has gone horribly wrong');
          }

          if (!empty($finalResult['errors'])) {
            $moduleGenerateContent->addonSite('addon-bulk-upload-result', 'Bulk Upload Report', $finalResult);
          }

          funcRedirect(URI_ADMIN . '?task=list&what=langpacks');
        }

        $moduleGenerateContent->addonSite('addon-bulk-upload-langpack', 'Bulk Upload Language Packs');
        break;
      default:
        gfError('Invalid bulk upload request');
    }
    break;
  default:
    gfError('Invalid task');
}

// ====================================================================================================================

?>