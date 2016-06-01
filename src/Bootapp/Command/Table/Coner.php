<?php
namespace Bootapp\Command\Table;

class Coner
{
    public static function topLeft()
    {
        return html_entity_decode('&#x250f;', ENT_NOQUOTES, 'UTF-8');
    }

    public static function topRight()
    {
        return html_entity_decode('&#x2513;', ENT_NOQUOTES, 'UTF-8');
    }

    public static function bottomLeft()
    {
        return html_entity_decode('&#x2517;', ENT_NOQUOTES, 'UTF-8');
    }

    public static function bottomRight()
    {
        return html_entity_decode('&#x251b;', ENT_NOQUOTES, 'UTF-8');
    }
}
