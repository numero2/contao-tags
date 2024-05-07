<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\NewsBundle\ContaoNewsBundle;


if( class_exists(ContaoCalendarBundle::class) ) {

    $GLOBALS['TL_DCA']['tl_module']['palettes']['events_tag_cloud'] = '{title_legend},name,headline,type;{config_legend},cal_calendar,event_tags,cal_format,cal_featured,tags_match_all;{redirect_legend},jumpToTags,tags_select_multiple,use_get_parameter;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
    $GLOBALS['TL_DCA']['tl_module']['palettes']['eventlist_related_tags'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['eventlist'];
    $GLOBALS['TL_DCA']['tl_module']['palettes']['eventlist_tags'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['eventlist'];
}

if( class_exists(ContaoNewsBundle::class) ) {

    $GLOBALS['TL_DCA']['tl_module']['palettes']['news_tag_cloud'] = '{title_legend},name,headline,type;{config_legend},news_archives,news_tags,news_featured,tags_match_all;{redirect_legend},jumpToTags,tags_select_multiple,use_get_parameter;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
    $GLOBALS['TL_DCA']['tl_module']['palettes']['newslist_related_tags'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['newslist'];
    $GLOBALS['TL_DCA']['tl_module']['palettes']['newslist_tags'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['newslist'];
}


$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToTags'] = $GLOBALS['TL_DCA']['tl_module']['fields']['jumpTo'];
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToTags']['label'] = &$GLOBALS['TL_LANG']['tl_module']['jumpToTags'];
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToTags']['eval']['tl_class'] = 'clr';


$GLOBALS['TL_DCA']['tl_module']['fields']['ignoreTags'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['tl_class'=>'w50']
,   'sql'               => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['tags_select_multiple'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['tl_class'=>'w50']
,   'sql'               => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['use_get_parameter'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['tl_class'=>'w50']
,   'sql'               => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['tags_match_all'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['tl_class'=>'w50']
,   'sql'               => "char(1) NOT NULL default ''"
];

if( class_exists(ContaoCalendarBundle::class) ) {

    $GLOBALS['TL_DCA']['tl_module']['fields']['event_tags'] = [
        'exclude'           => true
    ,   'inputType'         => 'checkbox'
    ,   'eval'              => ['mandatory'=>true, 'multiple'=>true]
    ,   'sql'               => "blob NULL"
    ];
}

if( class_exists(ContaoNewsBundle::class) ) {

    $GLOBALS['TL_DCA']['tl_module']['fields']['news_tags'] = [
        'exclude'           => true
    ,   'inputType'         => 'checkbox'
    ,   'eval'              => ['mandatory'=>true, 'multiple'=>true]
    ,   'sql'               => "blob NULL"
    ];
}