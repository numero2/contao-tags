<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\Util\TagUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * @FrontendModule("news_tag_cloud",
 *   category="news",
 *   template="mod_news_tag_cloud",
 * )
 */
class TagCloudController extends AbstractFrontendModuleController {


    /**
     * {@inheritdoc}
     */
    protected function getResponse( Template $template, ModuleModel $model, Request $request ): Response {

        $page = $this->getPageModel();

        $aArchives = StringUtil::deserialize($model->news_archives);
        $oTags = TagsModel::findByNewsArchives($aArchives);

        if( $oTags && !empty($aArchives) ) {

            $aTags = [];
            $aTagsAvailable = $oTags->fetchEach('tag');

            $oPageRedirect = $model->jumpToTags?PageModel::findOneById($model->jumpToTags):$page;
            $oPageRedirect = $oPageRedirect?:$page;

            $activeTags = TagUtil::getTagsFromUrl();
            $activeTags = array_intersect($aTagsAvailable, $activeTags);

            foreach( $oTags as $oTag ) {

                $alias = $oTag->tag;

                $parameterTags = null;
                $active = null;

                if( empty($model->tags_select_multiple) ) {

                    $active = in_array($alias, $activeTags);
                    $parameterTags = [$alias];

                } else {

                    $active = in_array($alias, $activeTags);
                    if( $active ) {
                        $parameterTags = array_diff($activeTags, [$alias]);
                    } else {
                        $parameterTags = [...$activeTags, $alias];
                    }
                }

                $href = TagUtil::generateUrlWithTags($oPageRedirect, $parameterTags, !empty($model->use_get_parameter));

                $aTags[] = [
                    'label' => $oTag->tag
                ,   'active'=> $active
                ,   'href'  => $href
                ,   'count' => TagsModel::countByIdAndNewsArchives($oTag->id, $aArchives)
                ,   'class' => 'tag_' . StringUtil::standardize($oTag->tag).($active?' active':'')
                ];
            }

            $template->tags = $aTags;
            $template->selectMultiple = !empty($model->tags_select_multiple);

            if( Input::get('tag') !== null ) {
                $template->resetHref = $page->getFrontendUrl();
            }

            return $template->getResponse();
        }

        return new Response('');
    }
}
