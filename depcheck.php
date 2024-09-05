<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2021, numero2 - Agentur für digitales Marketing GbR
 */


use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;


return (new Configuration())
    ->ignoreErrorsOnPackage('contao/manager-plugin', [ErrorType::DEV_DEPENDENCY_IN_PROD])

    // ignore classes these will be checked during runtime
    // contao/calendar-bundle
    ->ignoreUnknownClasses([
        'Contao\CalendarBundle\ContaoCalendarBundle',
        'Contao\CalendarEventsModel',
        'Contao\CalendarModel',
        'Contao\ModuleEventlist',
    ])

    // contao/news-bundle
    ->ignoreUnknownClasses([
        'Contao\ModuleNews',
        'Contao\ModuleNewsList',
        'Contao\NewsArchiveModel',
        'Contao\NewsBundle\ContaoNewsBundle',
        'Contao\NewsModel',
    ])
;