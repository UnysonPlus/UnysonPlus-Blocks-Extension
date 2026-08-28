<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = [];

$manifest['name']        = __( 'Gutenberg Blocks', 'fw' );
$manifest['slug']        = 'unysonplus-gutenberg';
$manifest['description'] = __(
	'Exposes Unyson+ elements as native Gutenberg blocks, for people who prefer the block editor but still want the Unyson+ options framework. Blocks are server-rendered by the same code as the page builder, so the front-end output is identical.',
	'fw'
);

$manifest['version']     = '1.0.35';
$manifest['display']     = true;
$manifest['standalone']  = true;

// Repository Info
$manifest['github_update'] = 'UnysonPlus/UnysonPlus-Gutenberg-Extension';
$manifest['github_repo']   = 'https://github.com/UnysonPlus/UnysonPlus-Gutenberg-Extension';
$manifest['github_branch'] = 'master';

// Author Info
$manifest['author']     = 'UnysonPlus';
$manifest['author_uri'] = 'https://www.lastimosa.com.ph/unysonplus';

// Meta
$manifest['license']      = 'GPL-2.0-or-later';
$manifest['text_domain']  = 'fw';
$manifest['requires_php'] = '7.4';
$manifest['requires_wp']  = '6.1'; // block.json "render" (file:./render.php) landed in 6.1

/**
 * Requires the Shortcodes extension: every block delegates its front-end render
 * to the matching shortcode, so there is nothing to render without it.
 */
$manifest['requirements'] = array(
	'extensions' => array(
		'shortcodes' => array(),
	),
);
