<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
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
        $oTags = TagsModel::findByArchives($aArchives);

        if( $oTags && !empty($aArchives) ) {

            $aTags = [];

            $oPageRedirect = $model->jumpToTags?PageModel::findOneById($model->jumpToTags):$page;
            $oPageRedirect = $oPageRedirect?:$page;

            foreach( $oTags as $oTag ) {

                $alias = $oTag->tag;

                $aTags[] = [
                    'label' => $oTag->tag
                ,   'active'=> $alias == Input::get('tag')
                ,   'href'  => $model->use_get_parameter?$oPageRedirect->getFrontendUrl().'?tag='.urlencode($alias):$oPageRedirect->getFrontendUrl('/tag/'.$alias)
                ,   'count' => TagsModel::countById($oTag->id, $aArchives)
                ,   'class' => 'tag_' . StringUtil::standardize($oTag->tag)
                ];
            }

            $template->tags = $aTags;

            if( Input::get('tag') !== null ) {
                $template->resetHref = $page->getFrontendUrl();
            }

            return $template->getResponse();
        }

        return new Response('');
    }
}
