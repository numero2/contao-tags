<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Event;


final class TagsEvents {

    /**
     * The contao.tags_get_list is triggered whenever we need a list of tags.
     *
     * @see numero2\TagsBundle\Event\TagsGetListEvent
     */
    public const TAGS_GET_LIST = 'contao.tags_get_list';
}
