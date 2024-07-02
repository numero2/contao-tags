<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


PaletteManipulator::create()
    ->addField('tags', 'meta', 'before')
    ->applyToPalette('default', 'tl_files');


$GLOBALS['TL_DCA']['tl_files']['fields']['tags'] = [
    'exclude'           => true
,   'filter'            => true
,   'inputType'         => 'select'
,   'foreignKey'        => 'tl_tags.tag'
,   'options_callback'  => ['numero2_tags.listener.data_container.tags', 'getTagOptions']
,   'load_callback'     => [['numero2_tags.listener.data_container.tags', 'loadTags']]
,   'save_callback'     => [['numero2_tags.listener.data_container.tags', 'saveTags']]
,   'eval'              => ['multiple'=>true, 'size'=>8, 'tl_class'=>'clr long tags', 'chosen'=>true]
,   'sql'               => "blob NULL"
,   'relation'          => ['type'=>'hasMany', 'load'=>'eager']
];