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

namespace Google\Service\DisplayVideo;

class YoutubeAssetAssociationLinkedYouTubeAsset extends \Google\Model
{
  protected $affiliateLocationAssetFilterType = YoutubeAssetAssociationAffiliateLocationAssetFilter::class;
  protected $affiliateLocationAssetFilterDataType = '';
  protected $locationAssetFilterType = YoutubeAssetAssociationLocationAssetFilter::class;
  protected $locationAssetFilterDataType = '';
  protected $sitelinkAssetType = YoutubeAssetAssociationSitelinkAsset::class;
  protected $sitelinkAssetDataType = '';

  /**
   * @param YoutubeAssetAssociationAffiliateLocationAssetFilter
   */
  public function setAffiliateLocationAssetFilter(YoutubeAssetAssociationAffiliateLocationAssetFilter $affiliateLocationAssetFilter)
  {
    $this->affiliateLocationAssetFilter = $affiliateLocationAssetFilter;
  }
  /**
   * @return YoutubeAssetAssociationAffiliateLocationAssetFilter
   */
  public function getAffiliateLocationAssetFilter()
  {
    return $this->affiliateLocationAssetFilter;
  }
  /**
   * @param YoutubeAssetAssociationLocationAssetFilter
   */
  public function setLocationAssetFilter(YoutubeAssetAssociationLocationAssetFilter $locationAssetFilter)
  {
    $this->locationAssetFilter = $locationAssetFilter;
  }
  /**
   * @return YoutubeAssetAssociationLocationAssetFilter
   */
  public function getLocationAssetFilter()
  {
    return $this->locationAssetFilter;
  }
  /**
   * @param YoutubeAssetAssociationSitelinkAsset
   */
  public function setSitelinkAsset(YoutubeAssetAssociationSitelinkAsset $sitelinkAsset)
  {
    $this->sitelinkAsset = $sitelinkAsset;
  }
  /**
   * @return YoutubeAssetAssociationSitelinkAsset
   */
  public function getSitelinkAsset()
  {
    return $this->sitelinkAsset;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(YoutubeAssetAssociationLinkedYouTubeAsset::class, 'Google_Service_DisplayVideo_YoutubeAssetAssociationLinkedYouTubeAsset');
