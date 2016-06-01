<?php
namespace Bootapp\Command;

use Bootapp\Command\Table\Cell;
use Bootapp\Command\Table\Wall;
use Bootapp\Command\Table\Coner;

class Table
{
    /**
     * @var string
     */
    public $header = '';

    /**
     * @var array
     */
    public $body = [];

    /**
     * @var int
     */
    public $maxLength = 0;

    /**
     * @param string $str
     */
    public function len($str)
    {
        $len = strlen($str);

        if ($this->maxLength < $len) {
            $this->maxLength = $len;
        }
    }

    /**
     * @param  string  $header
     * @return $this
     */
    public function header($header)
    {
        $this->len($header);
        $this->header = ' '.$header;

        return $this;
    }

    /**
     * @param  string  $body
     * @return $this
     */
    public function addRow($row)
    {
        $this->len($row);
        $this->body[] = ' '.$row;

        return $this;
    }

    /**
     * @param  array   $rows
     * @return $this
     */
    public function addRows($rows)
    {
        $len  = max(array_map('strlen', $rows));
        $rows = array_map(function ($str) {
            return ' '.$str;
        }, $rows);

        if ($this->maxLength < $len) {
            $this->maxLength = $len;
        }

        $this->body = array_merge($this->body, $rows);

        return $this;
    }

    /**
     * @return mixed
     */
    public function render()
    {
        $max    = $this->maxLength + 10;
        $output = '';

        $output .= Coner::topLeft().Wall::horizontal($max).Coner::topRight().PHP_EOL;

        if ($this->header) {
            $output .= Wall::vertical().Wall::blank($max, $this->header, true).Wall::vertical().PHP_EOL;
            $output .= Cell::left().Wall::horizontal($max).Cell::right().PHP_EOL;
        }

        foreach ($this->body as $body) {
            $output .= Wall::vertical().Wall::blank($max, $body).Wall::vertical().PHP_EOL;
        }

        $output .= Coner::bottomLeft().Wall::horizontal($max).Coner::bottomRight().PHP_EOL;

        return $output;
    }
}
