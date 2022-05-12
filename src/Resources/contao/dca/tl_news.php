<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('tags_legend', 'teaser_legend', 'before')
    ->addField('tags', 'tags_legend', 'append')
    ->applyToPalette('default', 'tl_news');


$GLOBALS['TL_DCA']['tl_news']['fields']['tags'] = [
    'exclude'           => true
,   'inputType'         => 'select'
,   'foreignKey'        => 'tl_tags.tag'
,   'options_callback'  => ['numero2_tags.listener.data_container.tags', 'getTagOptions']
,   'save_callback'     => [['numero2_tags.listener.data_container.tags', 'saveTags']]
,   'eval'              => ['multiple'=>true, 'size'=>8, 'tl_class'=>'clr long tags', 'chosen'=>true]
,   'sql'               => "blob NULL"
,   'relation'          => ['type'=>'hasMany', 'load'=>'eager']
];
