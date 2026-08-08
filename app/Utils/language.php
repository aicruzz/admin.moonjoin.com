<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

if (!function_exists('translate')) {
    function translate($key): string
    {
        $local = getDefaultLanguage();
        App::setLocale($local);

        try {
            $lang_array = include(base_path('resources/lang/' . $local . '/messages.php'));
            $processed_key = ucfirst(str_replace('_', ' ', removeSpecialCharacters($key)));
            $key = removeSpecialCharacters($key);
            if (!array_key_exists($key, $lang_array)) {
                // RI.2: same persistence policy as the helpers.php implementation.
                // This copy is dead today - app/helpers.php is autoloaded first
                // (composer files index 7 vs 15) so function_exists() short-circuits
                // this one - but an unguarded second writer is a latent regression
                // if that load order ever changes.
                if (config('translation.persist_missing_keys')) {
                    $lang_array[$key] = $processed_key;
                    persist_language_array(base_path('resources/lang/' . $local . '/messages.php'), $lang_array);
                }

                $result = $processed_key;
            } else {
                $result = __('messages.' . $key);
            }
        } catch (\Exception $exception) {
            $result = __('messages.' . $key);
        }

        return $result;
    }
}


if (!function_exists('removeSpecialCharacters')) {

    function removeSpecialCharacters(string $text): string
    {
        return str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', preg_replace('/\s\s+/', ' ', $text));
    }
}

if (!function_exists('getDefaultLanguage')) {
    function getDefaultLanguage(): string
    {
        if (strpos(url()->current(), '/api')) {
            $lang = App::getLocale();
        } elseif (session()->has('local')) {
            $lang = session('local');
        } else {
            $data = getWebConfig('language');
            $code = 'en';
            $direction = 'ltr';
            foreach ($data as $ln) {
                if (array_key_exists('default', $ln) && $ln['default']) {
                    $code = $ln['code'];
                    if (array_key_exists('direction', $ln)) {
                        $direction = $ln['direction'];
                    }
                }
            }
            session()->put('local', $code);
            Session::put('direction', $direction);
            $lang = $code;
        }
        return $lang;
    }
}
