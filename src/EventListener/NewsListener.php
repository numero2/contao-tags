<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleNews;
use Contao\ModuleNewsList;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use numero2\TagsBundle\ModuleNewsListRelatedTags;
use numero2\TagsBundle\ModuleNewsListTags;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;
use numero2\TagsBundle\Util\TagUtil;


class NewsListener {


    /**
     * Return number of matching items if tag was given
     *
     * @param array $newsArchives
     * @param boolean $blnFeatured
     * @param ModuleNewsList $module
     *
     * @return integer|false
     *
     * @Hook("newsListCountItems")
     */
    public function newsListCountItems( $newsArchives, $blnFeatured, ModuleNewsList $module ) {

        $tag = Input::get('tag');

        if( $module instanceof ModuleNewsListRelatedTags ) {

            $alias = Input::get('items');
            $currentNews = NewsModel::findPublishedByParentAndIdOrAlias($alias, $newsArchives);

            $news = TagsRelModel::findPublishedRelatedNewsByID($currentNews->id, $newsArchives, $blnFeatured);

            if( $news ) {
                return $news->count();
            }

            return 0;

        } else if( $module instanceof ModuleNewsListTags ) {

            $tags = StringUtil::deserialize($module->news_tags, true);
            $blnMultiple = !empty($module->tags_match_all);

            $news = TagsRelModel::findPublishedNewsByTags($tags, $newsArchives, $blnFeatured, $blnMultiple);

            if( $news && !$tag ) {
                return $news->count();
            }
        }

        if( $module->ignoreTags ) {
            return false;
        }

        if( !empty($tag) ) {

            $articles = null;
            $articles = $this->newsListFetchItems($newsArchives, $blnFeatured, 0, 0, $module);

            return count($articles);
        }

        return false;
    }


    /**
     * Sort out non matching articles if tag was given
     *
     * @param array $newsArchives
     * @param boolean $blnFeatured
     * @param integer $limit
     * @param integer $offset
     * @param ModuleNewsList $module
     *
     * @return Model\Collection|NewsModel|false
     *
     * @Hook("newsListFetchItems", priority=100)
     */
    public function newsListFetchItems( $newsArchives, $blnFeatured, $limit, $offset, ModuleNewsList $module ) {

        $preSelectedNews = [];
        $news = null;

        // determine sorting
        $t = NewsModel::getTable();
        $arrOptions = [];

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

        if( $module instanceof ModuleNewsListRelatedTags ) {

            $alias = Input::get('items');
            $currentNews = NewsModel::findPublishedByParentAndIdOrAlias($alias, $newsArchives);

            $news = TagsRelModel::findPublishedRelatedNewsByID($currentNews->id, $newsArchives, $blnFeatured, $limit, $offset, $arrOptions);

            return $news;

        } else if( $module instanceof ModuleNewsListTags ) {

            $moduleTags = StringUtil::deserialize($module->news_tags, true);
            $blnMatchAll = !empty($module->tags_match_all);

            $news = TagsRelModel::findPublishedNewsByTags($moduleTags, $newsArchives, $blnFeatured, $blnMatchAll, 0, 0, $arrOptions);

            // fill array with news matching the pre-selected tag
            if( $news ) {
                $preSelectedNews = $news->fetchEach('id');
            }
        }

        if( $module->ignoreTags ) {
            return false;
        }

        $urlTags = TagUtil::getTagsFromUrl();

        // filter by given tag
        if( !empty($urlTags) || !empty($preSelectedNews) ) {

            if( !empty($urlTags) ) {

                if( System::getContainer()->has('contao.routing.response_context_accessor') ) {

                    $responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

                    if( $responseContext && $responseContext->has(HtmlHeadBag::class) ) {

                        $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);

                        $htmlHeadBag->setMetaRobots('noindex,nofollow');

                        // overwrite canoncial
                        $requestStack = System::getContainer()->get('request_stack');
                        $request = $requestStack->getCurrentRequest();
                        $page = $request->get('pageModel');

                        if( $page->enableCanonical ) {
                            $url = $page->getAbsoluteUrl();
                            $htmlHeadBag->setCanonicalUri($url);
                        }
                    }
                }
            }

            $collection = null;
            $collection = NewsModel::findPublishedByPids($newsArchives, $blnFeatured, 0, 0, $arrOptions);

            $articles = [];
            $articles = $collection->getModels();

            // sort out non matching tags
            if( !empty($urlTags) ) {

                // get tags id
                $aUrlTags = [];
                foreach( $urlTags as $tag ) {
                    $oTag = TagsModel::findOneByTag($tag);
                    if( $oTag ) {
                        $aUrlTags[] = $oTag->id;
                    }
                }
            }

            foreach( $articles as $i => $article ) {

                // remove all news that do not match the pre-selected set of results
                if( !empty($preSelectedNews) && !in_array($article->id, $preSelectedNews) ) {

                    unset($articles[$i]);
                    continue;
                }

                $newsTags = TagsModel::findByIdForFieldAndTable($article->id, 'tags', NewsModel::getTable());

                if( empty($newsTags) ) {
                    $newsTags = [];
                } else {
                    $newsTags = $newsTags->fetchEach('id');
                }

                if( !empty($aUrlTags) ) {

                    if( !empty($module->tags_match_all) ) {
                        if( count(array_intersect($aUrlTags, $newsTags)) === count($aUrlTags) ) {
                            continue;
                        }
                    } else {
                        if( count(array_intersect($aUrlTags, $newsTags)) ) {
                            continue;
                        }
                    }

                    unset($articles[$i]);
                }
            }

            // limit articles
            if( $limit || $offset ) {
                $articles = array_slice($articles, $offset, $limit);
            }

            return new Collection($articles, $t);
        }

        // limit articles
        if( $module instanceof ModuleNewsListTags && ($limit || $offset) ) {

            $articles = [];
            $articles = $news->getModels();
            $articles = array_slice($articles, $offset, $limit);

            return new Collection($articles, $t);
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

            $oTags = null;
            $oTags = TagsModel::findByIdForFieldAndTable($arrArticle['id'], 'tags', NewsModel::getTable());

            if( $oTags ) {

                $tagsRaw = [];
                $tags = [];

                foreach( $oTags as $tag ) {

                    $aTag = TagUtil::parseTag($tag);

                    $tagsRaw[] = $aTag;

                    if( $pageList ) {

                        $href = TagUtil::generateUrlWithTags($pageList, [$aTag['tag']]);

                        $tags[] = sprintf(
                            '<a href="%s" class="tag_%s" rel="nofollow">%s</a>'
                        ,   $href
                        ,   StringUtil::standardize($aTag['tag'])
                        ,   $aTag['title']
                        );

                    } else {

                        $tags[] = $aTag['title'];
                    }
                }

                $objTemplate->tagsRaw = $tagsRaw;
                $objTemplate->tags = $tags;
            }
        }
    }
}
