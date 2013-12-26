<?php

class NivelEducativo extends ActiveRecord\Model
{
    static $table_name = "niveleducativo";

    static function sorted()
    {
        return self::all(array('select' => 'id,descripcion', 'order' => 'orden'));
    }
}