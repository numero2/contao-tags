Contao Tags
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-tags.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-tags) [![](https://img.shields.io/badge/License-LGPL%20v3-blue.svg?style=flat-square)](http://www.gnu.org/licenses/lgpl-3.0)

About
--

Adds the possibility to assign tags to individual elements (news articles only at the moment).

System requirements
--

* [Contao 4.9 or newer](https://github.com/contao/contao)

Installation & Configuration
--

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

* Additionally add the new `Tag Cloud` module anywhere on your page

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
