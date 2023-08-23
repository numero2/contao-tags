<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


$GLOBALS['TL_DCA']['tl_module']['palettes']['news_tag_cloud'] = '{title_legend},name,headline,type;{config_legend},news_archives;{redirect_legend},jumpToTags,use_get_parameter;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['newslist_related_tags'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['newslist'];
$GLOBALS['TL_DCA']['tl_module']['palettes']['newslist_tags'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['newslist'];


$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToTags'] = $GLOBALS['TL_DCA']['tl_module']['fields']['jumpTo'];
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToTags']['label'] = &$GLOBALS['TL_LANG']['tl_module']['jumpToTags'];
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToTags']['eval']['tl_class'] = 'clr';


$GLOBALS['TL_DCA']['tl_module']['fields']['ignoreTags'] = [
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

$GLOBALS['TL_DCA']['tl_module']['fields']['news_tags'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['mandatory'=>true, 'multiple'=>true]
,   'sql'               => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['tags_match_all'] = [
    'exclude'           => true
,   'inputType'         => 'checkbox'
,   'eval'              => ['tl_class'=>'w50']
,   'sql'               => "char(1) NOT NULL default ''"
];