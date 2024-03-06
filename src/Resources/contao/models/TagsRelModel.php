<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle;

use Contao\Database;
use Contao\Date;
use Contao\Model;
use Contao\NewsModel;
use Contao\System;

class TagsRelModel extends Model {

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_tags_rel';

    /**
     * Find all related news for the given news id
     *
     * @param integer $id
     * @param array $aColumns
     * @param array $aValues
     * @param array $aOptions
     *
     * @return Collection|NewsModel|null
     */
    public static function findRelatedNewsByID( $id, array $aColumns=[], array $aValues=[], array $aOptions=[] ) {

        $newsTable = NewsModel::getTable();

        $objResult = NULL;
        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT tl_news.*
            FROM ".self::$strTable." AS rel
            LEFT JOIN ".$newsTable." ON (tl_news.id=rel.pid AND rel.ptable='".$newsTable."' AND rel.field='tags')
            WHERE rel.tag_id IN (
                SELECT tag_id
                FROM ".self::$strTable."
                WHERE pid=? AND ptable='".$newsTable."' AND field='tags'
            ) AND rel.ptable='".$newsTable."' AND rel.field='tags' AND tl_news.id!=?
            ".(!empty($aColumns)?' AND '.implode(' AND ', $aColumns):'')."
            ".(!empty($aOptions['order'])?'ORDER BY '.$aOptions['order']:'')."
            ".(!empty($aOptions['limit'])?'LIMIT '.$aOptions['limit']:'')."
            ".(!empty($aOptions['offset'])?'OFFSET '.$aOptions['offset']:'')."
        ")->execute($id, $id, ...$aValues);

        return self::createCollectionFromDbResult($objResult, $newsTable);
    }


    /**
     * Find published news items by the given news id
     *
     * @param integer $id
     * @param array $aPids
     * @param boolean $blnFeatured
     * @param integer $intLimit
     * @param integer $intOffset
     * @param array $aOptions
     *
     * @return Collection|NewsModel|null
     */
    public static function findPublishedRelatedNewsByID( $id, $aPids, $blnFeatured=null, $intLimit=0, $intOffset=0, array $aOptions=[] ) {

        if( empty($aPids) || !is_array($aPids) ) {
            return null;
        }

        $t = NewsModel::getTable();
        $aColumns = ["$t.pid IN (" . implode(',', array_map('\intval', $aPids)) . ")"];

        if( $blnFeatured === true ) {
            $aColumns[] = "$t.featured='1'";
        } else if( $blnFeatured === false ) {
            $aColumns[] = "$t.featured=''";
        }

        // Never return unpublished elements in the back end, so they don't end up in the RSS feed
        $securityChecker = System::getContainer()->get('security.authorization_checker');
        if( (!$securityChecker->isGranted('ROLE_ADMIN') || !$securityChecker->isGranted('ROLE_USER')) || System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest()) ) {
            $time = Date::floorToMinute();
            $aColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        if( !isset($aOptions['order']) ) {
            $aOptions['order']  = "$t.date DESC";
        }

        $aOptions['limit']  = $intLimit;
        $aOptions['offset'] = $intOffset;

        return self::findRelatedNewsByID($id, $aColumns, [], $aOptions);
    }


    /**
     * Find all news with the given tags
     *
     * @param array $ids
     * @param boolean $blnMatchAll
     * @param array $aColumns
     * @param array $aValues
     * @param array $aOptions
     *
     * @return Collection|NewsModel|null
     */
    public static function findNewsByTags( array $ids, $blnMatchAll=null, array $aColumns=[], array $aValues=[], array $aOptions=[] ) {

        $tRel = self::getTable();
        $tNews = NewsModel::getTable();

        $ids = array_filter(array_map('\intval', $ids));
        $strIds = implode(',', $ids);
        $countIds = count($ids);

        if( !strlen($strIds) ) {
            return null;
        }

        $objResult = NULL;
        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT $tNews.*
            FROM $tRel AS rel
            LEFT JOIN $tNews ON ($tNews.id=rel.pid AND rel.ptable='$tNews' AND rel.field='tags')
            ".($blnMatchAll?"LEFT JOIN (SELECT pid, COUNT(1) AS count FROM tl_tags_rel AS subRel WHERE subRel.tag_id IN ($strIds) AND subRel.ptable='$tNews' AND subRel.field='tags' GROUP BY subRel.pid) AS tagCount ON tagCount.pid=$tNews.id":"")."
            WHERE rel.tag_id IN ($strIds) AND ".($blnMatchAll?"tagCount.count=$countIds AND ":"")."rel.ptable='$tNews' AND rel.field='tags'
            ".(!empty($aColumns)?' AND '.implode(' AND ', $aColumns):'')."
            ".(!empty($aOptions['order'])?'ORDER BY '.$aOptions['order']:'')."
            ".(!empty($aOptions['limit'])?'LIMIT '.$aOptions['limit']:'')."
            ".(!empty($aOptions['offset'])?'OFFSET '.$aOptions['offset']:'')."
        ")->execute(...$aValues);

        return self::createCollectionFromDbResult($objResult, $tNews);
    }


    /**
     * Find published news items by the given tags and archives
     *
     * @param array $tagsIds
     * @param array $aPids
     * @param boolean $blnFeatured
     * @param boolean $blnMatchAll
     * @param integer $intLimit
     * @param integer $intOffset
     * @param array $aOptions
     *
     * @return Collection|NewsModel|null
     */
    public static function findPublishedNewsByTags( $tagsIds, $aPids, $blnFeatured=null, $blnMatchAll=null, $intLimit=0, $intOffset=0, array $aOptions=[] ) {

        if( empty($tagsIds) || !is_array($tagsIds) ) {
            return null;
        }
        if( empty($aPids) || !is_array($aPids) ) {
            return null;
        }

        $t = NewsModel::getTable();
        $aColumns = ["$t.pid IN (" . implode(',', array_map('\intval', $aPids)) . ")"];

        if( $blnFeatured === true ) {
            $aColumns[] = "$t.featured='1'";
        } else if( $blnFeatured === false ) {
            $aColumns[] = "$t.featured=''";
        }


        // Never return unpublished elements in the back end, so they don't end up in the RSS feed
        $securityChecker = System::getContainer()->get('security.authorization_checker');
        if( (!$securityChecker->isGranted('ROLE_ADMIN') || !$securityChecker->isGranted('ROLE_USER')) || System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest()) ) {
            $time = Date::floorToMinute();
            $aColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        if( !isset($aOptions['order']) ) {
            $aOptions['order']  = "$t.date DESC";
        }

        $aOptions['limit']  = $intLimit;
        $aOptions['offset'] = $intOffset;

        return self::findNewsByTags($tagsIds, $blnMatchAll, $aColumns, [], $aOptions);
    }
}
