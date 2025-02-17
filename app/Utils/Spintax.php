<?php

namespace App\Utils;

class Spintax
{
    /**
     * Set seed to make the spinner predictable.
     */
    public function seed(mixed $seed)
    {
        mt_srand(crc32($seed));
    }

    public function process(string $text)
    {
        return preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*?)\}/x',
            function ($match) {
                $text = $this->process($match[1]);
                $parts = explode('|', $text);

                return $parts[mt_rand(0, count($parts) - 1)];
            },
            $text
        );
    }

    /**
     * If you want highest randomness,
     * this is the method to use.
     * Note: Seed has no effect.
     *
     * Last updated: 2023-10-26
     */
    public function spin(string $text)
    {
        $pattern = '/{([^{}]+)}/';
        while (preg_match($pattern, $text)) {
            $text = preg_replace_callback($pattern, function ($matches) {
                $options = explode('|', $matches[1]);

                return $options[random_int(0, count($options) - 1)];
            }, $text);
        }

        return $text;
    }
}
