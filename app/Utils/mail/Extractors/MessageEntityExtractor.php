<?php

namespace App\Utils\mail\Extractors;


class MessageEntityExtractor
{
    protected $patterns;
    protected $action;

    public function __construct($action, $regexPatterns)
    {
        $this->action = $action;

        $regexPatterns = is_array($regexPatterns[0]) ? $regexPatterns[0] : $regexPatterns;

        $this->patterns = array_map(function ($pattern) {
            return '/' . trim($pattern, '/') . '/';
        }, $regexPatterns);
    }

    protected function modifyText($text)
    {
        return $text;
    }

    /**
     * @param string $message
     * @return Entity[]
     */
    public function extract($message)
    {
        $entities = [];

        foreach ($this->patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[0] as $match) {
                    $entities[] = new Entity($this->modifyText($match), $this->action);
                }
            }
        }

        return $entities;
    }
}
