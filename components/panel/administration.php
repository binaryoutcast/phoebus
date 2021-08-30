<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ========================================================================================================

// Serve the Adminsitration landing page
if ($gaRuntime['qPath'] == URI_ADMIN && !$gaRuntime['qPanelTask']) {
  $gmGenerateContent->addonSite('admin-frontpage.xhtml', 'Administration');
}

switch ($gaRuntime['qPanelTask']) {
  case 'list':
    if (!$gaRuntime['qPanelWhat']) {
      gfError('You did not specify what you want to list');
    }

    switch ($gaRuntime['qPanelWhat']) {
      case 'langpacks':
        if ($gaRuntime['authentication']['level'] < 4) {
          gfError('You are not allowed to list language packs!');
        }
      case 'extensions':
      case 'externals':
      case 'themes':
        $addons = $gmReadManifest->getAddons('panel-addons-by-type',
                                                 substr($gaRuntime['qPanelWhat'], 0, -1));

        $gmGenerateContent->addonSite('admin-list-' . $gaRuntime['qPanelWhat'],
                                          ucfirst($gaRuntime['qPanelWhat']) . ' - Administration',
                                          $addons);
        break;
      case 'unreviewed':
        $addons = $gmReadManifest->getAddons('panel-unreviewed-addons');
        $gmGenerateContent->addonSite('admin-list-unreviewed',
                                          'Unreviewed Add-ons - Administration',
                                          $addons);
        break;
      case 'users':
        $users = $gmAccount->getUsers();
        $gmGenerateContent->addonSite('admin-list-users', 'Users - Administration', $users);
      case 'user-addons':
        if (!$gaRuntime['qPanelSlug']) {
          gfError('You did not specify a slug (username)');
        }

        $userManifest = $gmAccount->getSingleUser($gaRuntime['qPanelSlug'], true);

        // Check if manifest is valid
        if (!$userManifest) {
          gfError('User Manifest is null');
        }

        $addons = $gmReadManifest->getAddons('panel-user-addons', $userManifest['addons']) ?? [];
        $gmGenerateContent->addonSite('admin-user-addons-list',
                                          ($userManifest['displayName'] ?? $userManifest['username']) . '\'s Add-ons',
                                          $addons);
      break;
      case 'logs':
        $logs = $gmLog->fetch();
        $gmGenerateContent->addonSite('admin-list-logs', 'Last 100 Logged Events', $logs);
        break;
      default:
        gfError('Invalid list request');
    }
    break;
  case 'submit':
    if (!$gaRuntime['qPanelWhat']) {
      gfError('You did not specify what you want to submit');
    }

    switch ($gaRuntime['qPanelWhat']) {
      case 'addon':
      case 'langpack':
        $isLangPack = (bool)($gaRuntime['qPanelWhat'] == 'langpack');
        $strTitle = $isLangPack ? 'Pale Moon Language Pack' : 'Add-on';
        if ($boolHasPostData) {
          $finalSlug = $gmWriteManifest->submitNewAddon($isLangPack);

          // If an error happened stop.
          if (!$finalSlug) {
            gfError('Something has gone horribly wrong');
          }

          // Add-on Submitted go to edit metadata
          $gmLog->record('[ADMIN] SUCCESS - Submitted Add-on: ' . $finalSlug);
          gfRedirect(URI_ADMIN . '?task=update&what=metadata' . '&slug=' . $finalSlug);
        }

        // Generate the submit page
        $gmGenerateContent->addonSite('panel-submit-' . $gaRuntime['qPanelWhat'],
                                          'Submit new ' . $strTitle);
        break;
      case 'external':
        if ($boolHasPostData) {
          $finalSlug = $gmWriteManifest->submitNewExternal();

          // If an error happened stop.
          if (!$finalSlug) {
            gfError('Something has gone horribly wrong');
          }

          // External Submitted go to edit metadata
          $gmLog->record('[ADMIN] SUCCESS - Submitted External: ' . $finalSlug);
          gfRedirect(URI_ADMIN . '?task=update&what=metadata' . '&slug=' . $finalSlug);
        }

        // Generate the submit page
        $gmGenerateContent->addonSite('panel-submit-external', 'Submit new External');
        break;
      default:
        gfError('Invalid submit request');
    }
    break;
  case 'update':
    if (!$gaRuntime['qPanelWhat'] || !$gaRuntime['qPanelSlug']) {
      gfError('You did not specify what you want to update');
    }

    switch ($gaRuntime['qPanelWhat']) {
      case 'release':
        // Check for valid slug
        if (!$gaRuntime['qPanelSlug']) {
          gfError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $gmReadManifest->getAddon('panel-by-slug', $gaRuntime['qPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          gfError('Add-on Manifest is null');
        }

        $isLangPack = (bool)($addonManifest['type'] == 'langpack');

        if ($isLangPack && $gaRuntime['authentication']['level'] < 4) {
          $gmLog->record('[ADMIN] FAIL - Tried to update a language pack release ' . $addonManifest['slug']);
          gfError('You are not allowed to update language packs!');
        }

        if ($addonManifest['type'] == 'external') {
          $gmLog->record('[ADMIN] FAIL - Tried to update external release' . $addonManifest['slug']);
          gfError('Externals do not physically exist here.. Are you a moron?');
        }

        if ($boolHasPostData) {
          $finalType = $gmWriteManifest->updateAddonRelease($addonManifest, $isLangPack);

          // If an error happened stop.
          if (!$finalType) {
            gfError('Something has gone horribly wrong');
          }

          // Add-on Submitted go to edit metadata
          $gmLog->record('[ADMIN] SUCCESS - Updated Add-on Release: ' . $addonManifest['slug']);
          gfRedirect(URI_ADMIN . '?task=list&what=' . $finalType);
        }

        $gmGenerateContent->addonSite('panel-update-release', 'Release new version', $addonManifest['slug']);
        break;
      case 'metadata':
        // Check for valid slug
        if (!$gaRuntime['qPanelSlug']) {
          gfError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $gmReadManifest->getAddon('panel-by-slug', $gaRuntime['qPanelSlug']);

        // Check if manifest is valid
        if (!$addonManifest) {
          gfError('Add-on Manifest is null');
        }

        if ($addonManifest['type'] == 'langpack' && $gaRuntime['authentication']['level'] < 4) {
          $gmLog->record('[ADMIN] FAIL - Tried to update language pack metadata' . $addonManifest['slug']);
          gfError('You are not allowed to edit language packs!');
        }

        // We have post data so we should update the manifest data via classWriteManifest
        if ($boolHasPostData) {
          // Extenrals need special handling
          if ($addonManifest['type'] == 'external') {
            $boolUpdate = $gmWriteManifest->updateExternalMetadata($addonManifest);
          }
          else {
            $boolUpdate = $gmWriteManifest->updateAddonMetadata($addonManifest);
          }

          // If an error happened stop.
          if (!$boolUpdate) {
            gfError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          $gmLog->record('[ADMIN] SUCCESS - Updated Add-on/External Metadata: ' . $addonManifest['slug']);
          gfRedirect(URI_ADMIN . '?task=list&what=' . $addonManifest['type'] . 's');
        }

        // Create an array to hold extra data to send to smarty
        // Such as the list of licenses
        $arrayExtraData = array('licenses' => array_keys($gmReadManifest::LICENSES));

        // Extensions need the associative array of extension categories as well
        if ($addonManifest['type'] != 'theme') {
          $arrayExtraData['categories'] = $gmReadManifest::EXTENSION_CATEGORY_SLUGS;
        }

        $strMetadataType = 'addon';

        // Extenrals need special handling
        if ($addonManifest['type'] == 'external') {
          $strMetadataType = 'external';
        }

        // Generate the edit add-on metadata page
        $gmGenerateContent->addonSite('admin-edit-' . $strMetadataType . '-metadata',
                                           'Editing Metadata for ' . ($addonManifest['name'] ?? $addonManifest['slug']),
                                           $addonManifest,
                                           $arrayExtraData);
        break;
      case 'user':
        // Check for valid slug
        if (!$gaRuntime['qPanelSlug']) {
          gfError('You did not specify a slug (username)');
        }

        $userManifest = $gmAccount->getSingleUser($gaRuntime['qPanelSlug'], true);

        // Check if manifest is valid
        if (!$userManifest) {
          gfError('User Manifest is null');
        }

        // Do not allow editing of users at or above a user level unless they are you or you are level 5
        if ($gaRuntime['authentication']['level'] != 5 &&
            $userManifest['level'] >= $gaRuntime['authentication']['level'] &&
            $userManifest['username'] != $gaRuntime['authentication']['username']) {
          $gmLog->record('[ADMIN] FAIL - Tried to update User Metadata: ' . $gaRuntime['qPanelSlug']);
          gfError('You attempted to alter a user account that is the same or higher rank as you but not you. You\'re in trouble!');
        }

        // Deal with writing the updated user manifest
        if ($boolHasPostData) {
          $boolUpdate = $gmAccount->updateUserManifest($userManifest);

          // If an error happened stop.
          if (!$boolUpdate) {
            gfError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          $gmLog->record('[ADMIN] SUCCESS - Updated User Metadata: ' . $gaRuntime['qPanelSlug']);
          gfRedirect(URI_ADMIN . '?task=list&what=users');
        }

        $gmGenerateContent->addonSite('admin-edit-account-metadata',
                                          'Editing Account ' . ($userManifest['displayName'] ?? $userManifest['username']),
                                          $userManifest);
        break;
      default:
        gfError('Invalid update request');
    }
    break;
  case 'delete':
    switch ($gaRuntime['qPanelWhat']) {
      case 'addon':
        // Check for valid slug
        if (!$gaRuntime['qPanelSlug']) {
          gfError('You did not specify a slug');
        }

        // Get the manifest
        $addonManifest = $gmReadManifest->getAddon('panel-by-slug', $gaRuntime['qPanelSlug']);

        if ($gaRuntime['authentication']['level'] < 4) {
          $gmLog->record('[ADMIN] FAIL - Tried to delete add-on ' . $addonManifest['slug']);
          gfError('You are not allowed to delete add-ons!');
        }

        // Check if manifest is valid
        if (!$addonManifest) {
          gfError('Add-on Manifest is null');
        }

        if ($boolHasPostData) {
          $boolDelete = $gmWriteManifest->deleteAddon($addonManifest);

          // If an error happened stop.
          if (!$boolDelete) {
            gfError('Something has gone horribly wrong');
          }

          // Add-on deleted go somewhere
          $gmLog->record('[ADMIN] SUCCESS - Deleted Add-on: ' . $gaRuntime['qPanelSlug']);
          gfRedirect(URI_ADMIN . '?task=list&what=' . $addonManifest['type'] . 's');
        }

        // Generate the delete confirmation page
        $gmGenerateContent->addonSite('admin-delete-addon',
                                           'Remove ' . ($addonManifest['name'] ?? $addonManifest['slug']),
                                           $addonManifest);
        break;
      default:
      gfError('Invalid delete request');
    }
    break;
  case 'bulk-upload':
    if (!$gaRuntime['qPanelWhat']) {
      gfError('You did not specify what you want to bulk upload');
    }
    switch ($gaRuntime['qPanelWhat']) {
      case 'langpacks':
        if ($boolHasPostData) {
          $finalResult = $gmWriteManifest->bulkUploader('langpack');

          if (!$finalResult) {
            gfError('Something has gone horribly wrong');
          }

          if (!empty($finalResult['errors'])) {
            $gmGenerateContent->addonSite('addon-bulk-upload-result', 'Bulk Upload Report', $finalResult);
          }

          $gmLog->record('[ADMIN] SUCCESS - Bulk Uploaded Language Packs');
          gfRedirect(URI_ADMIN . '?task=list&what=langpacks');
        }

        $gmGenerateContent->addonSite('addon-bulk-upload-langpack', 'Bulk Upload Language Packs');
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