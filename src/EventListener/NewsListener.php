<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2021, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleNews;
use Contao\ModuleNewsList;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;


class NewsListener {


    /**
     * Return number of matching items if tag was given
     *
     * @param array $newsArchives
     * @param boolean $blnFeatured
     *
     * @return integer|false
     *
     * @Hook("newsListCountItems")
     */
    public function newsListCountItems($newsArchives, $blnFeatured) {

        $tag = Input::get('tag');

        if( !empty($tag) ) {

            $oArticles = null;
            $oArticles = $this->newsListFetchItems( $newsArchives, $blnFeatured, 0, 0 );

            return count($oArticles);
        }

        return false;
    }


    /**
     * Sort out non matching articles if tag was given
     *
     * @param array   $newsArchives
     * @param boolean $blnFeatured
     * @param integer $limit
     * @param integer $offset
     *
     * @return Model\Collection|NewsModel|false
     *
     * @Hook("newsListFetchItems")
     */
    public function newsListFetchItems( $newsArchives, $blnFeatured, $limit, $offset, ModuleNewsList $module=null ) {

        global $objPage;

        $tag = Input::get('tag');

        if( !empty($tag) ) {

            // set current page to noindex, add canonical
            $objPage->robots = 'noindex,nofollow';
            $GLOBALS['TL_HEAD'][] = '<link rel="canonical" href="'.$objPage->getAbsoluteUrl().'" />';

            // determine sorting
            $t = NewsModel::getTable();
            $arrOptions = array();

            if( $module ) {

                switch( $module->news_order ) {

                    case 'order_headline_asc':
                        $arrOptions['order'] = "$t.headline";
                        break;
                    case 'order_headline_desc':
                        $arrOptions['order'] = "$t.headline DESC";
                        break;
                    case 'order_random':
                        $arrOptions['order'] = "RAND()";
                        break;
                    case 'order_date_asc':
                        $arrOptions['order'] = "$t.date";
                        break;
                    default:
                        $arrOptions['order'] = "$t.date DESC";
                }
            }

            // TODO: Replace with custom query
            $collection = null;
            $collection = NewsModel::findPublishedByPids($newsArchives, $blnFeatured, 0, 0, $arrOptions);

            $articles = [];
            $articles = $collection->getModels();

            // sort out non matching tags
            if( !empty($tag) ) {

                // get tag id
                $oTag = null;
                $oTag = TagsModel::findOneByTag( $tag );

                if( $oTag ) {

                    foreach( $articles as $i => $current ) {

                        $tags = $current->getRelated('tags');

                        if( !empty($tags) ) {

                            $tags = $tags->fetchEach('id');

                            if( in_array($oTag->id, $tags) ) {
                                continue;
                            }
                        }

                        unset($articles[$i]);
                    }
                }
            }

            // limit articles
            if( $limit || $offset ) {
                $articles = array_slice($articles, $offset, $limit);
            }

            return new Collection($articles,NewsModel::getTable());
        }

        return false;
    }


    /**
     * Adds our additional data to the article
     *
     * @param FrontendTemplate $objTemplate
     * @param $arrArticle
     * @param ModuleNews $objModule
     *
     * @return none
     *
     * @Hook("parseArticles")
     */
    public function parseArticles( FrontendTemplate &$objTemplate, $arrArticle, ModuleNews $objModule ) {

        // add tags
        if( $arrArticle['tags'] ) {

            $pageList = null;

            if( $objModule->jumpToTags ) {
                $pageList = PageModel::findWithDetails($objModule->jumpToTags);
            }

            $aTagIDs = [];
            $aTagIDs = StringUtil::deserialize($arrArticle['tags']);

            if( !empty($aTagIDs) ) {

                $oTags = null;
                $oTags = TagsModel::findMultipleByIds($aTagIDs);

                if( $oTags ) {

                    if( $pageList ) {

                        $aLinks = [];

                        foreach( $oTags->fetchEach('tag') as $id => $tag ) {

                            $href = $pageList->getFrontendUrl('/tag/'.$tag);

                            $aLinks[] = sprintf(
                                '<a href="%s" rel="nofollow">%s</a>'
                                ,   $href
                                ,   $tag
                            );
                        }

                        $objTemplate->tags = $aLinks;

                    } else {
                        $objTemplate->tags = $oTags->fetchEach('tag');
                    }
                }
            }
        }
    }
}
