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

use Contao\CalendarModel;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\Date;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
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

        $aCalendar = StringUtil::deserialize($model->cal_calendar, true);
        $aCalendar = $this->sortOutProtected($aCalendar);

        $blnFeatured = $model->cal_featured;
        if( $blnFeatured == 'featured' ) {
            $blnFeatured = true;
        } else if( $blnFeatured == 'unfeatured' ) {
            $blnFeatured = false;
        } else {
            $blnFeatured = null;
        }

        list($intStart, $intEnd, $strError) = $this->getDatesFromFormat($model);

        $aTags = StringUtil::deserialize($model->event_tags, true);

        if( !empty($aTags) ) {

            $blnMatchAll = !empty($model->tags_match_all);

            return TagsModel::findByTagsAndCalendar($aTags, $aCalendar, $blnMatchAll, $intStart, $intEnd, $blnFeatured);
        }

        return TagsModel::findByCalendar($aCalendar, $intStart, $intEnd, $blnFeatured);
    }


    /**
     * {@inheritdoc}
     */
    protected function getTagCount( TagsModel $tag, ModuleModel $model, Request $request ): int {

        $aCalendar = StringUtil::deserialize($model->cal_calendar, true);
        $aCalendar = $this->sortOutProtected($aCalendar);

        $blnFeatured = $model->cal_featured;
        if( $blnFeatured == 'featured' ) {
            $blnFeatured = true;
        } else if( $blnFeatured == 'unfeatured' ) {
            $blnFeatured = false;
        } else {
            $blnFeatured = null;
        }

        list($intStart, $intEnd, $strError) = $this->getDatesFromFormat($model);

        $aTags = StringUtil::deserialize($model->event_tags, true);

        if( !empty($aTags) ) {

            $blnMatchAll = !empty($model->tags_match_all);

            return TagsModel::countByIdAndTagsAndCalendar($tag->id, $aTags, $aCalendar, $blnMatchAll, $intStart, $intEnd, $blnFeatured);
        }

        return TagsModel::countByIdAndCalendar($tag->id, $aCalendar);
    }


    /**
     * Sort out protected calendars, mainly taken from contao/contao
     *
     * @param array $aCalendars
     *
     * @return array
     */
    protected function sortOutProtected( array $aCalendars ): array {

        if( empty($aCalendars) || !\is_array($aCalendars) ) {
            return $aCalendars;
        }

        $oCalendar = CalendarModel::findMultipleByIds($aCalendars);
        $aCalendars = [];

        if( $oCalendar !== null ) {

            $security = System::getContainer()->get('security.helper');

            while( $oCalendar->next() ) {
                if( $oCalendar->protected && !$security->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, StringUtil::deserialize($oCalendar->groups, true)) ) {
                    continue;
                }

                $aCalendars[] = $oCalendar->id;
            }
        }

        return $aCalendars;
    }


    /**
     * Return the beginning and end timestamp and an error message as array, mainly taken from contao/contao
     *
     * @param Contao\Module $model
     *
     * @return array
     */
    protected function getDatesFromFormat( ModuleModel $model ): array {

        $intYear = null;
        $intMonth = null;
        $intDay = null;

        // Jump to the current period
        if( !isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['day']) ) {

            switch( $model->cal_format ) {
                case 'cal_year':
                    $intYear = date('Y');
                    break;

                case 'cal_month':
                    $intMonth = date('Ym');
                    break;

                case 'cal_day':
                    $intDay = date('Ymd');
                    break;
            }

            $blnClearInput = true;
        }

        $blnDynamicFormat = (!$model->cal_ignoreDynamic && \in_array($model->cal_format, ['cal_day', 'cal_month', 'cal_year']));

        // Create the date object
        $objDate = null;
        $strFormat = null;
        if( $blnDynamicFormat && $intYear ) {
            $objDate = new Date($intYear, 'Y');
            $strFormat = 'cal_year';
        } else if( $blnDynamicFormat && $intMonth ) {
            $objDate = new Date($intMonth, 'Ym');
            $strFormat = 'cal_month';
        } else if( $blnDynamicFormat && $intDay ) {
            $objDate = new Date($intDay, 'Ymd');
            $strFormat = 'cal_day';
        } else {
            $objDate = new Date();
        }

        switch( $strFormat ) {

            case 'cal_day':
                return [$objDate->dayBegin, $objDate->dayEnd, $GLOBALS['TL_LANG']['MSC']['cal_emptyDay']];

            default:
            case 'cal_month':
                return [$objDate->monthBegin, $objDate->monthEnd, $GLOBALS['TL_LANG']['MSC']['cal_emptyMonth']];

            case 'cal_year':
                return [$objDate->yearBegin, $objDate->yearEnd, $GLOBALS['TL_LANG']['MSC']['cal_emptyYear']];

            case 'cal_all': // 1970-01-01 00:00:00 - 2106-02-07 07:28:15
                return [0, min(4294967295, PHP_INT_MAX), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_7':
                return [time(), strtotime('+7 days'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_14':
                return [time(), strtotime('+14 days'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_30':
                return [time(), strtotime('+1 month'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_90':
                return [time(), strtotime('+3 months'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_180':
                return [time(), strtotime('+6 months'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_365':
                return [time(), strtotime('+1 year'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_two':
                return [time(), strtotime('+2 years'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_cur_month':
                return [time(), strtotime('last day of this month 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_cur_year':
                return [time(), strtotime('last day of december this year 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_next_month':
                return [strtotime('first day of next month 00:00:00'), strtotime('last day of next month 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_next_year':
                return [strtotime('first day of january next year 00:00:00'), strtotime('last day of december next year 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'next_all': // 2106-02-07 07:28:15
                return [time(), min(4294967295, PHP_INT_MAX), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_7':
                return [strtotime('-7 days'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_14':
                return [strtotime('-14 days'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_30':
                return [strtotime('-1 month'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_90':
                return [strtotime('-3 months'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_180':
                return [strtotime('-6 months'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_365':
                return [strtotime('-1 year'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_two':
                return [strtotime('-2 years'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_cur_month':
                return [strtotime('first day of this month 00:00:00'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_cur_year':
                return [strtotime('first day of january this year 00:00:00'), time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_prev_month':
                return [strtotime('first day of last month 00:00:00'), strtotime('last day of last month 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_prev_year':
                return [strtotime('first day of january last year 00:00:00'), strtotime('last day of december last year 23:59:59'), $GLOBALS['TL_LANG']['MSC']['cal_empty']];

            case 'past_all': // 1970-01-01 00:00:00
                return [0, time(), $GLOBALS['TL_LANG']['MSC']['cal_empty']];
        }
    }
}
