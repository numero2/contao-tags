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

use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


class PurgeTags {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private Connection $connection;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private LoggerInterface $logger;


    public function __construct( Connection $connection, LoggerInterface $logger ) {

        $this->connection = $connection;
        $this->logger = $logger;
    }


    public function __invoke(): void {

        $tTag = TagsModel::getTable();
        $tRel = TagsRelModel::getTable();

        $schemaManager = $this->connection->createSchemaManager();

        // get used table, field from relations
        $rows = $this->connection->executeQuery(
            "SELECT DISTINCT ptable, field FROM $tRel"
        )->fetchAllAssociative();

        if( !empty($rows) ) {

            $entries = [];

            foreach( $rows as $row ) {
                $entries[$row['ptable']][] = $row['field'];
            }

            foreach( $entries as $table => $fields ) {

                if( empty($table) || !$schemaManager->tablesExist([$table]) ) {

                    // remove tag relation where ptable not exists
                    $this->connection->executeStatement(
                        "DELETE FROM $tRel WHERE ptable=:table"
                    ,   ['table'=>$table]
                    );
                    continue;
                }

                foreach( $fields as $field ) {

                    $columns = $schemaManager->listTableColumns($table);
                    $columns = array_map(function( $column ) {
                        return $column->getName();
                    }, $columns);

                    if( empty($field) || !in_array($field, $columns) ) {

                        // remove tag relation where field in table not exists
                        $this->connection->executeStatement(
                            "DELETE FROM $tRel WHERE ptable=:table AND field=:field"
                        ,   ['table'=>$table, 'field'=>$field]
                        );
                        continue;
                    }

                    // remove tag relation where pid in table not exists
                    $this->connection->executeStatement(
                        "DELETE FROM $tRel WHERE pid NOT IN (SELECT id from $table) AND ptable=:table"
                    ,   ['table'=>$table]
                    );
                }
            }
        }

        // remove tag relation where tag is missing
        $this->connection->executeStatement(
            "DELETE rel FROM $tRel AS rel
            LEFT JOIN $tTag AS tag ON (tag.id=rel.tag_id)
            WHERE ISNULL(tag.id)"
        );

        // remove tag if none relation exists
        $this->connection->executeStatement(
            "DELETE tag FROM $tTag AS tag
            LEFT JOIN $tRel AS rel ON tag.id=rel.tag_id
            WHERE ISNULL(rel.tag_id)"
        );

        $this->logger->log(LogLevel::INFO, 'Purged unused tags', ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]);
    }
}

