<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class PlanVisitas extends CI_Controller {

    public function __construct() {
        parent::__construct();

        check_profile(array("Administrador", "Coordinador", "Digitador", "Consultas"));
    }

    public function index() {

        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div><label class="error">', '</label></div>');

        function to_array($model) {
            return $model->to_array();
        }

        $respuestas_relacion_visitas = array_map('to_array', TipoActividadVisita::all(array('select' => 'id,descripcion', 'order' => 'orden', 'conditions' => array('categoria = ?', 1))));
        $respuestas_visitas = array_map('to_array', TipoActividadVisita::all(array('select' => 'id,descripcion', 'order' => 'orden', 'conditions' => array('categoria = ?', 2))));
        $respuestas_actividades = array_map('to_array', TipoActividadVisita::all(array('select' => 'id,descripcion', 'order' => 'orden', 'conditions' => array('categoria = ?', 3))));


        ///consulto las respuestas si existen en la BD (edicion)
        $respuestas_bd_aux = RespuestaActividadVisita::all();

        //var_dump(count($respuestas_bd_aux));
        ///las acomodo para poder acceder mas facil a cada input
        $respuestas_bd_input = $respuestas_bd = array();
        foreach ($respuestas_bd_aux as $obj) {
            $respuestas_bd[$obj->idtipoactividad] = $obj;
            $respuestas_bd_input['columna_1_' . $obj->id] = $obj->columna1;
            $respuestas_bd_input['columna_2_' . $obj->id] = $obj->columna2;
            $respuestas_bd_input['columna_3_' . $obj->id] = $obj->columna3;
            $respuestas_bd_input['columna_4_' . $obj->id] = $obj->columna4;
            $respuestas_bd_input['columna_5_' . $obj->id] = $obj->columna5;
            $respuestas_bd_input['columna_6_' . $obj->id] = $obj->columna6;
            $respuestas_bd_input['columna_7_' . $obj->id] = $obj->columna7;
            $respuestas_bd_input['columna_8_' . $obj->id] = $obj->columna8;
            $respuestas_bd_input['columna_7_' . $obj->id] = $obj->columna9;
            $respuestas_bd_input['columna_8_' . $obj->id] = $obj->columna10;
        }


        ///creo las reglas para los inputs
        $arr_aux = array_merge($respuestas_relacion_visitas, $respuestas_visitas, $respuestas_actividades);
        foreach ($arr_aux as $value) {
            for ($i = 1; $i <= 10; $i++) {
                $nombreInput = "columna_{$i}_{$value->id}";
                $this->form_validation->set_rules($nombreInput, ' ', 'required|numeric');
            }
        }

        if ($this->form_validation->run()) {

            ///itero por todas las actividades
            foreach ($arr_aux as $value) {

                ///si ya tiene datos edito, si no, creo
                $objRespuestaActividadVisita = isset($respuestas_bd[$value->id]) ? $respuestas_bd[$value->id] : new RespuestaActividadVisita;
                $objRespuestaActividadVisita->idtipoactividad = $value->id;
                $objRespuestaActividadVisita->columna1 = $this->input->post("columna_1_{$value->id}");
                $objRespuestaActividadVisita->columna2 = $this->input->post("columna_2_{$value->id}");
                $objRespuestaActividadVisita->columna3 = $this->input->post("columna_3_{$value->id}");
                $objRespuestaActividadVisita->columna4 = $this->input->post("columna_4_{$value->id}");
                $objRespuestaActividadVisita->columna5 = $this->input->post("columna_5_{$value->id}");
                $objRespuestaActividadVisita->columna6 = $this->input->post("columna_6_{$value->id}");
                $objRespuestaActividadVisita->columna7 = $this->input->post("columna_7_{$value->id}");
                $objRespuestaActividadVisita->columna8 = $this->input->post("columna_8_{$value->id}");
                $objRespuestaActividadVisita->columna9 = $this->input->post("columna_9_{$value->id}");
                $objRespuestaActividadVisita->columna10 = $this->input->post("columna_10_{$value->id}");

                $objRespuestaActividadVisita->save();
            }
        }

        $nombres_columnas['columna_1'] = 'Total Visitas-Meta';
        $nombres_columnas['columna_2'] = 'Avance Productores';
        $nombres_columnas['columna_3'] = 'Corte Diciembre 2013';
        $nombres_columnas['columna_4'] = 'Ene-14';
        $nombres_columnas['columna_5'] = 'Feb-14';
        $nombres_columnas['columna_6'] = 'Mar-14';
        $nombres_columnas['columna_7'] = 'Abr-14';
        $nombres_columnas['columna_8'] = 'May-14';
        $nombres_columnas['columna_9'] = 'Jun-14';
        $nombres_columnas['columna_10'] = 'Jul-14';

        // Aunque el nombre es "respuestas" referencian las preguntas
        $this->twiggy->set('respuestas_relacion_visitas', $respuestas_relacion_visitas);
        $this->twiggy->set('respuestas_visitas', $respuestas_visitas);
        $this->twiggy->set('respuestas_actividades', $respuestas_actividades);
        $this->twiggy->set('nombres_columnas', $nombres_columnas);
        $this->twiggy->set('respuestas_bd_input', $respuestas_bd_input);

        $this->twiggy->template("informes/planvisitas");
        $this->twiggy->display();
    }

}
