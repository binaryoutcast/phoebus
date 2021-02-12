<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ========================================================================================================

switch ($arraySoftwareState['requestPath']) {
  case URI_DEV:
    if (funcCheckAccessLevel(3, true)) {
      funcRedirect(URI_ADMIN);
    }

    $moduleGenerateContent->addonSite('developer-frontpage.xhtml', 'Add-on Developer');
    break;
  case URI_ACCOUNT:
    // Users level 3 or above should use the administration codepath
    if (funcCheckAccessLevel(3, true)) {
      funcRedirect(URI_ADMIN . '?task=update&what=user&slug=' . $arraySoftwareState['authentication']['username']);
    }

    $userManifest = $moduleAccount->getSingleUser($arraySoftwareState['authentication']['username'], true);

    // Check if manifest is valid
    if (!$userManifest) {
      funcError('User Manifest is null');
    }

    // Deal with writing the updated user manifest
    if ($boolHasPostData) {
      $boolUpdate = $moduleAccount->updateUserManifest($userManifest);

      // If an error happened stop.
      if (!$boolUpdate) {
        funcError('Something has gone horribly wrong');
      }

      // Manifest updated go somewhere
      funcRedirect(URI_DEV);
    }

    $moduleGenerateContent->addonSite('developer-account', 'Your Account', $arraySoftwareState['authentication']);
    break;
  case URI_ADDONS:
    // Serve the Developer Add-ons page
    if ($arraySoftwareState['requestPath'] == URI_ADDONS && !$arraySoftwareState['requestPanelTask']) {
      // Users level 3 or above should use the administration codepath
      if (funcCheckAccessLevel(3, true)) {
        funcRedirect(URI_ADMIN . '?task=list&what=user-addons&slug=' . $arraySoftwareState['authentication']['username']);
      }

      $addons = $moduleReadManifest->getAddons('panel-user-addons', $arraySoftwareState['authentication']['addons']) ?? [];
      $moduleGenerateContent->addonSite('developer-addons-list', 'Your Add-ons', $addons);
    }

    // Users level 3 and above should redirect to the administration codepath
    if (funcCheckAccessLevel(3, true)) {
      funcRedirect(str_replace(URI_ADDONS, URI_ADMIN, $arraySoftwareState['phpRequestURI']));
    }

    switch ($arraySoftwareState['requestPanelTask']) {
      case 'submit':
        if (!$arraySoftwareState['requestPanelWhat']) {
          funcError('You did not specify what you want to submit');
        }

        switch ($arraySoftwareState['requestPanelWhat']) {
          case 'addon':
            if ($boolHasPostData) {
              $finalSlug = $moduleWriteManifest->submitNewAddon();

              // If an error happened stop.
              if (!$finalSlug) {
                funcError('Something has gone horribly wrong');
              }

              $moduleLog->record('SUCCESS - Submitted Add-on: ' . $finalSlug);

              // Add-on Submitted go to edit metadata
              funcRedirect(URI_ADDONS . '?task=update&what=metadata' . '&slug=' . $finalSlug);
            }

            // Generate the submit page
            $moduleGenerateContent->addonSite('panel-submit-addon', 'Submit new Add-on');
            break;
          default:
            funcError('Invalid submit request');
        }
        break;
      case 'update':
        switch ($arraySoftwareState['requestPanelWhat']) {
          case 'release':
            // Check for valid slug
            if (!$arraySoftwareState['requestPanelSlug']) {
              funcError('You did not specify a slug');
            }

            // Get the manifest
            $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $arraySoftwareState['requestPanelSlug']);

            // Check if manifest is valid
            if (!$addonManifest || !in_array($addonManifest['type'], ['extension', 'theme'])) {
              funcError('Add-on Manifest is null');
            }

            if (!in_array($arraySoftwareState['requestPanelSlug'], $arraySoftwareState['authentication']['addons'])) {
              $moduleLog->record('FAIL - Tried to update an add-on that was not assigned: ' . $addonManifest['slug']);
              funcError('You do not own this add-on. Stop trying to fuck with other people\'s shit!');
            }

            if ($boolHasPostData) {
              $finalType = $moduleWriteManifest->updateAddonRelease($addonManifest);

              // If an error happened stop.
              if (!$finalType) {
                funcError('Something has gone horribly wrong');
              }

              // Add-on Submitted go to edit metadata
              $moduleLog->record('SUCCESS - Updated Add-on Release: ' . $addonManifest['slug']);
              funcRedirect(URI_ADDONS);
            }

            $moduleGenerateContent->addonSite('panel-update-release', 'Release new version Add-on', $addonManifest['slug']);
            break;
          case 'metadata':
            // Check for valid slug
            if (!$arraySoftwareState['requestPanelSlug']) {
              funcError('You did not specify a slug');
            }

            // Get the manifest
            $addonManifest = $moduleReadManifest->getAddon('panel-by-slug', $arraySoftwareState['requestPanelSlug']);

            // Check if manifest is valid
            if (!$addonManifest || !in_array($addonManifest['type'], ['extension', 'theme'])) {
              funcError('Add-on Manifest is null');
            }

            if (!in_array($arraySoftwareState['requestPanelSlug'], $arraySoftwareState['authentication']['addons'])) {
              funcError('You do not own this add-on. Stop trying to fuck with other people\'s shit!');
            }

            // We have post data so we should update the manifest data via classWriteManifest
            if ($boolHasPostData) {
              $boolUpdate = $moduleWriteManifest->updateAddonMetadata($addonManifest);

              // If an error happened stop.
              if (!$boolUpdate) {
                funcError('Something has gone horribly wrong');
              }

              $moduleLog->record('SUCCESS - Updated Add-on Metadata: ' . $addonManifest['slug']);

              // Manifest updated go somewhere
              funcRedirect(URI_ADDONS);
            }

            // Create an array to hold extra data to send to smarty
            // Such as the list of licenses
            $arrayExtraData = array('licenses' => array_keys($moduleReadManifest::LICENSES));

            // Extensions need the associative array of extension categories as well
            if ($addonManifest['type'] == 'extension') {
              $arrayExtraData['categories'] = $moduleReadManifest::EXTENSION_CATEGORY_SLUGS;
            }

            // Generate the edit add-on metadata page
            $moduleGenerateContent->addonSite('developer-edit-addon-metadata',
                                               'Editing Metadata for ' . $addonManifest['name'],
                                               $addonManifest,
                                               $arrayExtraData);
            break;
          default:
            funcError('Invalid update request');
        }
        break;
      default:
        funcSendHeader('501');
    }
    break;
}

// ====================================================================================================================

?>