<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Chromewebstore\Resource;

use Google\Service\Chromewebstore\UploadItemPackageRequest;
use Google\Service\Chromewebstore\UploadItemPackageResponse;

/**
 * The "media" collection of methods.
 * Typical usage is:
 *  <code>
 *   $chromewebstoreService = new Google\Service\Chromewebstore(...);
 *   $media = $chromewebstoreService->media;
 *  </code>
 */
class Media extends \Google\Service\Resource
{
  /**
   * Upload a new package to an existing item. (media.upload)
   *
   * @param string $name Required. Name of the item to upload the new package to
   * in the form `publishers/{publisherId}/items/{itemId}`
   * @param UploadItemPackageRequest $postBody
   * @param array $optParams Optional parameters.
   * @return UploadItemPackageResponse
   * @throws \Google\Service\Exception
   */
  public function upload($name, UploadItemPackageRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('upload', [$params], UploadItemPackageResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Media::class, 'Google_Service_Chromewebstore_Resource_Media');
