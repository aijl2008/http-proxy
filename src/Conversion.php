<?php

namespace Ajl;

class Conversion
{
    function convert($html, $mime)
    {
        if (0 === stripos($mime, 'text/html')) {
            return $this->convertHtml($html);
        }
        if (0 === stripos($mime, 'text/css')) {
            return $this->convertCss($html);
        }
        if (0 === stripos($mime, 'application/x-javascript')) {
            return $this->convertJs($html);
        }
        return $html;
    }

    private function convertCss($html)
    {
        return str_replace(
            ['https://', 'http://', 'https:\\/\\/', 'http:\\/\\/'],
            ['/?https://', '/?http://', '/?https:\\/\\/', '/?http:\\/\\/'],
            $html
        );

    }

    private function convertJs($html)
    {
        return str_replace(
            ['https://', 'http://', 'https:\\/\\/', 'http:\\/\\/'],
            ['/?https://', '/?http://', '/?https:\\/\\/', '/?http:\\/\\/'],
            $html
        );
    }


    private function convertHtml($html)
    {
        return str_replace(
            ['https://', 'http://', 'https:\\/\\/', 'http:\\/\\/'],
            ['/?https://', '/?http://', '/?https:\\/\\/', '/?http:\\/\\/'],
            $html
        );
    }
}