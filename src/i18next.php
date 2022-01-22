<?php

/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <github.com/Mika-> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return
 * ----------------------------------------------------------------------------
 */

namespace samerton\i18next;

class i18next {

    /**
     * Path for the translation files
     * @var string Path
     */
    private string $_path = '';

    /**
     * Primary language to use
     * @var string Code for the current language
     */
    private string $_language = '';

    /**
     * Fallback language for translations not found in current language
     * @var string Fallback language
     */
    private string $_fallbackLanguage = 'dev';

    /**
     * Array to store the translations
     * @var array Translations
     */
    private array $_translation = [];

    /**
     * Logs keys for missing translations
     * @var array Missing keys
     */
    private array $_missingTranslation = [];

    /**
     * Inits i18next class
     * Path may include __lng___ and __ns__ placeholders so all languages and namespaces are loaded
     *
     * @param string $language Locale language code
     * @param ?string $path Path to locale json files
     * @param ?string $fallback Optional fallback language code
     *
     * @throws \Exception via loadTranslation
     */
    public function __construct(string $language = 'en', string $path = null, string $fallback = null) {
        $this->_language = $language;
        $this->_path = $path;

        if (!empty($fallback))
            $this->_fallbackLanguage = $fallback;

        $this->loadTranslation();
    }

    /**
     * Change default language and fallback language
     * If fallback is not set it is left unchanged
     *
     * @param string $language New default language
     * @param ?string $fallback Fallback language
     */
    public function setLanguage(string $language, string $fallback = null) {

        $this->_language = $language;

        if (!empty($fallback))
            $this->_fallbackLanguage = $fallback;

    }

    /**
     * Get list of missing translations
     *
     * @return array Missing translations
     */
    public function getMissingTranslations(): array {

        return $this->_missingTranslation;

    }

    /**
     * Check if translated string is available
     *
     * @param string $key Key for translation
     * @return boolean Stating the result
     */
    public function existTranslation(string $key) {

        $return = $this->_getKey($key);

        if ($return)
            $return = true;

        return $return;

    }

    /**
     * Get translation for given key
     *
     * @param string $key Key for the translation
     * @param array $variables Variables
     * @return mixed Translated string or array
     */
    public function getTranslation(string $key, array $variables = array()) {

        $return = $this->_getKey($key, $variables);

        // Log missing translation
        if (!$return && array_key_exists('lng', $variables))
            array_push($this->_missingTranslation, array('language' => $variables['lng'], 'key' => $key));

        else if (!$return)
            array_push($this->_missingTranslation, array('language' => $this->_language, 'key' => $key));

        // fallback language check
        if (!$return && !isset($variables['lng']) && !empty($this->_fallbackLanguage))
            $return = $this->_getKey($key, array_merge($variables, array('lng'=>  $this->_fallbackLanguage)));

        if (!$return && array_key_exists('defaultValue', $variables))
            $return = $variables['defaultValue'];

        if ($return && isset($variables['postProcess']) && $variables['postProcess'] === 'sprintf' && isset($variables['sprintf'])) {

            if (is_array($variables['sprintf']))
                $return = vsprintf($return, $variables['sprintf']);

            else
                $return = sprintf($return, $variables['sprintf']);

        }

        if (!$return)
            $return = $key;

        foreach ($variables as $variable => $value) {

            if (is_string($value) || is_numeric($value)) {
                $return = preg_replace('/__' . $variable . '__/', $value, $return);
                $return = preg_replace('/{{' . $variable . '}}/', $value, $return);
            }

        }

        return $return;

    }

    /**
     * Loads translation(s)
     * @throws \Exception
     */
    private function loadTranslation() {

        $path = preg_replace('/__(.+?)__/', '*', $this->_path, 2, $hasNs);

        if (!preg_match('/\.json$/', $path)) {

            $path = $path . 'translation.json';

            $this->_path = $this->_path . 'translation.json';

        }

        $dir = glob($path);

        if (count($dir) === 0)
            throw new \Exception('Translation file not found');

        foreach ($dir as $file) {

            $translation = file_get_contents($file);

            $translation = json_decode($translation, true);

            if ($translation === null)
                throw new \Exception('Invalid json ' . $file);

            if ($hasNs) {

                $regexp = preg_replace('/__(.+?)__/', '(?<$1>.+)?', preg_quote($this->_path, '/'));

                preg_match('/^' . $regexp . '$/', $file, $ns);

                if (!array_key_exists('lng', $ns))
                    $ns['lng'] = $this->_language;

                if (array_key_exists('ns', $ns)) {

                    if (array_key_exists($ns['lng'], $this->_translation) && array_key_exists($ns['ns'], $this->_translation[$ns['lng']]))
                        $this->_translation[$ns['lng']][$ns['ns']] = array_merge($this->_translation[$ns['lng']][$ns['ns']], array($ns['ns'] => $translation));

                    else if (array_key_exists($ns['lng'], $this->_translation))
                        $this->_translation[$ns['lng']] = array_merge($this->_translation[$ns['lng']], array($ns['ns'] => $translation));

                    else
                        $this->_translation[$ns['lng']] = array($ns['ns'] => $translation);

                }
                else {

                    if (array_key_exists($ns['lng'], $this->_translation))
                        $this->_translation[$ns['lng']] = array_merge($this->_translation[$ns['lng']], $translation);

                    else
                        $this->_translation[$ns['lng']] = $translation;

                }

            }
            else {

                if (array_key_exists($this->_language, $translation))
                    $this->_translation = $translation;

                else
                    $this->_translation = array_merge($this->_translation, $translation);

            }

        }

    }

    /**
     * Get translation for given key
     *
     * Translation is looked up in language specified in $variables['lng'], current language or Fallback language - in this order.
     * Fallback language is used only if defined and no explicit language was specified in $variables
     *
     * @param string $key Key for translation
     * @param array $variables Variables
     * @return mixed Translated string or array if requested. False if translation doesn't exist
     */
    private function _getKey(string $key, array $variables = array()) {

        $return = false;

        if (array_key_exists('lng', $variables) && array_key_exists($variables['lng'], $this->_translation))
            $translation = $this->_translation[$variables['lng']];

        else if (array_key_exists($this->_language, $this->_translation))
            $translation = $this->_translation[$this->_language];

        else
            $translation = $this->_translation;

        // path traversal - last array will be response
        $paths_arr = explode('.', $key);

        while ($path = array_shift($paths_arr)) {

            if (array_key_exists($path, $translation) && is_array($translation[$path]) && count($paths_arr) > 0) {

                $translation = $translation[$path];

            }
            else if (array_key_exists($path, $translation)) {

                // Request has context
                if (array_key_exists('context', $variables)) {

                    if (array_key_exists($path . '_' . $variables['context'], $translation))
                        $path = $path . '_' . $variables['context'];

                }

                // Request is plural form
                // TODO: implement more complex i18next handling
                if (array_key_exists('count', $variables)) {

                    if ($variables['count'] != 1 && array_key_exists($path . '_plural_' . $variables['count'], $translation))
                        $path = $path . '_plural' . $variables['count'];

                    else if ($variables['count'] != 1 && array_key_exists($path . '_plural', $translation))
                        $path = $path . '_plural';

                }

                $return = $translation[$path];

                break;

            }
            else {

                return false;

            }

        }

        if (is_array($return) && isset($variables['returnObjectTrees']) && $variables['returnObjectTrees'] === true)
            $return = $return;

        else if (is_array($return) && array_keys($return) === range(0, count($return) - 1))
            $return = implode("\n", $return);

        else if (is_array($return))
            return false;

        return $return;

    }

}
