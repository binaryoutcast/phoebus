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
      case 'extensions':
      case 'externals':
      case 'themes':
      case 'langpacks':
        $addons = $moduleReadManifest->getAddons('panel-addons-by-type',
                                                 substr($arraySoftwareState['requestPanelWhat'], 0, -1));

        $moduleGenerateContent->addonSite('admin-list-' . $arraySoftwareState['requestPanelWhat'],
                                          ucfirst($arraySoftwareState['requestPanelWhat']) . ' - Administration',
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
      default:
        funcError('Invalid list request');
    }
    break;
  case 'submit':
    funcSendHeader('501');
    break;
  case 'update':
    if (!$arraySoftwareState['requestPanelWhat'] || !$arraySoftwareState['requestPanelSlug']) {
      funcError('You did not specify what you want to update');
    }

    switch ($arraySoftwareState['requestPanelWhat']) {
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

        // Extenrals need special handling so just send back the manifest for now
        if ($addonManifest['type'] == 'external') {
          funcError($addonManifest, 98);
        }

        if ($addonManifest['type'] == 'langpack') {
          funcError('Language Packs are not handled using this function. Stop being a moron.');
        }

        // We have post data so we should update the manifest data via classWriteManifest
        if ($boolHasPostData) {
          $boolUpdate = $moduleWriteManifest->updateAddonMetadata($addonManifest);

          // If an error happened stop.
          if (!$boolUpdate) {
            funcError('Something has gone horribly wrong');
          }

          // Manifest updated go somewhere
          funcRedirect(URI_ADMIN . '?task=list&what=' . $addonManifest['type'] . 's');
        }

        // Create an array to hold extra data to send to smarty
        // Such as the list of licenses
        $arrayExtraData = array('licenses' => array_keys($moduleReadManifest::LICENSES));

        // Extensions need the associative array of extension categories as well
        if ($addonManifest['type'] == 'extension') {
          $arrayExtraData['categories'] = $moduleReadManifest::EXTENSION_CATEGORY_SLUGS;
        }

        // Generate the edit add-on metadata page
        $moduleGenerateContent->addonSite('admin-edit-addon-metadata',
                                           'Editing Metadata for ' . $addonManifest['name'],
                                           $addonManifest,
                                           $arrayExtraData);
        break;
      case 'release':
        funcSendHeader('501');
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
  default:
    funcSendHeader('501');
}

// ====================================================================================================================

?>