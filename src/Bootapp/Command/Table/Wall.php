<?php
namespace Bootapp\Command\Table;

class Wall
{
    /**
     * @return string
     */
    public static function vertical()
    {
        return html_entity_decode('&#x2503;', ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * @param  int      $max
     * @return string
     */
    public static function horizontal($max)
    {
        $str = html_entity_decode('&#x2501;', ENT_NOQUOTES, 'UTF-8');

        return str_repeat($str, $max);
    }

    /**
     * @param  int          $max
     * @param  string|blank $str
     * @return string
     */
    public static function blank($max, $str = '', $header = false)
    {
        if (true === $header) {
            $text = "\e[1m".$str."\e[0m";
        } else {
            $text = $str;
        }

        return $text.(str_repeat(' ', $max - strlen($str)));
    }
}
