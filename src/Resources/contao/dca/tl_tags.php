<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2021, numero2 - Agentur für digitales Marketing GbR
 */


$GLOBALS['TL_DCA']['tl_tags'] = [

    'config' => [
        'dataContainer' => 'Table'
    ,   'ctable'        => ['tl_tags_rel']
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'fields' => [

        'id' => [
            'sql'   => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tag' => [
            'sql'   => "varchar(255) NOT NULL default ''"
        ]
    ]
];