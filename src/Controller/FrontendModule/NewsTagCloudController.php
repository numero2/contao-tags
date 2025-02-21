<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Controller\FrontendModule;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use numero2\TagsBundle\TagsModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * @FrontendModule("news_tag_cloud",
 *   category="news",
 *   template="mod_news_tag_cloud",
 * )
 */
class NewsTagCloudController extends AbstractTagCloudController {


    public function __construct( EventDispatcherInterface $eventDispatcher ) {

        $this->field = 'tags';
        $this->table = NewsModel::getTable();

        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * {@inheritdoc}
     */
    protected function getTags( ModuleModel $model, Request $request ): ?Collection {

        $aArchives = StringUtil::deserialize($model->news_archives, true);
        $aArchives = $this->sortOutProtected($aArchives);

        $blnFeatured = $model->news_featured;
        if( $blnFeatured == 'featured' ) {
            $blnFeatured = true;
        } else if( $blnFeatured == 'unfeatured' ) {
            $blnFeatured = false;
        } else {
            $blnFeatured = null;
        }

        $aTags = StringUtil::deserialize($model->news_tags, true);

        if( !empty($aTags) ) {

            $blnMatchAll = !empty($model->tags_match_all);

            return TagsModel::findByTagsAndNewsArchives($aTags, $aArchives, $blnMatchAll, $blnFeatured);
        }

        return TagsModel::findByNewsArchives($aArchives, $blnFeatured);
    }


    /**
     * {@inheritdoc}
     */
    protected function getTagCount( TagsModel $tag, ModuleModel $model, Request $request ): int {

        $aArchives = StringUtil::deserialize($model->news_archives, true);
        $aArchives = $this->sortOutProtected($aArchives);

        $blnFeatured = $model->news_featured;
        if( $blnFeatured == 'featured' ) {
            $blnFeatured = true;
        } else if( $blnFeatured == 'unfeatured' ) {
            $blnFeatured = false;
        } else {
            $blnFeatured = null;
        }

        $aTags = StringUtil::deserialize($model->news_tags, true);

        if( !empty($aTags) ) {

            $blnMatchAll = !empty($model->tags_match_all);

            return TagsModel::countByIdAndTagsAndNewsArchives($tag->id, $aTags, $aArchives, $blnMatchAll, $blnFeatured);
        }

        return TagsModel::countByIdAndNewsArchives($tag->id, $aArchives, $blnFeatured);
    }


    /**
     * Sort out protected archives, mainly taken from contao/contao
     *
     * @param array $aArchives
     *
     * @return array
     */
    protected function sortOutProtected( array $aArchives ): array {

        if( empty($aArchives) || !\is_array($aArchives) ) {
            return $aArchives;
        }

        $oArchive = NewsArchiveModel::findMultipleByIds($aArchives);
        $aArchives = [];

        if( $oArchive !== null ) {
            $security = System::getContainer()->get('security.helper');

            while( $oArchive->next() ) {
                if( $oArchive->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($oArchive->groups, true)) ) {
                    continue;
                }

                $aArchives[] = $oArchive->id;
            }
        }

        return $aArchives;
    }
}
