<?php
/*
Plugin Name: GFM Syntax Highlighter
Plugin URI:  
Description: Uses Git Flavored Markdown and Syntax highlighting.
Version:     0.0.1
Author:      Oomph Inc
Author URI:  http://www.oomphinc.com/
Domain Path: /languages
Text Domain: gfm-syn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class GFM_SyntaxHighlighter {

	static $code_block = array();
	static $code_block_index = 0;
	static $dependant = false;
	static function init() {
		add_action( 'init', array( __CLASS__, 'action_init' ) );
		register_activation_hook( __FILE__, array( __CLASS__,'check_dependencies' ) );
		add_action( 'admin_init', array( __CLASS__,'maybe_self_deactivate' ) ); 
	}

	static function check_dependencies() {
		$activated = self::has_dependencies();
	}
	static function has_dependencies() {
		if ( !class_exists( 'WP_GFM' ) || !class_exists( 'SyntaxHighlighter' ) ) {
			//echo '<div class="error"><p>gfm-syntaxhighlighter cannot be activated because either wp-gfm or SyntaxHighlighter Evolved is not activated.</p></div>';
			trigger_error('gfm-syntaxhighlighter cannot be activated because either wp-gfm or SyntaxHighlighter Evolved is not activated.', E_USER_ERROR);
			$dependant = false;
		} else {
			$dependant = true;
		}
		return $dependant;
	}
	static function maybe_self_deactivate() {
		if ( !class_exists( 'WP_GFM' ) || !class_exists( 'SyntaxHighlighter' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( plugin_basename( __FILE__ ) );
			echo '<div class="error"><p>gfm-syntaxhighlighter has deactivated itself because either wp-gfm or SyntaxHighlighter Evolved is no longer active.</p></div>';
		}
	}
	static function action_init() {
		if( !class_exists( 'SyntaxHighlighter' ) ) {
			return;
		}
		add_filter( 'the_content', array( __CLASS__, 'the_content' ), 6 );
		add_filter( 'the_content', array( __CLASS__, 'replace_placeholders' ), 100 );
	}

	static function the_content( $content ) {
		$content = preg_replace_callback( '/\[markdown\](.*?)\[\/markdown\]/s', array( __CLASS__, 'create_placeholders' ), $content );
		$content = preg_replace_callback( '/\[gfm\](.*?)\[\/gfm\]/s', array( __CLASS__, 'create_placeholders' ), $content );
		return $content;
	}

	static function create_placeholders( $content ) {
		return preg_replace_callback('/(```)([a-z-]*)\s(.*?)\1/sm',
			array(__CLASS__, 'replace_codeblocks'), $content[0]
		);
	}

	static function replace_codeblocks( $matches ) {
		$placeholder = '::code_block' . self::$code_block_index++ . '::';
		self::$code_block[$placeholder] = $matches;
		return $placeholder;
	}

	static function replace_placeholders( $content ) {
		global $SyntaxHighlighter;
		//Removing <p> tags from code snippets
		$content = preg_replace( '#\<p\>(::code_block\d+::)\<\/p\>#s','$1' , $content );

		foreach( self::$code_block as $placeholder => $matches ) {
			$code = $SyntaxHighlighter->parse_shortcodes( '[code lang="' . $matches[2] . '""]' . html_entity_decode( $matches[3] ) . '[/code]');
			$content = str_replace( $placeholder, $code, $content );
		}
		return $content;
	}
}
GFM_SyntaxHighlighter::init();
