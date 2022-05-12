<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


use numero2\TagsBundle\ModuleNewsListRelatedTags;
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
$GLOBALS['FE_MOD']['news']['newslist_related_tags'] = ModuleNewsListRelatedTags::class;
