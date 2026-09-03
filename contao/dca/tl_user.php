<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Add palettes to tl_user
 */
PaletteManipulator::create()
    ->addLegend('tags_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['tags_disable_add_new'], 'tags_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;

/**
 * Add fields to tl_user
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['tags_disable_add_new'] = [
    'exclude'       => true
,   'inputType'     => 'checkbox'
,   'eval'          => ['tl_class'=>'w50 m12']
,   'sql'           => "blob NULL"
];