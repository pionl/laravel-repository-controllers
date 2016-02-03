<?php

/**
 * Translate the given message in the repository package
 *
 * @param  string  $id
 * @param  array   $parameters
 * @param  string  $domain
 * @param  string  $locale
 * @return \Symfony\Component\Translation\TranslatorInterface|string
 */
function rpPackageTrans($id = null, $parameters = [], $domain = 'messages', $locale = null) {
    if (is_string($id)) {
        $package = \Pion\Repository\RepositoryServiceProvider::NAME."::".$id;
        return trans($package, $parameters, $domain, $locale);
    } else {
        return trans($id, $parameters, $domain, $locale);
    }

}

/**
 * Adds support for Laravel 5.1 and below. In time will be removed?
 */
if (! function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param  string  $path
     * @return string
     */
    function resource_path($path = '')
    {
        return app()->basePath().DIRECTORY_SEPARATOR.'resources'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}