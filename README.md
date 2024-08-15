# WP-GeSHi-Highlight-Redux

This is a personal fork of the venerable [WP-GeSHi-Highlight](https://gehrcke.de/wp-geshi-highlight/) - a WordPress plugin which converts `<pre><code class="language-*>…</code></pre>` into highlighted code, using server-side rendering.

You can [download the original WordPress plugin](https://wordpress.org/plugins/wp-geshi-highlight/) which was developed by [Dr. Jan-Philip Gehrcke](https://gehrcke.de/).

This fork contains some bugfixes, changes, and improvements by [Terence Eden](https://edent.tel).

[![No Maintenance Intended](https://unmaintained.tech/badge.svg)](http://unmaintained.tech/)

## Changelog

### 1.6.0 (2024-08-15)

* Disable comment rendering
* Move to post-Markdown rendering
* More flexibility on the name of the class

### 1.5.0

* RSS & Atom feeds - disable code highlighting
* Remove extra style wrappers
* Markdown support
* Remove line-numbers
* Remove escape option (escape now permanent)
* Remove TinyMCE changes
* Remove custom CSS options
* Improve default CSS
* Improve HTML detection