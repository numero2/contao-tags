<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\NewsBundle\ContaoNewsBundle;
use numero2\TagsBundle\ModuleEventlistRelatedTags;
use numero2\TagsBundle\ModuleEventlistTags;
use numero2\TagsBundle\ModuleNewsListRelatedTags;
use numero2\TagsBundle\ModuleNewsListTags;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;


/**
 * MODELS
 */
$GLOBALS['TL_MODELS'][TagsModel::getTable()] = TagsModel::class;
$GLOBALS['TL_MODELS'][TagsRelModel::getTable()] = TagsRelModel::class;


/**
 * FRONTEND MODULES
 */
if( class_exists(ContaoCalendarBundle::class) ) {
    $GLOBALS['FE_MOD']['events']['eventlist_related_tags'] = ModuleEventlistRelatedTags::class;
    $GLOBALS['FE_MOD']['events']['eventlist_tags'] = ModuleEventlistTags::class;
}
if( class_exists(ContaoNewsBundle::class) ) {
    $GLOBALS['FE_MOD']['news']['newslist_related_tags'] = ModuleNewsListRelatedTags::class;
    $GLOBALS['FE_MOD']['news']['newslist_tags'] = ModuleNewsListTags::class;
}


/**
 * BACKEND MODULES
 */
$GLOBALS['BE_MOD']['system']['contao_tags'] = [
    'tables' => [TagsModel::getTable()]
];


/**
 * PURGE
 */
$GLOBALS['TL_PURGE']['custom']['purgeTags'] = [
    'callback' => ['numero2_tags.automator.purge_tags', '__invoke']
];


/**
 * PERMISSIONS
 */
$GLOBALS['TL_PERMISSIONS'][] = 'tags_disable_add_new';