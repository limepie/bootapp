<?php
namespace Bootapp\Command\Table;

class Cell
{
    public static function left()
    {
        return html_entity_decode('&#x2523;', ENT_NOQUOTES, 'UTF-8');
    }

    public static function right()
    {
        return html_entity_decode('&#x252b;', ENT_NOQUOTES, 'UTF-8');
    }
}
