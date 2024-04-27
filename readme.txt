=== WP-GeSHi-Highlight-Redux — rock-solid syntax highlighting for 259 languages ===
Contributors: edent, jgehrcke
Tags: syntax, highlight, code, geshi, highlighting, syntax
Tested up to: 6.5.2
Requires at least: 6.5.2
Requires PHP: 8.0
Stable tag: 1.5
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple. Fast. 259 languages. Mobile-friendly. Rock-solid (GeSHi).

== Description ==

**• Highlights:**

* Supports [259 languages](https://github.com/GeSHi/geshi-1.0/tree/v1.0.9.1/src/geshi).
* Server-side. Saves bandwidth, latency, and battery compared to client-side rendering.
* Near-zero load on the backend. With a caching solution this does not affect your page load time at all.
* Line numbering. If you want to (no vertical displacements between code line and line number).
* Default style was tested with more than 20 themes, including Twenty Ten to Fifteen, and tweaked based on a bunch of user feedback.
* If you'd like to tweak the style: just provide your own CSS file. Styles are highly & easily configurable.
* Per-block styles: each code block on a single page can be styled on its own (if that is something you would liked to do).
* Clean, small and valid HTML output.
* Well-documented source code using modern WordPress API calls.
* I have maintained this plugin for several years and I plan to continue to provide support.
* Based on [GeSHi](http://qbnz.com/highlighter/), a reliable and well-established PHP highlighting engine, used by popular community forum applications such as phpBB or wiki applications such as Dokuwiki or MediaWiki.

WP-GeSHi-Highlight works as a drop-in replacement for [WP-Syntax](http://wordpress.org/extend/plugins/wp-syntax/), which does not seem to be maintained anymore (as of 2015). Make the switch, it should just work (let me know how it went)!

**• Usage:**

Recommendation: Write in Markdown using the "Text" (raw) editor. Do not use the "Visual" editor. Do not use Gutenberg. 

For example:

````
```php
$a  = $b + 2;
```
````

That will render as the HTML:

`<pre><code class="language-php">
$a = 1;
</code></pre>`

Which will then be syntax highlighted.

Publish/preview, and have a look at the result!


== Installation ==
1. Upload the `wp-geshi-highlight-redux` directory to the `/wp-content/plugins` directory.
1. Activate the plugin through the plugins menu in WordPress.
1. Use it.


== Changelog ==

= 1.4.3 (2023-04-30) =
* Update GeSHi to commit 7884d2 (Feb 19, 2023).
* Fix for Golang undefined indices.
* Fix for highlighted lines.

= 1.4.2 (2019-11-30) =
* Update GeSHi to release 1.0.9.1.
* New language support for Roff, sshconfig, Wolfram.
* Improved language support for to bash, Haskell, ini, Matlab, rsplus, scilab.

= 1.4.1 (2019-06-12) =
* Enhance compatibility with the Kotha theme (thanks to Zsolt Herczeg).

= 1.4.0 (2019-06-12) =
* Instruct TinyMCE to leave the pre tag alone regardless of its attributes (this enhances compatibility with the Classic Editor).
* Consolidate style of line numbers (thanks to Wolfgang Maennel for reporting).
* Include 20 language files erroneously missed in previous releases (thanks to Berry Plasman for reporting).

= 1.3.4 (2018-12-26) =
* Update GeSHi to the current development version of the 1.0.9.1 release. This improves support for the SQL and MySQL languages and enhances PHP 7 compatibility.

= 1.3.3 (2018-05-02) =
* Address count()-related warnings on PHP 7 (thanks to dmorlock for reporting).

= 1.3.2 (2018-04-23) =
* Address 'A non-numeric value encountered' warning on PHP 7' (thanks to John and Sven for reporting).
* Fix a bug when using custom CSS files (thanks to Dan Bader for reporting).

= 1.3.1 (2017-10-03) =
* Update GeSHi to 1.0.8.13. This is a major language support update. With that, WP-GeSHI-Highlight now newly supports or has improved support for Swift, Julia, biblatex, Kotlin, Lua, Ceylon, T-SQL, Haskell, AutoIt, Windows batch, SASS, LLVM IR, and others.

= 1.3.0 (2015-06-18) =
* Enhance compatibility of the default stylesheet with a large range of themes by increasing the specificity of certain CSS selectors and by adding more style directives. This ensures a better out-of-the-box experience. Thanks to Pascal Krause for reporting an incompatilibity with Twenty Ten.

= 1.2.4 (2015-06-17) =
* Increase compatibility with CDNs: fix double slash appearing in CSS file URL.
* Remove redundant call to `wp_register_style()`.
* Change style sheet ID prefix, add newline characters to GeSHi CSS code output.
* Improve code documentation and readability.

= 1.2.3 (2015-01-12) =
* Update GeSHi to 1.0.8.12 (language file updates).

= 1.2.2 (2014-05-26) =
* Improve default CSS (add box-shadow:none to pre block, override external setting).

= 1.2.1 (2014-05-21) =
* Use plugin_dir_path/url() instead of obsolete WP_PLUGIN_DIR/URL constants (improve compatibility with HTTPS-driven websites).
* Remove obsolete screenshot from release.
* Minor code cleanup.

= 1.2.0 (2014-04-16) =
* Update GeSHi to git state of 2014-04-16 (tons of language updates).
* Largely improve default style, for compatibility with modern browsers.

= 1.1.0 (2013-06-22) =
* Adjust default style for compatibility with Twentythirteen theme.
* Remove GeSHi's hard-coded font-size and line-height code styles.
* Reduce box shadow and border radius in default style.
* Slightly increase top and bottom padding in default style.

= 1.0.8 (2013-01-17) =
* Improve default stylesheet: make use of CSS3 box shadows, several tweaks.
* If the code block style file is found in the [theme style directory](http://codex.wordpress.org/Function_Reference/get_stylesheet_directory), it now has priority over the one in the plugin directory.
* Update GeSHi to 1.0.8.11 (numerous language file updates).
* Include GeSHi language file for nginx configuration files (taken from GeSHi SVN revision r2572, to be released with 1.0.8.12).
* Use [wp_enqueue_style](http://codex.wordpress.org/Function_Reference/wp_enqueue_style) method for style sheet inclusion.
* Deactivate GeSHi economic mode when printing style sheet.
* Do not print credits to HTML source anymore.

= 1.0.7 (2012-05-12) =
* Fix collision with other plugins including their own version of GeSHi (thanks to Bas for reporting).

= 1.0.6 (2012-05-12) =
* Fix line-spacing bug when displaying code blocks with different line numbering settings on the same page (thanks to Bas ten Berge for reporting).

= 1.0.5 (2011-02-27) =
* Update GeSHi to 1.0.8.10 ("Some minor parser tweaks and fixes to existing language files. It adds 15 more languages.").

= 1.0.4 (2011-01-12) =
* Optimize: now, CSS code is only printed once if the same language is used for multiple code blocks on the same page.
* Minor code changes.

= 1.0.3 (2011-01-06) =
* Fix: comments are not always showing up (thanks to Uli for reporting).

= 1.0.2 (2011-01-04) =
* Minor code changes.
* Remove beta tag.

= 1.0.1-beta (2010-12-18) =
* Fix: highlight in comments not always showing up.

= 1.0.0-beta (2010-11-22) =
* Initial release.

