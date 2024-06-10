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
use Contao\Frontend;
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
     * Get the translation data for this model
     */
    public function getTranslationData() {

        $aTranslation = Frontend::getMetaData($this->translation, $GLOBALS['TL_LANGUAGE']);

        return $aTranslation;
    }


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
     * @param int|null $start
     * @param int|null $end
     * @param bool|null $blnFeatured
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByCalendar( array $aCalendar, $start=null, $end=null, $blnFeatured=null, array $aOptions=[] ) {

        $aCalendar = array_map('\intval', $aCalendar);
        if( !count($aCalendar) ) {
            return null;
        }

        $publishedEvent = '';

        if( $start !== null ) {
            $publishedEvent .= " AND e.startTime>=".intval($start);
        }
        if( $end !== null ) {
            $publishedEvent .= " AND e.startTime<=".intval($end);
        }

        if( $blnFeatured === true ) {
            $publishedEvent .= " AND e.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedEvent .= " AND e.featured=''";
        }

        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedEvent .= " AND e.published='1' AND (e.start='' OR e.start<=$time) AND (e.stop='' OR e.stop>$time)";
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
     * @param int|null $start
     * @param int|null $end
     * @param bool|null $blnFeatured
     * @param array $aOptions
     *
     * @return int
     */
    public static function countByIdAndCalendar( $id, array $aCalendar, $start=null, $end=null, $blnFeatured=null, array $aOptions=[] ): int {

        $aCalendar = array_map('\intval', $aCalendar);
        if( !count($aCalendar) ) {
            return null;
        }

        $publishedEvent = '';

        if( $start !== null ) {
            $publishedEvent .= " AND e.startTime>=".intval($start);
        }
        if( $end !== null ) {
            $publishedEvent .= " AND e.startTime<=".intval($end);
        }

        if( $blnFeatured === true ) {
            $publishedEvent .= " AND e.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedEvent .= " AND e.featured=''";
        }

        $publishedEvent = '';
        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedEvent .= " AND e.published='1' AND (e.start='' OR e.start<=$time) AND (e.stop='' OR e.stop>$time)";
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
     * Find all used tags in the given calendar that are related to the given tags
     *
     * @param array $aTags
     * @param array $aCalendar
     * @param bool $blnMatchAll
     * @param int|null $start
     * @param int|null $end
     * @param bool|null $blnFeatured
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByTagsAndCalendar( array $aTags, array $aCalendar, bool $blnMatchAll, $start=null, $end=null, $blnFeatured=null, array $aOptions=[] ) {

        $aTags = array_map('\intval', $aTags);
        $aCalendar = array_map('\intval', $aCalendar);
        if( !count($aTags) || !count($aCalendar) ) {
            return null;
        }

        $publishedEvent = '';

        if( $start !== null ) {
            $publishedEvent .= " AND e.startTime>=".intval($start);
        }
        if( $end !== null ) {
            $publishedEvent .= " AND e.startTime<=".intval($end);
        }

        if( $blnFeatured === true ) {
            $publishedEvent .= " AND e.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedEvent .= " AND e.featured=''";
        }

        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedEvent .= " AND e.published='1' AND (e.start='' OR e.start<=$time) AND (e.stop='' OR e.stop>$time)";
        }

        $objResult = null;
        if( $blnMatchAll ) {

            $objResult = Database::getInstance()->prepare("
                SELECT
                    tag.*
                FROM (
                    SELECT
                        s.id, COUNT(1) AS count
                    FROM (
                        SELECT DISTINCT
                            t.*, rTags.tag_id
                        FROM ".CalendarModel::getTable()." AS c
                            JOIN ".CalendarEventsModel::getTable()." AS e ON (e.pid = c.id)
                            JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = e.id AND rTags.ptable = '".CalendarEventsModel::getTable()."' AND rTags.field = 'tags')
                            JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = e.id AND r.ptable = '".CalendarEventsModel::getTable()."' AND r.field = 'tags')
                            JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                        WHERE
                            rTags.tag_id in (".implode(',', $aTags).") AND c.id in (".implode(',', $aCalendar).")".$publishedEvent."
                        GROUP BY t.id, rTags.tag_id
                    ) AS s
                    GROUP BY s.id
                    HAVING count>=?
                ) AS tagCount
                JOIN ".static::$strTable." AS tag ON (tag.id = tagCount.id)
                ORDER BY tag.tag ASC
                ")->execute(count($aTags));

        } else {

            $objResult = Database::getInstance()->prepare("
                SELECT DISTINCT
                    t.*
                FROM ".CalendarModel::getTable()." AS c
                    JOIN ".CalendarEventsModel::getTable()." AS e ON (e.pid = c.id)
                    JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = e.id AND rTags.ptable = '".CalendarEventsModel::getTable()."' AND rTags.field = 'tags')
                    JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = e.id AND r.ptable = '".CalendarEventsModel::getTable()."' AND r.field = 'tags')
                    JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                WHERE
                    rTags.tag_id in (".implode(',', $aTags).") AND c.id in (".implode(',', $aCalendar).")".$publishedEvent."
                ORDER BY t.tag ASC
            ")->execute();
        }

        return static::createCollectionFromDbResult($objResult, static::$strTable);
    }


    /**
     * Count how many times the given tag was used in the given calendar that are related to the given tags
     *
     * @param int $id
     * @param array $aTags
     * @param array $aCalendar
     * @param bool $blnMatchAll
     * @param int|null $start
     * @param int|null $end
     * @param bool|null $blnFeatured
     * @param array $aOptions
     *
     * @return int
     */
    public static function countByIdAndTagsAndCalendar( $id, array $aTags, array $aCalendar, bool $blnMatchAll, $start=null, $end=null, $blnFeatured=null, array $aOptions=[] ): int {

        $aTags = array_map('\intval', $aTags);
        $aCalendar = array_map('\intval', $aCalendar);
        if( !count($aTags) || !count($aCalendar) ) {
            return null;
        }

        $publishedEvent = '';

        if( $start !== null ) {
            $publishedEvent .= " AND e.startTime>=".intval($start);
        }
        if( $end !== null ) {
            $publishedEvent .= " AND e.startTime<=".intval($end);
        }

        if( $blnFeatured === true ) {
            $publishedEvent .= " AND e.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedEvent .= " AND e.featured=''";
        }

        $publishedEvent = '';
        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedEvent .= " AND e.published='1' AND (e.start='' OR e.start<=$time) AND (e.stop='' OR e.stop>$time)";
        }

        $objResult = null;
        if( $blnMatchAll ) {

            $objResult = Database::getInstance()->prepare("
                SELECT
                    tag.*
                FROM (
                    SELECT
                        s.id, COUNT(1) AS count
                    FROM (
                        SELECT DISTINCT
                            t.*, rTags.tag_id
                        FROM ".CalendarModel::getTable()." AS c
                            JOIN ".CalendarEventsModel::getTable()." AS e ON (e.pid = c.id)
                            JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = e.id AND rTags.ptable = '".CalendarEventsModel::getTable()."' AND rTags.field = 'tags')
                            JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = e.id AND r.ptable = '".CalendarEventsModel::getTable()."' AND r.field = 'tags')
                            JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                        WHERE
                            rTags.tag_id in (".implode(',', $aTags).") AND c.id in (".implode(',', $aCalendar).") AND t.id=? ".$publishedEvent."
                        GROUP BY t.id, rTags.tag_id
                    ) AS s
                    GROUP BY s.id
                    HAVING count>=?
                ) AS tagCount
                JOIN ".static::$strTable." AS tag ON (tag.id = tagCount.id)
                ORDER BY tag.tag ASC
                ")->execute($id, count($aTags));

        } else {

            $objResult = Database::getInstance()->prepare("
                SELECT
                    COUNT(*) AS count
                FROM ".CalendarModel::getTable()." AS c
                    JOIN ".CalendarEventsModel::getTable()." AS e ON (e.pid = c.id)
                    JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = e.id AND rTags.ptable = '".CalendarEventsModel::getTable()."' AND rTags.field = 'tags')
                    JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = e.id AND r.ptable = '".CalendarEventsModel::getTable()."' AND r.field = 'tags')
                    JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                WHERE
                    rTags.tag_id in (".implode(',', $aTags).") AND c.id in (".implode(',', $aCalendar).") AND t.id=? ".$publishedEvent."
            ")->execute($id);
        }

        return (int)$objResult->count;
    }


    /**
     * Find all used tags in the given archives
     *
     * @param array $aArchives
     * @param boolean|null $blnFeature
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByNewsArchives( array $aArchives, $blnFeatured=null, array $aOptions=[] ) {

        $aArchives = array_map('\intval', $aArchives);
        if( !count($aArchives) ) {
            return null;
        }

        $publishedNews = '';

        if( $blnFeatured === true ) {
            $publishedNews .= " AND n.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedNews .= " AND n.featured=''";
        }

        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedNews .= " AND n.published='1' AND (n.start='' OR n.start<=$time) AND (n.stop='' OR n.stop>$time)";
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
     * @param boolean|null $blnFeature
     * @param array $aOptions
     *
     * @return int
     */
    public static function countByIdAndNewsArchives( $id, array $aArchives, $blnFeatured=null, array $aOptions=[] ): int {

        $aArchives = array_map('\intval', $aArchives);
        if( !count($aArchives) ) {
            return null;
        }

        $publishedNews = '';

        if( $blnFeatured === true ) {
            $publishedNews .= " AND n.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedNews .= " AND n.featured=''";
        }

        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedNews .= " AND n.published='1' AND (n.start='' OR n.start<=$time) AND (n.stop='' OR n.stop>$time)";
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
     * Find all used tags in the given archives nad are related to the given tags
     *
     * @param array $aTags
     * @param array $aArchives
     * @param boolean $blnMatchAll
     * @param boolean|null $blnFeature
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByTagsAndNewsArchives( array $aTags, array $aArchives, bool $blnMatchAll, $blnFeatured=null, array $aOptions=[] ) {

        $aTags = array_map('\intval', $aTags);
        $aArchives = array_map('\intval', $aArchives);
        if( !count($aTags) || !count($aArchives) ) {
            return null;
        }

        $publishedNews = '';

        if( $blnFeatured === true ) {
            $publishedNews .= " AND n.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedNews .= " AND n.featured=''";
        }

        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedNews .= " AND n.published='1' AND (n.start='' OR n.start<=$time) AND (n.stop='' OR n.stop>$time)";
        }

        $objResult = null;
        if( $blnMatchAll ) {

            $objResult = Database::getInstance()->prepare("
                SELECT
                    tag.*
                FROM (
                    SELECT
                        s.id, COUNT(1) AS count
                    FROM (
                        SELECT DISTINCT
                            t.*, rTags.tag_id
                        FROM ".NewsArchiveModel::getTable()." AS a
                            JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                            JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = n.id AND rTags.ptable = '".NewsModel::getTable()."' AND rTags.field = 'tags')
                            JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                            JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                        WHERE
                            rTags.tag_id in (".implode(',', $aTags).") AND a.id in (".implode(',', $aArchives).")".$publishedNews."
                        GROUP BY t.id, rTags.tag_id
                    ) AS s
                    GROUP BY s.id
                    HAVING count>=?
                ) AS tagCount
                JOIN ".static::$strTable." AS tag ON (tag.id = tagCount.id)
                ORDER BY tag.tag ASC
                ")->execute(count($aTags));

        } else {

            $objResult = Database::getInstance()->prepare("
                SELECT DISTINCT
                    t.*
                FROM ".NewsArchiveModel::getTable()." AS a
                    JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                    JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = n.id AND rTags.ptable = '".NewsModel::getTable()."' AND rTags.field = 'tags')
                    JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                    JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                WHERE
                    rTags.tag_id in (".implode(',', $aTags).") AND a.id in (".implode(',', $aArchives).")".$publishedNews."
                ORDER BY t.tag ASC
            ")->execute();
        }

        return static::createCollectionFromDbResult($objResult, static::$strTable);
    }


    /**
     * Count all used tags in the given archives nad are related to the given tags
     *
     * @param string $id
     * @param array $aTags
     * @param array $aArchives
     * @param boolean $blnMatchAll
     * @param boolean|null $blnFeature
     * @param array $aOptions
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function countByIdAndTagsAndNewsArchives( $id, array $aTags, array $aArchives, bool $blnMatchAll, $blnFeatured=null, array $aOptions=[] ) {

        $aTags = array_map('\intval', $aTags);
        $aArchives = array_map('\intval', $aArchives);
        if( !count($aTags) || !count($aArchives) ) {
            return null;
        }

        $publishedNews = '';

        if( $blnFeatured === true ) {
            $publishedNews .= " AND n.featured='1'";
        } else if( $blnFeatured === false ) {
            $publishedNews .= " AND n.featured=''";
        }

        if( !static::isPreviewMode($aOptions) ) {
            $time = Date::floorToMinute();
            $publishedNews .= " AND n.published='1' AND (n.start='' OR n.start<=$time) AND (n.stop='' OR n.stop>$time)";
        }

        $objResult = null;
        if( $blnMatchAll ) {

            $objResult = Database::getInstance()->prepare("
                SELECT
                    tag.*
                FROM (
                    SELECT
                        s.id, COUNT(1) AS count
                    FROM (
                        SELECT DISTINCT
                            t.*, rTags.tag_id
                        FROM ".NewsArchiveModel::getTable()." AS a
                            JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                            JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = n.id AND rTags.ptable = '".NewsModel::getTable()."' AND rTags.field = 'tags')
                            JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                            JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                        WHERE
                            rTags.tag_id in (".implode(',', $aTags).") AND a.id in (".implode(',', $aArchives).") AND t.id=?".$publishedNews."
                        GROUP BY t.id, rTags.tag_id
                    ) AS s
                    GROUP BY s.id
                    HAVING count>=?
                ) AS tagCount
                JOIN ".static::$strTable." AS tag ON (tag.id = tagCount.id)
                ")->execute($id, count($aTags));

        } else {

            $objResult = Database::getInstance()->prepare("
                SELECT
                    COUNT(*) as count
                FROM ".NewsArchiveModel::getTable()." AS a
                    JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                    JOIN ".TagsRelModel::getTable()." AS rTags ON (rTags.pid = n.id AND rTags.ptable = '".NewsModel::getTable()."' AND rTags.field = 'tags')
                    JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                    JOIN ".static::$strTable." AS t ON (t.id = r.tag_id)
                WHERE
                    rTags.tag_id in (".implode(',', $aTags).") AND a.id in (".implode(',', $aArchives).") AND t.id=?".$publishedNews."
                ")->execute($id);
        }

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
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=?)
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
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=?)
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
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=?)
            WHERE r.pid=?
            ORDER BY t.tag ASC
        ")->execute($field, $table, $id);

        if( $objResult && $objResult->count() ) {
            return $objResult->count;
        }

        return 0;
    }
}