<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */

use Contao\DC_Table;


$GLOBALS['TL_DCA']['tl_tags_rel'] = [

    'config' => [
        'dataContainer' => DC_Table::class
    ,   'sql' => [
            'keys' => [
                'tag_id' => 'index'
            ,   'pid' => 'index'
            ,   'ptable,field' => 'index'
            ]
        ]
    ]
,   'fields' => [
        'tag_id' => [
            'sql'   => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'pid' => [
            'sql'   => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'ptable' => [
            'sql'   => "varchar(64) NOT NULL default ''"
        ]
    ,   'field' => [
            'sql'   => "varchar(64) NOT NULL default ''"
        ]
    ]
];
