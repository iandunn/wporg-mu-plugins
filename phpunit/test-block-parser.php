<?php
/**
 * Test the BlockParser class and related functions.
 */

use WordPressdotorg\MU_Plugins\Utilities\BlockParser\BlockParser;
use function WordPressdotorg\MU_Plugins\Utilities\BlockParser\replace_with_i18n;

require dirname( __DIR__ ) . '/wp-content/mu-plugins/utilities/block-parser/class-block-parser.php';

class Test_BlockParser extends WP_UnitTestCase {
	/**
	 * Data provider for valid block content, and the expected strings when parsed.
	 *
	 * @return array
	 */
	public function data_block_content_strings() {
		return [
			[
				// Two plain paragraphs.
				"<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->",
				[ 'One.', 'Two.' ],
			],
			[
				// A paragraph with nested HTML.
				"<!-- wp:paragraph -->\n<p>A paragraph with <strong>bold</strong> text.</p>\n<!-- /wp:paragraph -->",
				[ 'A paragraph with <strong>bold</strong> text.' ],
			],
			[
				// A paragraph with a nested link.
				"<!-- wp:paragraph -->\n<p>A paragraph with <a href=\"#\">a link</strong>.</p>\n<!-- /wp:paragraph -->",
				[ 'A paragraph with <a href="#">a link</strong>.' ],
			],
			[
				// Empty paragraph.
				"<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->\n",
				[],
			],
			[
				// Buttons.
				"<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"#\">Button 1</a></div>\n<!-- /wp:button -->\n\n<!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\" href=\"#\">Button 2</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons -->",
				[ 'Button 1', '#', 'Button 2' ],
			],
			[
				// Column with a list, list-items.
				"<!-- wp:column {\"verticalAlignment\":\"center\",\"width\":\"50%\",\"style\":{\"spacing\":{\"padding\":{\"right\":\"60px\"}}}} -->\n<div class=\"wp-block-column is-vertically-aligned-center\" style=\"padding-right:60px;flex-basis:50%\"><!-- wp:list {\"className\":\"is-style-features\"} -->\n<ul class=\"is-style-features\"><!-- wp:list-item -->\n<li>Simple</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Intuitive</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Extendable</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Free</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Open</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list --></div>\n<!-- /wp:column -->",
				[ 'Simple', 'Intuitive', 'Extendable', 'Free', 'Open' ],
			],
			[
				// Image block with an alt.
				"<!-- wp:image {\"width\":150,\"height\":45,\"linkDestination\":\"custom\"} -->\n<figure class=\"wp-block-image is-resized\"><a href=\"#\"><img src=\"./badge-apple.png\" alt=\"Download on the Apple App Store\" width=\"150\" height=\"45\" /></a></figure>\n<!-- /wp:image -->",
				[ 'Download on the Apple App Store' ],
			],
			[
				// Navigation with custom navigation links.
				"<!-- wp:navigation {\"textColor\":\"blueberry-1\",\"overlayMenu\":\"never\",\"className\":\"is-style-dots\",\"style\":{\"spacing\":{\"blockGap\":\"0px\"}},\"fontSize\":\"small\"} -->\n<!-- wp:navigation-link {\"label\":\"Releases\",\"url\":\"https://wordpress.org/download/releases/\",\"kind\":\"custom\",\"isTopLevelLink\":true} /-->\n\n<!-- wp:navigation-link {\"label\":\"Nightly\",\"url\":\"https://wordpress.org/download/beta-nightly/\",\"kind\":\"custom\",\"isTopLevelLink\":true} /-->\n\n<!-- wp:navigation-link {\"label\":\"Counter\",\"url\":\"https://wordpress.org/download/counter/\",\"kind\":\"custom\",\"isTopLevelLink\":true} /-->\n\n<!-- wp:navigation-link {\"label\":\"Source\",\"url\":\"https://wordpress.org/download/source/\",\"kind\":\"custom\",\"isTopLevelLink\":true} /-->\n<!-- /wp:navigation -->",
				[ 'Releases', 'https://wordpress.org/download/releases/', 'Nightly', 'https://wordpress.org/download/beta-nightly/', 'Counter', 'https://wordpress.org/download/counter/', 'Source', 'https://wordpress.org/download/source/' ],
			],
			[
				// Cover with background image (with alt), and nested paragraph.
				"<!-- wp:cover {\"url\":\"http://localhost:8878/wp-content/uploads/2022/10/kerstin-wrba-zeInZepl_Hw-unsplash-scaled.jpg\",\"id\":7,\"dimRatio\":50,\"overlayColor\":\"tertiary\",\"isDark\":false} -->\n<div class=\"wp-block-cover is-light\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-tertiary-background-color has-background-dim\"></span><img class=\"wp-block-cover__image-background wp-image-7\" alt=\"Some alt.\" src=\"http://localhost:8878/wp-content/uploads/2022/10/kerstin-wrba-zeInZepl_Hw-unsplash-scaled.jpg\" data-object-fit=\"cover\"/><div class=\"wp-block-cover__inner-container\"><!-- wp:paragraph {\"align\":\"center\",\"fontSize\":\"large\"} -->\n<p class=\"has-text-align-center has-large-font-size\">Testing a Cover</p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:cover -->",
				[ 'Some alt.', 'Testing a Cover' ],
			],
			[
				// Social links.
				"<!-- wp:social-links {\"className\":\"is-style-logos-only\"} -->\n<ul class=\"wp-block-social-links is-style-logos-only\">\n<!-- wp:social-link {\"url\":\"https://www.facebook.com/WordPress/\",\"service\":\"facebook\",\"label\":\"Visit our Facebook page\"} /-->\n<!-- wp:social-link {\"url\":\"https://twitter.com/WordPress\",\"service\":\"twitter\",\"label\":\"Visit our Twitter account\"} /-->\n</ul>\n<!-- /wp:social-links -->",
				[ 'Visit our Facebook page', 'Visit our Twitter account' ],
			],
			[
				// List with links
				"<!-- wp:list -->\n<ul>\n<!-- wp:list-item -->\n<li><a href=\"#\">Fonts API</a></li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li>Interactivity <a href=\"#\">Link</a> API</li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li><a href=\"\">Block API</a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
				[ 'Fonts API', 'Interactivity <a href="#">Link</a> API', 'Block API' ],
			],
			[
				// List of lists
				"<!-- wp:list -->\n<ul><!-- wp:list-item -->\n<li>APIs:<!-- wp:list -->\n<ul>\n<!-- wp:list-item -->\n<li>Fonts API</li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li>Interactivity API</li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li>Block API</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list --></li>\n<!-- /wp:list-item -->\n</ul>\n<!-- /wp:list -->",
				[ 'APIs:', 'Fonts API', 'Interactivity API', 'Block API' ],
			],
		];
	}

	/**
	 * Test string parsing from valid block content.
	 *
	 * @dataProvider data_block_content_strings
	 */
	public function test_strings_parser( $block_content, $expected ) {
		$parser = new BlockParser( $block_content );
		$strings = $parser->to_strings();
		$this->assertSame( $expected, $strings );
	}

	/**
	 * Data provider for valid block content and the i18n-ized results.
	 *
	 * @return array
	 */
	public function data_block_content_i18n() {
		return [
			[
				// Two plain paragraphs.
				"<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->",
				"<!-- wp:paragraph -->\n<p><?php _e( 'One.', 'wporg' ); ?></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p><?php _e( 'Two.', 'wporg' ); ?></p>\n<!-- /wp:paragraph -->",
			],
			[
				// Image block with an alt.
				"<!-- wp:image {\"width\":150,\"height\":45,\"linkDestination\":\"custom\"} -->\n<figure class=\"wp-block-image is-resized\"><a href=\"#\"><img src=\"./badge-apple.png\" alt=\"Download on the Apple App Store\" width=\"150\" height=\"45\" /></a></figure>\n<!-- /wp:image -->",
				"<!-- wp:image {\"width\":150,\"height\":45,\"linkDestination\":\"custom\"} -->\n<figure class=\"wp-block-image is-resized\"><a href=\"#\"><img src=\"./badge-apple.png\" alt=\"<?php _e( 'Download on the Apple App Store', 'wporg' ); ?>\" width=\"150\" height=\"45\" /></a></figure>\n<!-- /wp:image -->",
			],
			[
				// Navigation with custom navigation links.
				"<!-- wp:navigation {\"textColor\":\"blueberry-1\",\"overlayMenu\":\"never\",\"className\":\"is-style-dots\",\"style\":{\"spacing\":{\"blockGap\":\"0px\"}},\"fontSize\":\"small\"} -->\n<!-- wp:navigation-link {\"label\":\"Releases\",\"url\":\"#\",\"kind\":\"custom\",\"isTopLevelLink\":true} /-->\n<!-- /wp:navigation -->",
				"<!-- wp:navigation {\"textColor\":\"blueberry-1\",\"overlayMenu\":\"never\",\"className\":\"is-style-dots\",\"style\":{\"spacing\":{\"blockGap\":\"0px\"}},\"fontSize\":\"small\"} -->\n<!-- wp:navigation-link {\"label\":\"<?php _e( 'Releases', 'wporg' ); ?>\",\"url\":\"<?php _e( '#', 'wporg' ); ?>\",\"kind\":\"custom\",\"isTopLevelLink\":true} /-->\n<!-- /wp:navigation -->",
			],
			[
				// List with links
				"<!-- wp:list -->\n<ul>\n<!-- wp:list-item -->\n<li><a href=\"#\">Fonts API</a></li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li>Interactivity <a href=\"#\">Link</a> API</li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li><a href=\"\">Block API</a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
				"<!-- wp:list -->\n<ul>\n<!-- wp:list-item -->\n<li><a href=\"#\"><?php _e( 'Fonts API', 'wporg' ); ?></a></li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li><?php _e( 'Interactivity <a href=\"#\">Link</a> API', 'wporg' ); ?></li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li><a href=\"\"><?php _e( 'Block API', 'wporg' ); ?></a></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
			],
			[
				// List of lists
				"<!-- wp:list -->\n<ul><!-- wp:list-item -->\n<li>APIs:<!-- wp:list -->\n<ul>\n<!-- wp:list-item -->\n<li>Fonts API</li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li>Interactivity API</li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li>Block API</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list --></li>\n<!-- /wp:list-item -->\n</ul>\n<!-- /wp:list -->\n",
				"<!-- wp:list -->\n<ul><!-- wp:list-item -->\n<li><?php _e( 'APIs:', 'wporg' ); ?><!-- wp:list -->\n<ul>\n<!-- wp:list-item -->\n<li><?php _e( 'Fonts API', 'wporg' ); ?></li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li><?php _e( 'Interactivity API', 'wporg' ); ?></li>\n<!-- /wp:list-item -->\n<!-- wp:list-item -->\n<li><?php _e( 'Block API', 'wporg' ); ?></li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list --></li>\n<!-- /wp:list-item -->\n</ul>\n<!-- /wp:list -->\n"
			]
		];
	}

	/**
	 * Test the i18n replacement.
	 *
	 * @dataProvider data_block_content_i18n
	 */
	public function test_i18n_replacement( $block_content, $expected ) {
		$content_with_i18n = replace_with_i18n( $block_content );
		$this->assertSame( $expected, $content_with_i18n );
	}
}