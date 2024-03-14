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

use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use numero2\TagsBundle\TagsModel;
use Symfony\Component\HttpFoundation\Request;


/**
 * @FrontendModule("events_tag_cloud",
 *   category="events",
 *   template="mod_events_tag_cloud",
 * )
 */
class EventsTagCloudController extends AbstractTagCloudController {


    /**
     * {@inheritdoc}
     */
    protected function getTags( ModuleModel $model, Request $request ): ?Collection {

        $aArchives = StringUtil::deserialize($model->cal_calendar, true);

        return TagsModel::findByCalendar($aArchives);
    }


    /**
     * {@inheritdoc}
     */
    protected function getTagCount( TagsModel $tag, ModuleModel $model, Request $request ): int {

        $aArchives = StringUtil::deserialize($model->cal_calendar, true);

        return TagsModel::countByIdAndCalendar($tag->id, $aArchives);
    }
}
