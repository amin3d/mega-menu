<?php
/**
 * Returns an array containing all values in $data with the key of $key
 * @param string The key to return values for
 * @param array|object The array or object to search for $key in
 * @return array
 * @see http://stackoverflow.com/a/10660002
 * @author wilmoore
 * @author firejdl
 */
function array_pluck( $key, $data ) {
	return array_reduce( $data, function( $result, $array ) use( $key ) {
		is_array( $array ) && isset( $array[ $key ] ) && $result[] = $array[ $key ];
		is_object( $array ) && isset( $array->$key ) && $result[] = $array->$key;

		return $result;
	}, array() );
}
