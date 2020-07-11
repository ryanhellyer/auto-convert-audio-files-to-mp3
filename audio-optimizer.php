<?php

namespace AudioOptimizer;

/*
Plugin Name: Audio Optimizer
Plugin URI: https://geek.hellyer.kiwi/plugins/convert-audio-files/
Description: Converts audio files from WAV to MP3
Version: 2.0.0
Author: Ryan Hellyer, Grayson Erhard
Author URI: https://geek.hellyer.kiwi/

*/


add_filter( 'wp_handle_upload', 'AudioOptimizer\convert' );

/**
 * @param $data
 *
 * Convert into hardcoded format and bitrate.
 *
 * @return mixed
 */
function convert( $data ) {

	$bitrate = 160000;

	$refactored_data = refactor( $data );

	if ( ! validated( $refactored_data ) ) {
		return $data;
	}

	$command = 'ffmpeg -i ' . $refactored_data['old_file'] . ' -b:a ' . $bitrate . ' ' . $refactored_data['file'];

	$result = shell_exec( $command );

	unlink( $refactored_data['old_file'] );

	return $refactored_data;
}


/**
 * @param $data
 *
 * Refactor data passed by WordPress uploader and convert it into the desired format.
 *
 * @return mixed
 */
function refactor( $data ) {

	$desired_ext                  = 'ogg';
	$path_parts                   = pathinfo( $data['file'] );
	$refactored_data['ext']       = preg_replace( "/[^a-zA-Z0-9]+/", "", $path_parts['extension'] );
	$refactored_data['file_name'] = sanitize_file_name( $path_parts['filename'] );
	$refactored_data['old_file']  = $path_parts['dirname'] . '/' . $refactored_data['file_name'] . '.' . $refactored_data['ext'];
	$refactored_data['file']      = $path_parts['dirname'] . '/' . $refactored_data['file_name'] . '.' . $desired_ext;
	$refactored_data['url']       = preg_replace( '?' . $refactored_data['file_name'] . '.' . $refactored_data['ext'] . '?', $refactored_data['file_name'] . '.' . $desired_ext, $data['url'] );
	$refactored_data['type']      = 'audio/' . $desired_ext;

	return $refactored_data;
}

/**
 * @param $refactored_data
 *
 * Validate that only lossless files will be converted.
 *
 * @return bool
 */
function validated( $refactored_data ) {
	$file_types_to_convert = array(
		'wav',
		'ogg',
		'flac'
	);

	return ( in_array( $refactored_data['ext'], $file_types_to_convert ) );

}