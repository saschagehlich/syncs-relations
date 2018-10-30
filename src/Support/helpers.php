<?php
if (! function_exists('array_is_assoc')) {
    /**
     * Checks whether the given array is an associated array
     *
     * @param  array  $array
     * @return bool
     */
    function array_is_assoc(array $array)
    {
        foreach(array_keys($array) as $key) {
            if (!is_int($key)) return true;
        }
	    return false;
    }
}