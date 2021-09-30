<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2021, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;


class ModuleListener {


    /**
     * Add jumpToTags to existing modules
     *
     * @param Contao\DataContainer $dc
     */
    public function modifyPalettes( DataContainer $dc ): void {

        $pm = PaletteManipulator::create()
            ->addField('jumpToTags', 'config_legend', 'append');

        foreach( ['newslist','newsreader'] as $palette ) {
            $pm->applyToPalette($palette, $dc->table);
        }
    }
}
