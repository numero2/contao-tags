<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle;

use Contao\Database;
use Contao\Date;
use Contao\Model;
use Contao\NewsModel;


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
        $aColumns = ["$t.pid IN(" . implode(',', array_map('\intval', $aPids)) . ")"];

        if( $blnFeatured === true ) {
            $aColumns[] = "$t.featured='1'";
        } else if( $blnFeatured === false ) {
            $aColumns[] = "$t.featured=''";
        }

        // Never return unpublished elements in the back end, so they don't end up in the RSS feed
        if( !BE_USER_LOGGED_IN || TL_MODE == 'BE' ) {
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
}
