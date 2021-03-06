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

