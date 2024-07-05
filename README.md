Contao Tags
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-tags.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-tags) [![](https://img.shields.io/badge/License-LGPL%20v3-blue.svg?style=flat-square)](http://www.gnu.org/licenses/lgpl-3.0)

## About

Adds the possibility to assign tags to individual elements.

## System requirements

* [Contao 4.13 or newer](https://github.com/contao/contao)

## Installation & Configuration

* Install via Contao Manager or Composer (`composer require numero2/contao-tags`)

* Add the following code snippet into your `news_(full|latest|short|simple).html5` template

  ```php
  <?php if( $this->tags ): ?>
    <div class="tags">
      <?php foreach( $this->tags as $tag ): ?>
        <?= $tag; ?>
      <?php endforeach; ?>
   </div>
  <?php endif; ?>
  ```

* Configure your `Newslist` or `Newsreader` front end modules and set a `Redirect page for tags`

* Additionally add the `News Tag Cloud` module anywhere on your page

* All of the above is now also possible for the templates `event_(full|list|teaser|upcoming).html5`, used in `Eventlist` or `Eventreader` and there is an `Events Tag Cloud` module

## Insert-Tags

This extensions comes with a couple of Insert-Tags that can be used to link to a page which will only show entries with matching tags.

| Insert-Tag      | Description                    |
| ------------- | ------------------------------ |
| {{tag_link::1::foo}} | Creates a link URL to the page with ID 1 and the tag `foo` |
| {{tag_link::1::foo::bar}} | Creates a link URL to the page with ID 1 and the tags `foo` and `bar` |
| {{tag_link::1::foo&#124;absolute}} | Creates an absolute link URL to the page with ID 1 and the tag `foo` |
| {{tag_link::1::foo&#124;get}} | Creates a link URL to the page with ID 1 and the tag `foo` using GET parameters |
| {{tag_link::1::foo&#124;absolute&#124;get}} | Creates an absolute link URL to the page with ID 1 and the tag `foo` using GET parameters |
| {{tags_active}} | Creates a list of all active tags seperated by `, `, e.g. `tag1, tag2, tag3` |
| {{tags_active:: + }} | Creates a list of all active tags seperated by the given string ` + `, e.g. `tag1 + tag2 + tag3` |
| {{tags_active::, :: and }} | Creates a list of all tags seperated by `, ` but the last tag is added with ` and `, e.g. `tag1, tag2, tag3 and tag4` |

A more robust way for the links would be to use the tag's ID instead of its name (which can be changed under `System › Tags`).
So instead of `{{tag_link::1::foo}}` you could also write `{{tag_link::1::69}}` (assuming the ID of `foo` is 69).

## For Developers

### Integrating a tags field in your own extension

If you want to use the tags for fields in your own Data Containers you can do so by defining the field as follows:

```php
$GLOBALS['TL_DCA']['tl_my_extension']['fields']['my_tags'] = [
    'exclude'           => true
,   'inputType'         => 'select'
,   'foreignKey'        => 'tl_tags.tag'
,   'options_callback'  => ['numero2_tags.listener.data_container.tags', 'getTagOptions']
,   'save_callback'     => [['numero2_tags.listener.data_container.tags', 'saveTags']]
,   'eval'              => ['multiple'=>true, 'size'=>8, 'tl_class'=>'clr long tags', 'chosen'=>true]
,   'sql'               => "blob NULL"
,   'relation'          => ['type'=>'hasMany', 'load'=>'eager']
];
```

`foreignKey`, `options_callback`and `save_callback`are mandatory.
In the `eval` section we add a class called `tags` - this is also needed for the JavaScript handling. There are some more options which can be defined in `eval`.

### Eval-Options

| Option             | Description                                                                                                                                                                                                                                     |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `groupTagsByField` | If set to `true` the user will only be able to select from a list of tags that have been already used on this specific field in the current table. If set to `false`or not set at all, the user will be able to choose from all tags available. |
