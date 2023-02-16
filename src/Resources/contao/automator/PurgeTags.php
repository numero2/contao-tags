<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 20232, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle;

use Contao\Automator;
use Contao\Database;
use Contao\System;


class PurgeTags extends Automator {


    public function execute(): void {

        $db = Database::getInstance();
        
        $sql ='DELETE tl_tags FROM tl_tags 
            LEFT JOIN tl_tags_rel ON tl_tags.id = tl_tags_rel.tag_id
            WHERE tl_tags_rel.tag_id IS NULL';
        $result = $db->execute($sql);

        System::getContainer()->get('monolog.logger.contao.cron')->info('Purged unused tags');
    }
}

