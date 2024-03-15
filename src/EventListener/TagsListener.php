<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\PageModel;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\Util\TagUtil;


class TagsListener {


    /**
     * Replace inserttags for tags
     *
     * @param string $insertTag
     * @param bool $useCache
     * @param string $cachedValue
     * @param array $flags
     *
     * @return string|bool
     *
     * @Hook("replaceInsertTags")
     */
    public function replaceInsertTags( string $insertTag, bool $useCache, string $cachedValue, array $flags ) {

        $tag = explode('::', $insertTag);

        if( $tag[0] === 'tag_link' ) {

            $page = PageModel::findByIdOrAlias($tag[1]);
            $tags = array_slice($tag, 2);

            if( !$page || empty($tags) ) {
                return '';
            }

            $tagNames = [];
            foreach( $tags as $t ) {

                $oTag = TagsModel::findByIdOrName($t);

                if( $oTag ) {
                    $tagNames[] = $oTag->tag;
                }
            }

            $tagNames = array_unique($tagNames);
            if( empty($tagNames) ) {
                return '';
            }

            $blnGetParameter = \in_array('get', $flags, true);
            $blnAbsolute = \in_array('absolute', $flags, true);

            return TagUtil::generateUrlWithTags($page, $tagNames, $blnGetParameter, $blnAbsolute);
        }

        return false;
    }
}
