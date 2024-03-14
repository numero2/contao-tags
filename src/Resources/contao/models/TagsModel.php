<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Database;
use Contao\Date;
use Contao\Model;
use Contao\NewsArchiveModel;
use Contao\NewsModel;


class TagsModel extends Model {


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_tags';


    /**
     * Find tags by their ID or name
     *
     * @param mixed $varId
     * @param array $arrOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByIdOrName( $varId, array $arrOptions=[] ) {

        $t = static::$strTable;
        $arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? ["$t.tag=?"] : ["$t.id=?"];

        return static::findBy($arrColumns, $varId, $arrOptions);
    }


    /**
     * Find all used tags in the given calendar
     *
     * @param array $aCalendar
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByCalendar( array $aCalendar, array $aOptions=[] ) {

        $aCalendar = array_map('\intval', $aCalendar);
        if( !count($aCalendar) ) {
            return null;
        }

        $publishedEvent = '';
        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedEvent = " AND e.published='1' AND (e.start='' OR e.start<=$time) AND (e.stop='' OR e.stop>$time)";
        }

        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT
                t.*
            FROM ".CalendarModel::getTable()." AS c
                JOIN ".CalendarEventsModel::getTable()." AS e ON (e.pid = c.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = e.id AND r.ptable = '".CalendarEventsModel::getTable()."' AND r.field = 'tags')
                JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
            WHERE
                c.id in (".implode(',', $aCalendar).")".$publishedEvent."
            ORDER BY t.tag ASC
        ")->execute();

        return static::createCollectionFromDbResult($objResult, static::$strTable);
    }


    /**
     * Count how many times the given tag was used
     *
     * @param int $id
     * @param array $aCalendar
     * @param array $aOptions
     *
     * @return int
     */
    public static function countByIdAndCalendar( $id, array $aCalendar, array $aOptions=[] ): int {

        $aCalendar = array_map('\intval', $aCalendar);
        if( !count($aCalendar) ) {
            return null;
        }

        $publishedEvent = '';
        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedEvent = " AND e.published='1' AND (e.start='' OR e.start<=$time) AND (e.stop='' OR e.stop>$time)";
        }

        $objResult = Database::getInstance()->prepare("
            SELECT
                COUNT(*) AS count
            FROM ".CalendarModel::getTable()." AS c
                JOIN ".CalendarEventsModel::getTable()." AS e ON (e.pid = c.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = e.id AND r.ptable = '".CalendarEventsModel::getTable()."' AND r.field = 'tags')
                JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
            WHERE
                c.id in (".implode(',', $aCalendar).") AND t.id=? ".$publishedEvent."
        ")->execute($id);

        return (int)$objResult->count;
    }


    /**
     * Find all used tags in the given archives
     *
     * @param array $aArchives
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByNewsArchives( array $aArchives, array $aOptions=[] ) {

        $aArchives = array_map('\intval', $aArchives);
        if( !count($aArchives) ) {
            return null;
        }

        $publishedNews = '';
        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedNews = " AND n.published='1' AND (n.start='' OR n.start<=$time) AND (n.stop='' OR n.stop>$time)";
        }

        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT
                t.*
            FROM ".NewsArchiveModel::getTable()." AS a
                JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
            WHERE
                a.id in (".implode(',', $aArchives).")".$publishedNews."
            ORDER BY t.tag ASC
        ")->execute();

        return static::createCollectionFromDbResult($objResult, static::$strTable);
    }


    /**
     * Count how many times the given tag was used
     *
     * @param int $id
     * @param array $aArchives
     * @param array $aOptions
     *
     * @return int
     */
    public static function countByIdAndNewsArchives( $id, array $aArchives, array $aOptions=[] ): int {

        $aArchives = array_map('\intval', $aArchives);
        if( !count($aArchives) ) {
            return null;
        }

        $publishedNews = '';
        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedNews = " AND n.published='1' AND (n.start='' OR n.start<=$time) AND (n.stop='' OR n.stop>$time)";
        }

        $objResult = Database::getInstance()->prepare("
            SELECT
                COUNT(*) AS count
            FROM ".NewsArchiveModel::getTable()." AS a
                JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
            WHERE
                a.id in (".implode(',', $aArchives).") AND t.id=? ".$publishedNews."
        ")->execute($id);

        return (int)$objResult->count;
    }


    /**
     * Find all used tags for the given field and table
     *
     * @param string $field
     * @param string $table
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findAllByFieldAndTable( string $field, string $table ) {

        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT
                t.*
            FROM ".static::$strTable." AS t
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=? )
            ORDER BY t.tag ASC
        ")->execute($field, $table);

        return static::createCollectionFromDbResult($objResult, static::$strTable);
    }


    /**
     * Find used tags for the given id, field and table
     *
     * @param int $id
     * @param string $field
     * @param string $table
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByIdForFieldAndTable( $id, string $field, string $table ) {

        $objResult = Database::getInstance()->prepare("
            SELECT t.*
            FROM ".static::$strTable." AS t
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=? )
            WHERE r.pid=?
            ORDER BY t.tag ASC
        ")->execute($field, $table, $id);

        return static::createCollectionFromDbResult($objResult, static::$strTable);
    }


    /**
     * Count used tags for the given id, field and table
     *
     * @param int $id
     * @param string $field
     * @param string $table
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function countByIdForFieldAndTable( $id, string $field, string $table ): int {

        $objResult = Database::getInstance()->prepare("
            SELECT count(1) as count
            FROM ".static::$strTable." AS t
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=? )
            WHERE r.pid=?
            ORDER BY t.tag ASC
        ")->execute($field, $table, $id);

        if( $objResult && $objResult->count() ) {
            return $objResult->count;
        }

        return 0;
    }
}