<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ========================================================================================================

// Serve the Adminsitration landing page
if ($arraySoftwareState['requestPath'] == URI_ADMIN && !$arraySoftwareState['requestPanelTask']) {
  $moduleGenerateContent->addonSite('admin-frontpage.xhtml', 'Administration');
}

switch ($arraySoftwareState['requestPanelTask']) {
  case 'list':
    if (!$arraySoftwareState['requestPanelWhat']) {
      funcError('You did not specify what you want to list');
    }

    switch ($arraySoftwareState['requestPanelWhat']) {
      case 'langpacks':
        if ($arraySoftwareState['authentication']['level'] < 4) {
          funcError('You are not allowed to list language packs!');
        }
      case 'extensions':
      case 'externals':
      case 'themes':
        $addons = $moduleReadManifest->getAddons('panel-addons-by-type',
                                                 substr($arraySoftwareState['requestPanelWhat'], 0, -1));

        $moduleGenerateContent->addonSite('admin-list-' . $arraySoftwareState['requestPanelWhat'],
                                          ucfirst($arraySoftwareState['requestPanelWhat']) . ' - Administration',
                                          $addons);
        break;
      case 'unreviewed':
        $addons = $moduleReadManifest->getAddons('panel-unreviewed-addons');
        $moduleGenerateContent->addonSite('admin-list-unreviewed',
                                          'Unreviewed Add-ons - Administration',
                                          $addons);
        break;
      case 'users':
        $users = $moduleAccount->getUsers();
        $moduleGenerateContent->addonSite('admin-list-users', 'Users - Administration', $users);
      case 'user-addons':
        if (!$arraySoftwareState['requestPanelSlug']) {
          funcError('You did not specify a slug (username)');
        }

        $userManifest = $moduleAccount->getSingleUser($arraySoftwareState['requestPanelSlug'], true);

        // Check if manifest is valid
        if (!$userManifest) {
          funcError('User Manifest is null');
        }

        $addons = $moduleReadManifest->getAddons('panel-user-addons', $userManifest['addons']) ?? [];
        $moduleGenerateContent->addonSite('admin-user-addons-list',
                                          ($userManifest['displayName'] ?? $userManifest['username']) . '\'s Add-ons',
                                          $addons);
      break;
      case 'logs':
        $logs = $moduleLog->fetch();
        $moduleGenerateContent->addonSite('admin-list-logs', 'Last 100 Logged Events', $logs);
        break;
      default:
        funcError('Invalid list request');
    }
    break;
  case 'submit':
    if (!$arraySoftwareState['requestPanelWhat']) {
      funcError('You did not specify what you want to submit');
    }

    switch ($arraySoftwareState['requestPanelWhat']) {
      case 'addon':
      case 'langpack':
        $isLangPack = (bool)($arraySoftwareState['requestPanelWhat'] == 'langpack');
        $strTitle = $isLangPack ? 'Pale Moon Language Pack' : 'Add-on';
        if ($boolHasPostData) {
          $finalSlug = $moduleWriteManifest->submitNewAddon($isLangPack);

          // If an error happened stop.
          if (!$finalSlug) {
            funcError('Something has gone horribly wrong');
          }

          // Add-on Submitted go to edit metadata
          $moduleLog->record('[ADMIN] SUCCESS - Submitted Add-on: ' . $finalSlug);
          funcRedirect(URI_ADMIN . '?task=update&what=metadata' . '&slug=' . $finalSlug);
        }

        // Generate the submit page
        $moduleGenerateContent->addonSite('panel-submit-' . $arraySoftwareState['requestPanelWhat'],
                                          'Submit new ' . $strTitle);
        break;
      case 'external':
        if ($boolHasPostData) {
          $finalSlug = $moduleWriteManifest->submitNewExternal();

          // If an error happened stop.
          if (!$finalSlug) {
            funcError('Something has gone horribly wrong');
          }

          // External Submitted go to edit metadata
          $moduleLog->record('[ADMIN] SUCCESS - Submitted External: ' . $finalSlug);
          funcRedirect(URI_ADMIN . '?task=update&what=metadata' . '&slug=' . $finalSlug);
        }

        // Generate the submit page
        $moduleGenerateContent->addonSite('panel-submit-external', 'Submit new External');
        break;
      default:
        funcError('Invalid submit request');
    }
    break;
  case 'update':
    if (!$arraySoftwareState['requestPanelWhat'] || !$arraySoftwareState['requestPanelSlug']) {
      funcError('You did not specify what you want to update');
    }

    switch ($arraySoftwareState['requestPanelWhat']) {
      case 'release':
        // Check for valid slug
        if (!$arraySoftwareState['requestPanelSlug']) {
          funcError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $arraySoftwareState['requestPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          funcError('Add-on Manifest is null');
        }

        $isLangPack = (bool)($addonManifest['type'] == 'langpack');

        if ($isLangPack && $arraySoftwareState['authentication']['level'] < 4) {
          $moduleLog->record('[ADMIN] FAIL - Tried to update a language pack release ' . $addonManifest['slug']);
          funcError('You are not allowed to update language packs!');
        }

        if ($addonManifest['type'] == 'external') {
          $moduleLog->record('[ADMIN] FAIL - Tried to update external release' . $addonManifest['slug']);
          funcError('Externals do not physically exist here.. Are you a moron?');
        }

        if ($boolHasPostData) {
          $finalType = $moduleWriteManifest->updateAddonRelease($addonManifest, $isLangPack);

          // If an error happened stop.
          if (!$finalType) {
            funcError('Something has gone horribly wrong');
          }

          // Add-on Submitted go to edit metadata
          $moduleLog->record('[ADMIN] SUCCESS - Updated Add-on Release: ' . $addonManifest['slug']);
          funcRedirect(URI_ADMIN . '?task=list&what=' . $finalType);
        }

        $moduleGenerateContent->addonSite('panel-update-release', 'Release new version', $addonManifest['slug']);
        break;
      case 'metadata':
        // Check for valid slug
        if (!$arraySoftwareState['requestPanelSlug']) {
          funcError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $arraySoftwareState['requestPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          funcError('Add-on Manifest is null');
        }

        if ($addonManifest['type'] == 'langpack' && $arraySoftwareState['authentication']['level'] < 4) {
          $moduleLog->record('[ADMIN] FAIL - Tried to update language pack metadata' . $addonManifest['slug']);
          funcError('You are not allowed to edit language packs!');
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
            funcError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          $moduleLog->record('[ADMIN] SUCCESS - Updated Add-on/External Metadata: ' . $addonManifest['slug']);
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
      case 'delete':
        // Check for valid slug
        if (!$arraySoftwareState['requestPanelSlug']) {
          funcError('You did not specify a slug');
        }

        if ($arraySoftwareState['authentication']['level'] < 4) {
          $moduleLog->record('[ADMIN] FAIL - Tried to delete add-on ' . $addonManifest['slug']);
          funcError('You are not allowed to delete add-ons!');
        }

        // Get the manifest
        $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $arraySoftwareState['requestPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          funcError('Add-on Manifest is null');
        }

        if ($boolHasPostData) {
          funcSendHeader('501');
        }

        // Generate the delete confirmation page page
        $moduleGenerateContent->addonSite('admin-delete-addon',
                                           'Remove ' . ($addonManifest['name'] ?? $addonManifest['slug']),
                                           $addonManifest);
        break;
      case 'user':
        // Check for valid slug
        if (!$arraySoftwareState['requestPanelSlug']) {
          funcError('You did not specify a slug (username)');
        }

        $userManifest = $moduleAccount->getSingleUser($arraySoftwareState['requestPanelSlug'], true);

        // Check if manifest is valid
        if (!$userManifest) {
          funcError('User Manifest is null');
        }

        // Do not allow editing of users at or above a user level unless they are you or you are level 5
        if ($arraySoftwareState['authentication']['level'] != 5 &&
            $userManifest['level'] >= $arraySoftwareState['authentication']['level'] &&
            $userManifest['username'] != $arraySoftwareState['authentication']['username']) {
          $moduleLog->record('[ADMIN] FAIL - Tried to update User Metadata: ' . $arraySoftwareState['requestPanelSlug']);
          funcError('You attempted to alter a user account that is the same or higher rank as you but not you. You\'re in trouble!');
        }

        // Deal with writing the updated user manifest
        if ($boolHasPostData) {
          $boolUpdate = $moduleAccount->updateUserManifest($userManifest);

          // If an error happened stop.
          if (!$boolUpdate) {
            funcError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          $moduleLog->record('[ADMIN] SUCCESS - Updated User Metadata: ' . $arraySoftwareState['requestPanelSlug']);
          funcRedirect(URI_ADMIN . '?task=list&what=users');
        }

        $moduleGenerateContent->addonSite('admin-edit-account-metadata',
                                          'Editing Account ' . ($userManifest['displayName'] ?? $userManifest['username']),
                                          $userManifest);
        break;
      default:
        funcError('Invalid update request');
    }
    break;
  case 'bulk-upload':
    if (!$arraySoftwareState['requestPanelWhat']) {
      funcError('You did not specify what you want to bulk upload');
    }
    switch ($arraySoftwareState['requestPanelWhat']) {
      case 'langpacks':
        if ($boolHasPostData) {
          $finalResult = $moduleWriteManifest->bulkUploader('langpack');

          if (!$finalResult) {
            funcError('Something has gone horribly wrong');
          }

          if (!empty($finalResult['errors'])) {
            $moduleGenerateContent->addonSite('addon-bulk-upload-result', 'Bulk Upload Report', $finalResult);
          }

          $moduleLog->record('[ADMIN] SUCCESS - Bulk Uploaded Language Packs');
          funcRedirect(URI_ADMIN . '?task=list&what=langpacks');
        }

        $moduleGenerateContent->addonSite('addon-bulk-upload-langpack', 'Bulk Upload Language Packs');
        break;
      default:
        funcError('Invalid bulk upload request');
    }
    break;
  default:
    funcError('Invalid task');
}

// ====================================================================================================================

?>