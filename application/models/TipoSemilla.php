<?php

class TipoSemilla extends ActiveRecord\Model
{
    static $table_name = "tiposemilla";

    static function sorted()
    {
        return self::all(array('select' => 'id,descripcion', 'order' => 'orden'));
    }
}