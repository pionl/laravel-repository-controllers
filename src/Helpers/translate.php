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
        $package = RepositoryServiceProvider::NAME."::".$id;
        return trans($package, $parameters, $domain, $locale);
    } else {
        return trans($id, $parameters, $domain, $locale);
    }

}