<?php

class TipoServicioPublico extends ActiveRecord\Model
{
    static $table_name = "tiposerviciopublico";

    static function sorted()
    {
        return self::all(array('select' => 'id,descripcion', 'order' => 'orden'));
    }
}