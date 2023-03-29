<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */

use Contao\DataContainer;
use Contao\DC_Table;


$GLOBALS['TL_DCA']['tl_tags'] = [

    'config' => [
        'dataContainer' => DC_Table::class
    ,   'closed' => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'          => DataContainer::MODE_SORTABLE
        ,   'fields'        => ['tag']
        ,   'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC
        ,   'panelLayout'   => 'sort,search,limit'
        ]
    ,   'label' => [
            'fields'        => ['tag']
        ,   'showColumns'   => true
        ]
    ,   'operations' => [
            'edit' => [
                'href'      => 'act=edit'
            ,   'icon'      => 'edit.svg'
            ]
        ]
    ]
,   'palettes' => [
        'default' => '{title_legend},tag'
    ]
,   'fields' => [

        'id' => [
            'sql'       => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tag' => [
            'inputType' => 'text'
        ,   'sorting'   => true
        ,   'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC
        ,   'search'    => true
        ,   'eval'      => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'       => "varchar(255) NOT NULL default ''"
        ]
    ]
];