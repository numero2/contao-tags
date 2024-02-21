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

use Contao\Database;
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
     * Find all used tags in the given archives
     *
     * @param array $aArchives
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByNewsArchives( array $aArchives ) {

        $aArchives = (array)$aArchives;

        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT
                t.*
            FROM ".NewsArchiveModel::getTable()." AS a
                JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                JOIN ".self::getTable()." AS t ON (t.id = r.tag_id)
            WHERE
                a.id in (".implode(',', $aArchives).")
            ORDER BY t.tag ASC
        ")->execute();


        return static::createCollectionFromDbResult($objResult, self::$strTable);
    }


    /**
     * Count how many times the given tag was used
     *
     * @param int $id
     * @param array $aArchives
     *
     * @return int
     */
    public static function countByIdAndNewsArchives( $id, array $aArchives ): int {

        $aArchives = (array)$aArchives;

        $objResult = Database::getInstance()->prepare("
            SELECT
                COUNT(*) AS count
            FROM ".NewsArchiveModel::getTable()." AS a
                JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                JOIN ".self::getTable()." AS t ON (t.id = r.tag_id)
            WHERE
                a.id in (".implode(',', $aArchives).") AND t.id = ?
        ")->execute( $id );

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
            FROM ".self::getTable()." AS t
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=? )
            ORDER BY t.tag ASC
        ")->execute($field, $table);

        return static::createCollectionFromDbResult($objResult, self::$strTable);
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
            FROM ".self::getTable()." AS t
                JOIN ".TagsRelModel::getTable()." AS r ON (r.tag_id=t.id AND r.field=? AND r.ptable=? )
            WHERE r.pid=?
            ORDER BY t.tag ASC
        ")->execute($field, $table, $id);

        return static::createCollectionFromDbResult($objResult, self::$strTable);
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
            FROM ".self::getTable()." AS t
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