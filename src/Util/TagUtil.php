<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Util;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use numero2\TagsBundle\TagsModel;


class TagUtil {


    /**
     * Get tags from url
     *
     * @return array
     */
    public static function getTagsFromUrl(): array {

        $tag = Input::get('tag');

        if( $tag === null ) {
            return [];
        }

        $tags = json_decode(base64_decode(StringUtil::revertInputEncoding($tag))) ?? [];

        if( !$tags ) {
            $tags = [$tag];
        }

        return $tags;
    }


    /**
     * Get the ids of the tags given in the url
     *
     * @param bool $blnThrowOnUnknown
     *
     * @return array
     *
     * @throws Contao\CoreBundle\Exception\PageNotFoundException
     */
    public static function getTagIdsFromUrl( bool $blnThrowOnUnknown=true ): array {

        $tags = self::getTagsFromUrl();

        // json_decode may return anything, only accept a flat list of strings
        $tags = array_values(array_filter($tags, 'is_string'));

        if( empty($tags) ) {
            return [];
        }

        $oTags = null;
        $oTags = TagsModel::findBy(
            ['tag IN ('.implode(',', array_fill(0, count($tags), '?')).')']
        ,   $tags
        );

        // [id => tag]
        $aFound = $oTags ? $oTags->fetchEach('tag') : [];

        if( $blnThrowOnUnknown ) {

            // compare case-insensitive as the database collation usually is
            $aUnknown = array_udiff($tags, $aFound, 'strcasecmp');

            if( !empty($aUnknown) ) {
                throw new PageNotFoundException('Page not found: unknown tag "'.implode('", "', $aUnknown).'"');
            }
        }

        return array_keys($aFound);
    }


    /**
     * Generate parameter string for url
     *
     * @param array $tags
     *
     * @return string
     */
    public static function generateParameterForUrl( array $tags ): string {

        $tags = array_values($tags);
        if( empty($tags) ) {
            return '';
        }

        // for nicer url allow one tag to be used directly if it only got nice charatcers
        if( count($tags) === 1 && preg_match("/^[\w\-_]+$/", $tags[0]) ) {
            return $tags[0];
        }

        return base64_encode(json_encode($tags));
    }


    /**
     * Generate url for the given page with the given tags
     *
     * @param Contao\PageModel $page
     * @param array $tags
     * @param bool $blnGetParameter
     * @param bool $blnAbsolute
     *
     * @return string
     */
    public static function generateUrlWithTags( PageModel $page, array $tags, bool $blnGetParameter=false, bool $blnAbsolute=false ): string {

        $method = $blnAbsolute ? 'getAbsoluteUrl' : 'getFrontendUrl';
        $parameter = self::generateParameterForUrl($tags);

        $href = '';

        if( !strlen($parameter) ) {
            return $page->{$method}();
        }

        if( $blnGetParameter ) {
            $href = $page->{$method}().'?tag='.urlencode($parameter);
        } else {
            $href = $page->{$method}('/tag/'.$parameter);
        }

        return $href;
    }


    /**
     * Parse the given tag model to array
     *
     * @param numero2\TagsBundle\TagsModel $tag
     *
     * @return array
     */
    public static function parseTag( TagsModel $tag ): array {

        $aTag = $tag->row();
        $aTag['title'] = $tag->getTranslationData()['title'] ?? $tag->tag;

        if( empty($aTag['title']) ) {
            $aTag['title'] = $tag->tag;
        }

        $aTag['title'] = StringUtil::restoreBasicEntities($aTag['title']);

        return $aTag;
    }
}
