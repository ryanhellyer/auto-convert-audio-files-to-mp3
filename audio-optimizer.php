<?php

namespace AudioOptimizer;

/*
Plugin Name: Audio Optimizer
Description: Converts audio files from WAV to MP3
Version: 1.1.1
Author: Ryan Hellyer, Grayson Erhard
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

	// 160 kbps is the lowest bitrate you can have with the least audible compression difference.
	$bit_rate        = 160000;
	$bit_depth       = 's16'; // run 'ffmpeg -sample_fmts' to see all available bit depth settings.
	$sample_rate     = 44100; // Industry standard.
	$refactored_data = refactor( $data ); // Change file names, check for duplicates.

	// Validate against unwanted filetypes.
	if ( ! validated( $refactored_data ) ) {
		return $data;
	}

	$command = 'ffmpeg -i ' . $refactored_data['old_file'];
	$command .= ' -b:a ' . $bit_rate;
	$command .= ' -sample_rate ' . $sample_rate;
//	$command .= ' -sample_fmt ' . $bit_depth;
	$command .= ' ' . $refactored_data['file'];

	$result = shell_exec( $command );

	unlink( $refactored_data['old_file'] ); // Delete old file.

	return $refactored_data; // Return to WordPress Media.
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
	// New file needs to be in the array as "file" for the WordPress Media manager not to error out.
	// increment_file_name() checks for duplicates and appends a number if one exists.
	$refactored_data['file'] = increment_file_name( $path_parts['dirname'], $refactored_data['file_name'] . '.' . $desired_ext );
	$refactored_data['url']  = preg_replace( '?' . $refactored_data['file_name'] . '.' . $refactored_data['ext'] . '?', $refactored_data['file_name'] . '.' . $desired_ext, $data['url'] );
	$refactored_data['type'] = 'audio/' . $desired_ext;

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

function increment_file_name( $file_path, $filename ) {
	$array     = explode( ".", $filename );
	$file_ext  = end( $array );
	$root_name = str_replace( ( '.' . $file_ext ), "", $filename );
	$file      = trailingslashit( $file_path ) . $filename;
	$i         = 1;
	while( file_exists( $file ) ) {
		$file = trailingslashit( $file_path ) . $root_name . '-' . $i . '.' . $file_ext;
		$i ++;
	}

	return $file;
}