<?php
/***
Plugin Name: WP-GeSHi-Highlight-Redux
Plugin URI: https://github.com/edent/WP-GeSHi-Highlight-Redux
Description: Syntax highlighting for 259 languages. Mobile-friendly. Easy-to-use. Based on a rock-solid engine (GeSHi).
Author: Terence Eden
Version: 1.5
Author URI: https://edent.tel/

WP-GeSHi-Highlight-Redux was originally based on WP-GeSHi-Highlight
by Dr. Jan-Philip Gehrcke - https://gehrcke.de

WP-GeSHi-Highlight was originally based on WP-Syntax by Ryan McGeary.
https://web.archive.org/web/20160606232402/http://wp-syntax.com/


Copyright (C) 2024- Terence Eden
Copyright (C) 2010-2023 Dr. Jan-Philip Gehrcke
Copyright (C) 2007-2009 Ryan McGeary
	
This file is part of WP-GeSHi-Highlight-Redux.
You can use, modify, redistribute this program under the terms of the GNU General Public License Version 2 (GPL2): http://www.gnu.org/licenses.


## Advantages over comparable highlighters

* WP-GeSHi-Highlight filters & replaces code snippets as early as possible. The highlighted code is inserted as late as possible. Hence, interference with other plugins is minimized.
* No computing resources are wasted if the current view is free of code snippets.

## Usage of GeSHi's get_stylesheet()
* Creates short highlighting html code: styling is not based on long <span style"..."> occurrences.

## This is how the plugin works for all page requests

### I) template_redirect hook:
1. The user has sent a request. Wordpress has set up its `$wp_query` object.  `$wp_query` contains information about all content potentially shown to the user.
2. This plugin iterates over this content, i.e. over each post, including each (approved) comment belonging to this post.
3. While iterating over the post and comment texts, occurrences of the pattern <pre><code class="language-…>CODE</code></pre> are being searched for.
4. If one such pattern is found, the information (language and CODE) is stored in a global variable, together with a match index.
5. If there is code to highlight, the occurrence of the pattern in the original content (post/comment text) is deleted and replaced by a unique identifier containing the corresponding match index. Therefore, the content cannot be altered by any other plugin afterwards.
6. GeSHi iterates over all code snippets and generates HTML code for each snippet, according to the given programming language.
7. Additionally, GeSHi generates optimized CSS code for each snippet. All CSS code generated by GeSHi ends up in one string.
8. For each code snippet, the HTML code and the corresponding match index is stored in a global variable.

### II) wp_enqueue_scripts hook:

Via this hook, the plugin instructs WordPress to print include the following resources in the head section of the HTML document:

* A style tag referencing wp-geshi-highlight.css, from the theme or plugin directory, for general styling of code blocks.
* All CSS code generated by GeSHi is included, inline.

### III) content filters:
* The plugin defines three low priority filters on post text, post excerpt, and comment text. These filters run after most other plugins have done  their job, i.e. shortly before the html code is delivered to the user's browser.
* The filter code searches the content for the unique identifiers stored in step I.5.
* If such an identifier is found, it gets replaced by the corresponding highlighted code snippet.

***/

// Entry point of the plugin (right after WordPress has finished processing the user request, set up `$wp_query`, and right before the template renders the HTML output).
add_action('template_redirect', 'wp_geshi_main');


//	Main function
function wp_geshi_main() {
	//	Don't change the content on RSS / Atom feeds
	if (is_feed()) {
		return;
	}

	global $wp_geshi_codesnipmatch_arrays;
	global $wp_geshi_run_token;
	global $wp_geshi_comments;
	global $wp_geshi_used_languages;
	global $wp_geshi_requested_css_files;
	$wp_geshi_requested_css_files = array();
	$wp_geshi_comments = array();
	$wp_geshi_used_languages = array();

	// Snippets will temporarily be replaced by a unique token. Generate it.
	$wp_geshi_run_token = uniqid(rand());

	// Filter all post/comment texts and store and replace code snippets.
	wp_geshi_filter_and_replace_code_snippets();

	// If no snippets to highlight were found it is time to leave.
	if (!$wp_geshi_codesnipmatch_arrays || !count($wp_geshi_codesnipmatch_arrays)) return;

	// `$wp_geshi_codesnipmatch_arrays` is populated. Process it.
	// That is GeSHi's task: generate HTML and CSS code.
	wp_geshi_highlight_and_generate_css();

	// Now, `$wp_geshi_css_code` and `$wp_geshi_highlighted_matches` are set.
	// Add action to add CSS code to HTML header.
	add_action('wp_enqueue_scripts', 'wp_geshi_add_css_to_head');

	// Add high priority filter to replace comments with the ones stored in `$wp_geshi_comments`.
	// In `wp_geshi_filter_and_replace_code_snippets()` the comments are queried, filtered and stored in `$wp_geshi_comments`.
	// But, unlike posts, comments are queried again when `comments_template()` is called by the theme; so comments are read two times from the database.
	// After the second read, all changes (including the "uuid replacement") are lost.
	// The `comments_array` filter is triggered and can be used to set all comments to the state after the first filtering by wp-geshi-highlight (as saved in `$wp_geshi_comments`).
	add_filter('comments_array', 'wp_geshi_insert_comments_with_uuid', 1);

	// Add low priority filter to replace unique identifiers with highlighted code.
	add_filter('the_content', 'wp_geshi_insert_highlighted_code_filter', 99);
	add_filter('the_excerpt', 'wp_geshi_insert_highlighted_code_filter', 99);
	add_filter('comment_text', 'wp_geshi_insert_highlighted_code_filter', 99);
}


// Parse all posts and comments related to the current query.
// While iterating over these texts, do the following:
// - Detect <pre><code class="language-*>CODE</code></pre> patterns.
// - Store these patterns in a global variable.
// - Modify post/comment texts: replace code patterns by a unique identifier.
function wp_geshi_filter_and_replace_code_snippets() {
	global $wp_query;
	global $wp_geshi_comments;
	// Iterate over all posts in this query.
	foreach ($wp_query->posts as $post) {
		// Extract code snippets from the content. Replace them.
		$post->post_content = wp_geshi_filter_replace_code($post->post_content);
		// Iterate over all approved comments belonging to this post.
		// Store comments with uuid (code replacement) in `$wp_geshi_comments`.
		$comments = get_approved_comments($post->ID);
		foreach ($comments as $comment) {
			$wp_geshi_comments[$comment->comment_ID] =
				wp_geshi_filter_replace_code($comment->comment_content);
		}
	}
}


// This is called from the comments_array filter.
// Replace comments from the second DB read with the ones stored in `$wp_geshi_comments`.
function wp_geshi_insert_comments_with_uuid($comments_2nd_read) {
	global $wp_geshi_comments;
	// Iterate over comments from 2nd read.
	// Call by reference, otherwise the changes have no effect.
	foreach ($comments_2nd_read as &$comment) {
		if (array_key_exists($comment->comment_ID, $wp_geshi_comments)) {
			// Replace the comment content from 2nd read with the content that was created after the 1st read.
			$comment->comment_content =
				$wp_geshi_comments[$comment->comment_ID];
		}
	}
	return $comments_2nd_read;
}


// Search all <pre><code class="language-*"></code></pre> occurrences.
// Store them globally.
// Replace them with unique identifiers (uuid+snippet ID).
// Call `wp_geshi_substitute($match)` for each match.
// A `$match` is an array, following the sub-pattern of the regex:
// 0: all
// 1: language
// 2: code
function wp_geshi_filter_replace_code($s) {
	return preg_replace_callback(
		"/\s*<pre><code(?:class=[\"']language\-([\w-]+)[\"']|\s)+>(.*)<\/code><\/pre>\s*/siU",
		"wp_geshi_store_and_substitute",
		$s
	);
}


// Store snippet data. Return identifier for this snippet.
function wp_geshi_store_and_substitute($match_array) {
	global $wp_geshi_run_token, $wp_geshi_codesnipmatch_arrays;

	// count() returns 0 if the variable is not set already.
	// Index is required for building the identifier for this code snippet.
	$match_index = $wp_geshi_codesnipmatch_arrays ? count($wp_geshi_codesnipmatch_arrays) : 0;

	// Elements of `$match_array` are strings matching the sub-expressions in the regular expression search from `wp_geshi_filter_replace_code()`.
	// They contain the language and the code snippet itself.
	// Store this array for later usage.
	// Append the match index to `$match_array`.
	$match_array[] = $match_index;
	$wp_geshi_codesnipmatch_arrays[$match_index] = $match_array;

	// Return a string that identifies the match.
	// This string is replaces the <pre><code class="language-*>…</code></pre> pattern.
	return "<p>" . $wp_geshi_run_token . "_" .
		sprintf("%06d", $match_index) . "</p>";
}


// Iterate through all match arrays in `$wp_geshi_codesnipmatch_arrays`.
// Perform highlighting operation and store the resulting HTML in `$wp_geshi_highlighted_matches[$match_index]`.
// Generate CSS code and append it to global `$wp_geshi_css_code`.
function wp_geshi_highlight_and_generate_css() {
	global $wp_geshi_codesnipmatch_arrays;
	global $wp_geshi_css_code;
	global $wp_geshi_highlighted_matches;
	global $wp_geshi_requested_css_files;
	global $wp_geshi_used_languages;

	// Check for `class_exists('GeSHi')` to prevent `Cannot redeclare class GeSHi` errors caused by another plugin including its own version of GeSHi.
	// TODO: Include GeSHi via namespacing or class renaming.
	if (!class_exists('GeSHi')) include_once("geshi/geshi.php");
	$wp_geshi_css_code = "";
	foreach ($wp_geshi_codesnipmatch_arrays as $match_index => $match) {
		// Process match details.
		// The array structure is explained in `wp_geshi_filter_replace_code()`.
		$language = strtolower(trim($match[1]));
		$code = wp_geshi_code_trim($match[2]);
		$code = htmlspecialchars_decode($code);

		// Set up GeSHi.
		$geshi = new GeSHi($code, $language);
		
		// Output CSS code / do *not* create inline styles.
		$geshi->enable_classes();
		
		// Disable keyword links.
		$geshi->enable_keyword_links(false);

		// By default, geshi sets font size to 1em and line height to 1.2em.
		// That does not fit many modern CSS architectures. Make this
		// relative and, most importantly, customizable.
		$geshi->set_code_style('');

		// If the current language has not been processed in a previous iteration:
		// - create CSS code for this language
		// - append this to the `$wp_geshi_css_code string`.
		// $geshi->get_stylesheet(false) disables the economy mode, i.e. this will return the full CSS code for the given language.
		//	See http://qbnz.com/highlighter/geshi-doc.html#getting-stylesheet
		// This allows reuse of the same CSS code for multiple code blocks of the same language.
		if (!in_array($language, $wp_geshi_used_languages)) {
			$wp_geshi_used_languages[] = $language;
			$wp_geshi_css_code .= $geshi->get_stylesheet(false);
		}

		// Append the default css file to the array.
		$cssfile = "wp-geshi-highlight";
		$wp_geshi_requested_css_files[] = $cssfile;

		//	Start the output
		$output = '<div class="' . $cssfile . '">';

		// Create highlighted HTML code.
		$output .= $geshi->parse_code();
		if ($cssfile != "none") {
			$output .= '</div>';
		}

		// Store highlighted HTML code for later usage.
		$wp_geshi_highlighted_matches[$match_index] = $output;
	}

	// All code snippets have been parsed.
	// Highlighted code is stored.
	// CSS code has been generated.
	// Delete what is not required any more.
	unset($wp_geshi_codesnipmatch_arrays);
}


// Replace snippet IDs with highlighted HTML.
function wp_geshi_insert_highlighted_code_filter($content) {
	global $wp_geshi_run_token;
	return preg_replace_callback(
		"/<p>\s*" . $wp_geshi_run_token . "_(\d{6})\s*<\/p>/si",
		"wp_geshi_get_highlighted_code",
		$content
	);
}

//	Add matches to the highlighted code
function wp_geshi_get_highlighted_code($match) {
	global $wp_geshi_highlighted_matches;
	// Found a unique identifier. Extract code snippet match index.
	$match_index = intval($match[1]);
	// Return corresponding highlighted code.
	return $wp_geshi_highlighted_matches[$match_index];
}

//	Trimming function
function wp_geshi_code_trim($code) {
	// Special ltrim because leading whitespace matters on 1st line of content.
	$code = preg_replace("/^\s*\n/siU", "", $code);
	$code = rtrim($code);
	return $code;
}

//	CSS
function wp_geshi_add_css_to_head() {
	global $wp_geshi_css_code;
	global $wp_geshi_requested_css_files;

	// Get absolute path to the directory this plugin resides in, with a trailing slash.
	// https://codex.wordpress.org/Function_Reference/plugin_dir_path
	$plugin_dir = plugin_dir_path(__FILE__);

	// Generate URL pointing to this plugin directory, with a trailing slash.
	// https://codex.wordpress.org/Function_Reference/plugin_dir_url
	$plugin_dir_url = plugin_dir_url(__FILE__);

	// Get absolute path to the directory of the current (child) theme and add a trailing slash.
	// https://codex.wordpress.org/Function_Reference/get_stylesheet_directory
	$theme_dir = get_stylesheet_directory() . "/";

	// Get URL for the current (child) theme's stylesheet directory and add a trailing slash.
	// https://codex.wordpress.org/Function_Reference/get_stylesheet_directory_uri
	$theme_dir_url = get_stylesheet_directory_uri() . "/";

	// Process array of requested CSS files (i.e. of file basenames without .css extension)
	// Remove duplicates.
	$wp_geshi_requested_css_files = array_unique($wp_geshi_requested_css_files);

	foreach ($wp_geshi_requested_css_files as $cssfile) {
		$cssfilename = $cssfile . ".css";
		// If the CSS file is found in the `get_stylesheet_directory()`, make it take precedence over the CSS file in the plugin directory.
		$theme_css_path = $theme_dir . $cssfilename;
		$plugin_css_path = $plugin_dir . $cssfilename;
		if (file_exists($theme_css_path))
			// Use the CSS file from the theme directory.
			$cssurl = $theme_dir_url . $cssfilename;
		elseif (file_exists($plugin_css_path))
			// Use the CSS file from the plugin directory.
			$cssurl = $plugin_dir_url . $cssfilename;
		else
			// A CSS file was requested that does not reside in the file system.
			$cssurl = false;
		if ($cssurl) {
			// Instruct WordPress to include this resource in the HTML head.
			// https://codex.wordpress.org/Function_Reference/wp_enqueue_style
			wp_enqueue_style("wpgeshi-" . $cssfile, $cssurl);
		}
	}

	// Echo GeSHi highlighting CSS code inline.
	if (strlen($wp_geshi_css_code) > 0)
		echo "<style type=\"text/css\">\n" . $wp_geshi_css_code . "</style>\n";
}


// Set allowed attributes for pre tags. For more info see wp-includes/kses.php
// Also see http://code.tutsplus.com/articles/new-wp-config-tweaks-you-probably-dont-know--wp-35396
if (!CUSTOM_TAGS) {
	$allowedposttags['pre'] = array(
		'lang' => array(),
		'line' => array(),
		'escaped' => array(),
		'cssfile' => array()
	);
	// Allow plugin use in comments.
	$allowedtags['pre'] = array(
		'lang' => array(),
		'line' => array(),
		'escaped' => array(),
		'cssfile' => array()
	);
}

//	Prevent <pre> elements being mangled
function wp_geshi_tiny_mce_settings_filter($settings) {
	// Upon switching between the "text" (raw) view and the "HTML" (TinyMCE-based) view the TinyMCE editor may modify a post'scontent.
	// This filter instructs TinyMCE to leave <pre> elements alone when they have non-standard attributes as used by this plugin, such as `lang` and `cssfile` and `lang`.

	// https://codex.wordpress.org/Plugin_API/Filter_Reference/tiny_mce_before_settings
	// https://www.tiny.cloud/docs-3x/reference/configuration/Configuration3x@extended_valid_elements/
	// https://wordpress.stackexchange.com/q/52582/1694
	// https://www.wp-plugin-api.com/example/9568/

	// From the TinyMCE docs:
	// "When adding a new attribute by specifying an existing element rule (e.g. img), the entire rule for that element is over-ridden" 
	// Therefore, allow `pre[*]` which should be less strict than any other specification string for the `pre` element.

	// If other plugins modify extended_valid_elements then there is no conflict unless the `pre` tag behavior gets modified.
	// Upon conflict the filter ID matters I believe.

	$ext = 'pre[*]';

	if (isset($settings['extended_valid_elements'])) {
		$settings['extended_valid_elements'] .= ',' . $ext;
	} else {
		$settings['extended_valid_elements'] = $ext;
	}

	return $settings;
}
add_filter('tiny_mce_before_init', 'wp_geshi_tiny_mce_settings_filter', 99);
