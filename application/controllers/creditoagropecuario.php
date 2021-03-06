<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Creditoagropecuario extends CI_Controller {
    
    private $aGruposConDatos = array();

    public function __construct() {
        parent::__construct();

        //check_profile(array("Administrador", "Coordinador", "Digitador", "Consultas"));
    }

    public function index($ruat_id = NULL) {

        function to_array($model) {
            return $model->to_array();
        }

        $ruat = Ruat::find($ruat_id);

        if (!$ruat)
            show_404();

        /*echo("<pre>");
        var_dump($this->input->post());
        echo("</pre>");
        die;*/

        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div><label class="error">', '</label></div>');

        $productor = $ruat->productor;
        $contacto = $productor->contacto;
        $renglon = $productor->renglon_productivo;
        $finca = $ruat->finca;
        $finca->tenencia;
        $finca->municipio;
        $finca->municipio->departamento;

        $data = array();

        $data['tiposDocumento'] = array_map('to_array', TipoDocumento::sorted());

        $deptos = Departamento::all(array('order' => 'nombre', 'include' => array('municipios')));
        $deptos_municipios = array();
        foreach ($deptos as $depto) {
            $deptos_municipios[$depto->id] = array('nombre' => $depto->nombre, 'municipios' => array());
            foreach ($depto->municipios as $mun)
                $deptos_municipios[$depto->id]['municipios'][] = array('id' => $mun->id, 'nombre' => $mun->nombre);
        }

        $data['departamentos'] = $deptos_municipios;

        $solicitud_credito = SolicitudCredito::first(array(
                    'conditions' => array("ruat_id = ?", $ruat_id)
        ));
        
        $vtp = VisitaTipoProductor::first(array(
                    'conditions' => array('ruat_id = ?', $ruat_id)
        ));

        $conyugue = $solicitud_credito->conyugue;
        $referencias_familiares = $solicitud_credito->referencias_familiares;
        $referencias_personales = $solicitud_credito->referencias_personales;
        $referencias_financieras = $solicitud_credito->referencias_financieras;
        $referencias_comerciales = $solicitud_credito->referencias_comerciales;
        $descripcion_inversiones = $solicitud_credito->descripcion_inversiones;
        $informacion_predios_inversion = $solicitud_credito->informacion_predios_inversion;
        $ingresos_adicionales = $solicitud_credito->ingresos_adicionales;
        $descripcion_bienes = $solicitud_credito->descripcion_bienes;
        $descripcion_bienes_inmuebles = $solicitud_credito->descripcion_bienes_inmuebles;
        $lista_chequeo = $solicitud_credito->lista_chequeo;
        
        ///consulto las preguntas C para cargarlas dinamicamente
        $preguntas_c = TPCPregunta::all(array('order' => 'categoria, ordenamiento'));

        ///consulo las respuestas C
        $respuestas_c = TPCRespuesta::all(array(
                    'conditions' => array('visita_id = ?', $vtp->id)
        ));
        
        ///acomodo las respuestas con la pregunta como llave
        $respuestas_c_aux = array();
        foreach ($respuestas_c as $obj)
            $respuestas_c_aux[$obj->pregunta_c_id] = $obj;

        ///acomodo las preguntas C por categoria
        $preguntas_ingresos = $preguntas_egresos = $preguntas_activos = $preguntas_totales = array();
        foreach ($preguntas_c as $obj) {

            $objAux = $obj->to_array();

            ///Si la pregunta C tiene respuesta, la agrego al objeto de la pregunta
            if (isset($respuestas_c_aux[$obj->id]))
                $objAux['respuesta_bd'] = $respuestas_c_aux[$obj->id]->valor;

            if ($obj->categoria === 'A')
                $preguntas_activos[] = $objAux;
            elseif ($obj->categoria === 'B')
                $preguntas_ingresos[] = $objAux;
            elseif ($obj->categoria === 'C')
                $preguntas_egresos[] = $objAux;
            elseif ($obj->categoria === 'D')
                $preguntas_totales[] = $objAux;
        }
        
        $this->validar_input();
        $this->validation_rules();

        ///Si las validaciones son correctas procedo a guardar
        if ($this->form_validation->run()) {

            $connection = Ruat::connection();

            try {

                $connection->transaction();

                $solicitud_credito = ($solicitud_credito) ? $solicitud_credito : new SolicitudCredito();

                $solicitud_credito->ruat_id = $ruat_id;
                $solicitud_credito->fecha = $this->input->post('sc_fecha');
                $solicitud_credito->cod_beneficiario = $this->input->post('cod_beneficiario');
                $solicitud_credito->nombre_oficina = $this->input->post('nombre_oficina');
                $solicitud_credito->departamento_id = $this->input->post('banco_agrario_departamento');
                $solicitud_credito->municipio_id = $this->input->post('banco_agrario_municipio');
                $solicitud_credito->tipo_productor = $this->input->post('solicitud_tipo_productor');
                $solicitud_credito->experiencia = $this->input->post('experiencia');
                $solicitud_credito->calidad_de = $this->input->post('calidad_de');
                $solicitud_credito->rubros_fin_icr = $this->input->post('solicitud_credito_icr');
                $solicitud_credito->rubros_fin_dre = $this->input->post('solicitud_credito_dre');
                $solicitud_credito->descripcion_inv = $this->input->post('descripcion_inv');

                $solicitud_credito->actividades_agro = $this->input->post('actividades_agro');
                $solicitud_credito->otro_ingresos = $this->input->post('otro_ingresos');
                $solicitud_credito->comercializacion = $this->input->post('comercializacion');
                $solicitud_credito->entidades_acomp = $this->input->post('entidades_acomp');

                $solicitud_credito->forma_llegar_pred = $this->input->post('forma_llegar_pred');
                $solicitud_credito->tiempo_permanencia = $this->input->post('tiempo_permanencia');
                $solicitud_credito->experiencia_act = $this->input->post('experiencia_act');
                $solicitud_credito->responsable = 1; ///OJO! Poner el usuario
                $solicitud_credito->observaciones = $this->input->post('observaciones');

                if ($solicitud_credito->save()) {
//                  var_dump($solicitud_credito);
                    $conyugue = ($conyugue) ? $conyugue : new Conyugue;
                    $conyugue->solicitud_id = $solicitud_credito->id;
                    $conyugue->nombre1 = $this->input->post('conyugue_nombre1');
                    $conyugue->nombre2 = $this->input->post('conyugue_nombre2');
                    $conyugue->apellido1 = $this->input->post('conyugue_apellido1');
                    $conyugue->apellido2 = $this->input->post('conyugue_apellido2');
                    $conyugue->tipo_documento = $this->input->post('tipo_documento_conyugue');
                    $conyugue->identificacion = $this->input->post('conyugue_identificacion');
                    $conyugue->fecha_nacimiento = $this->input->post('conyugue_fecha_nacimiento');
                    $conyugue->telefono = $this->input->post('conyugue_telefono');
                    $conyugue->personas_cargo = $this->input->post('conyugue_personas_cargo');
                    $conyugue->save();

//                  echo 'referencias_fam1_nombres';
                    $referencias_familiares[0] = (isset($referencias_familiares[0]) && $referencias_familiares[0]) ? $referencias_familiares[0] : new ReferenciaFamiliarPersonal;
                    $referencias_familiares[0]->tipo = 1;
                    $referencias_familiares[0]->solicitud_id = $solicitud_credito->id;
                    $referencias_familiares[0]->nombres = $this->input->post('referencias_fam1_nombres');
                    $referencias_familiares[0]->apellido1 = $this->input->post('referencias_fam1_apellido1');
                    $referencias_familiares[0]->apellido2 = $this->input->post('referencias_fam1_apellido2');
                    $referencias_familiares[0]->parentesco = $this->input->post('referencias_fam1_parentesco');
                    $referencias_familiares[0]->direccion = $this->input->post('referencias_fam1_per_direccion');
                    $referencias_familiares[0]->departamento_id = $this->input->post('referencias_fam1_departamento');
                    $referencias_familiares[0]->municipio_id = $this->input->post('referencias_fam1_municipio');
                    $referencias_familiares[0]->barrio = $this->input->post('referencias_fam1_barrio');
                    $referencias_familiares[0]->indicativo1 = $this->input->post('referencias_fam1_indicativo1');
                    $referencias_familiares[0]->telefono1 = $this->input->post('referencias_fam1_telefono1');
                    $referencias_familiares[0]->indicativo2 = $this->input->post('referencias_fam1_indicativo2');
                    $referencias_familiares[0]->telefono2 = $this->input->post('referencias_fam1_telefono2');
                    $referencias_familiares[0]->indicativo3 = $this->input->post('referencias_fam1_indicativo3');
                    $referencias_familiares[0]->telefono3 = $this->input->post('referencias_fam1_telefono3');
                    if ($this->input->post('referencias_fam1_nombres')) {
                        $referencias_familiares[0]->save();
                    }

//                  echo 'referencias_fam2_nombres';
                    $referencias_familiares[1] = (isset($referencias_familiares[1]) && $referencias_familiares[1]) ? $referencias_familiares[1] : new ReferenciaFamiliarPersonal;
                    $referencias_familiares[1]->tipo = 1;
                    $referencias_familiares[1]->solicitud_id = $solicitud_credito->id;
                    $referencias_familiares[1]->nombres = $this->input->post('referencias_fam2_nombres');
                    $referencias_familiares[1]->apellido1 = $this->input->post('referencias_fam2_apellido1');
                    $referencias_familiares[1]->apellido2 = $this->input->post('referencias_fam2_apellido2');
                    $referencias_familiares[1]->parentesco = $this->input->post('referencias_fam2_parentesco');
                    $referencias_familiares[1]->direccion = $this->input->post('referencias_fam2_per_direccion');
                    $referencias_familiares[1]->departamento_id = $this->input->post('referencias_fam2_departamento');
                    $referencias_familiares[1]->municipio_id = $this->input->post('referencias_fam2_municipio');
                    $referencias_familiares[1]->barrio = $this->input->post('referencias_fam2_barrio');
                    $referencias_familiares[1]->indicativo1 = $this->input->post('referencias_fam2_indicativo1');
                    $referencias_familiares[1]->telefono1 = $this->input->post('referencias_fam2_telefono1');
                    $referencias_familiares[1]->indicativo2 = $this->input->post('referencias_fam2_indicativo2');
                    $referencias_familiares[1]->telefono2 = $this->input->post('referencias_fam2_telefono2');
                    $referencias_familiares[1]->indicativo3 = $this->input->post('referencias_fam2_indicativo3');
                    $referencias_familiares[1]->telefono3 = $this->input->post('referencias_fam2_telefono3');
                    if ($this->input->post('referencias_fam2_nombres')) {
                        $referencias_familiares[1]->save();
                    }

//                  echo 'referencias_per1_nombres';
                    $referencias_personales[0] = (isset($referencias_personales[0]) && $referencias_personales[0]) ? $referencias_personales[0] : new ReferenciaFamiliarPersonal;
                    $referencias_personales[0]->tipo = 2;
                    $referencias_personales[0]->solicitud_id = $solicitud_credito->id;
                    $referencias_personales[0]->nombres = $this->input->post('referencias_per1_nombres');
                    $referencias_personales[0]->apellido1 = $this->input->post('referencias_per1_apellido1');
                    $referencias_personales[0]->apellido2 = $this->input->post('referencias_per1_apellido2');
                    $referencias_personales[0]->parentesco = $this->input->post('referencias_per1_parentesco');
                    $referencias_personales[0]->direccion = $this->input->post('referencias_per1_per_direccion');
                    $referencias_personales[0]->departamento_id = $this->input->post('referencias_per1_departamento');
                    $referencias_personales[0]->municipio_id = $this->input->post('referencias_per1_municipio');
                    $referencias_personales[0]->barrio = $this->input->post('referencias_per1_barrio');
                    $referencias_personales[0]->indicativo1 = $this->input->post('referencias_per1_indicativo1');
                    $referencias_personales[0]->telefono1 = $this->input->post('referencias_per1_telefono1');
                    $referencias_personales[0]->indicativo2 = $this->input->post('referencias_per1_indicativo2');
                    $referencias_personales[0]->telefono2 = $this->input->post('referencias_per1_telefono2');
                    $referencias_personales[0]->indicativo3 = $this->input->post('referencias_per1_indicativo3');
                    $referencias_personales[0]->telefono3 = $this->input->post('referencias_per1_telefono3');
                    if ($this->input->post('referencias_per1_nombres')) {
                        $referencias_personales[0]->save();
                    }

//                  echo 'referencias_per2_nombres';
                    $referencias_personales[1] = (isset($referencias_personales[1]) && $referencias_personales[1]) ? $referencias_personales[1] : new ReferenciaFamiliarPersonal;
                    $referencias_personales[1]->tipo = 2;
                    $referencias_personales[1]->solicitud_id = $solicitud_credito->id;
                    $referencias_personales[1]->nombres = $this->input->post('referencias_per2_nombres');
                    $referencias_personales[1]->apellido1 = $this->input->post('referencias_per2_apellido1');
                    $referencias_personales[1]->apellido2 = $this->input->post('referencias_per2_apellido2');
                    $referencias_personales[1]->parentesco = $this->input->post('referencias_per2_parentesco');
                    $referencias_personales[1]->direccion = $this->input->post('referencias_per2_per_direccion');
                    $referencias_personales[1]->departamento_id = $this->input->post('referencias_per2_departamento');
                    $referencias_personales[1]->municipio_id = $this->input->post('referencias_per2_municipio');
                    $referencias_personales[1]->barrio = $this->input->post('referencias_per2_barrio');
                    $referencias_personales[1]->indicativo1 = $this->input->post('referencias_per2_indicativo1');
                    $referencias_personales[1]->telefono1 = $this->input->post('referencias_per2_telefono1');
                    $referencias_personales[1]->indicativo2 = $this->input->post('referencias_per2_indicativo2');
                    $referencias_personales[1]->telefono2 = $this->input->post('referencias_per2_telefono2');
                    $referencias_personales[1]->indicativo3 = $this->input->post('referencias_per2_indicativo3');
                    $referencias_personales[1]->telefono3 = $this->input->post('referencias_per2_telefono3');
                    if ($this->input->post('referencias_per2_nombres')) {
                        $referencias_personales[1]->save();
                    }

//                  echo 'referencias_fin1_entidad';
                    $referencias_financieras[0] = (isset($referencias_financieras[0]) && $referencias_financieras[0]) ? $referencias_financieras[0] : new ReferenciaFinanciera;
                    $referencias_financieras[0]->solicitud_id = $solicitud_credito->id;
                    $referencias_financieras[0]->entidad = $this->input->post('referencias_fin1_entidad');
                    $referencias_financieras[0]->clase = $this->input->post('referencias_fin1_clase');
                    $referencias_financieras[0]->nro_producto = $this->input->post('referencias_fin1_nro_producto');
                    $referencias_financieras[0]->sucursal = $this->input->post('referencias_fin1_sucursal');
                    $referencias_financieras[0]->departamento_id = $this->input->post('referencias_fin1_departamento');
                    $referencias_financieras[0]->municipio_id = $this->input->post('referencias_fin1_municipio');
                    if ($this->input->post('referencias_fin1_entidad')) {
                        $referencias_financieras[0]->save();
                    }

//                  echo 'referencias_fin2_entidad';
                    $referencias_financieras[1] = (isset($referencias_financieras[1]) && $referencias_financieras[1]) ? $referencias_financieras[1] : new ReferenciaFinanciera;
                    $referencias_financieras[1]->solicitud_id = $solicitud_credito->id;
                    $referencias_financieras[1]->entidad = $this->input->post('referencias_fin2_entidad');
                    $referencias_financieras[1]->clase = $this->input->post('referencias_fin2_clase');
                    $referencias_financieras[1]->nro_producto = $this->input->post('referencias_fin2_nro_producto');
                    $referencias_financieras[1]->sucursal = $this->input->post('referencias_fin2_sucursal');
                    $referencias_financieras[1]->departamento_id = $this->input->post('referencias_fin2_departamento');
                    $referencias_financieras[1]->municipio_id = $this->input->post('referencias_fin2_municipio');
                    if ($this->input->post('referencias_fin2_entidad')) {
                        $referencias_financieras[1]->save();
                    }

//                  echo 'referencias_com_nombre_est';
                    $referencias_comerciales[0] = (isset($referencias_comerciales[0]) && $referencias_comerciales[0]) ? $referencias_comerciales[0] : new ReferenciaComercial;
                    $referencias_comerciales[0]->solicitud_id = $solicitud_credito->id;
                    $referencias_comerciales[0]->nombre_est = $this->input->post('referencias_com_nombre_est');
                    $referencias_comerciales[0]->tipo_vinculo = $this->input->post('referencias_com_tipo_vinculo');
                    $referencias_comerciales[0]->departamento_id = $this->input->post('referencias_com_departamento');
                    $referencias_comerciales[0]->municipio_id = $this->input->post('referencias_com_municipio');
                    $referencias_comerciales[0]->telefono = $this->input->post('referencias_com_telefono');
                    if ($this->input->post("referencias_com_nombre_est")) {
                        $referencias_comerciales[0]->save();
                    }

                    /*for ($i = 0; $i < 4; $i++) {
                        $j = $i + 1;

                        $descripcion_inversiones[$i] = (isset($descripcion_inversiones[$i]) && $descripcion_inversiones[$i]) ? $descripcion_inversiones[$i] : new DescripcionInversion;
                        $descripcion_inversiones[$i]->solicitud_id = $solicitud_credito->id;
                        $descripcion_inversiones[$i]->codigo_finagro = $this->input->post("descripcion_inv_{$j}_1");
                        $descripcion_inversiones[$i]->destino_recursos_nombre_rubro = $this->input->post("descripcion_inv_{$j}_2");
                        $descripcion_inversiones[$i]->unidades_fin = $this->input->post("descripcion_inv_{$j}_3");
                        $descripcion_inversiones[$i]->valor_proyecto = $this->input->post("descripcion_inv_{$j}_4");
                        $descripcion_inversiones[$i]->valor_solicitud = $this->input->post("descripcion_inv_{$j}_5");
                        $descripcion_inversiones[$i]->plazo_total = $this->input->post("descripcion_inv_{$j}_6");
                        $descripcion_inversiones[$i]->periodo_gracia = $this->input->post("descripcion_inv_{$j}_7");
                        $descripcion_inversiones[$i]->modalidad_pago = $this->input->post("descripcion_inv_{$j}_8");
                        $descripcion_inversiones[$i]->amortizacion_cap = $this->input->post("descripcion_inv_{$j}_9");
                        $descripcion_inversiones[$i]->tasa_interes = $this->input->post("descripcion_inv_{$j}_10");

                        if ($this->input->post("descripcion_inv_{$j}_1")) {
                            $descripcion_inversiones[$i]->save();
                        }
                    }*/

                    $informacion_predios_inversion[0] = (isset($informacion_predios_inversion[0]) && $informacion_predios_inversion[0]) ? $informacion_predios_inversion[0] : new PredioInversion;
                    $informacion_predios_inversion[0]->solicitud_id = $solicitud_credito->id;
                    $informacion_predios_inversion[0]->fuente_hid = $this->input->post('informacion_pre_fuente_hid');
                    $informacion_predios_inversion[0]->fecha_ini = $this->input->post('informacion_pre_fecha_ini');
                    $informacion_predios_inversion[0]->fecha_fin = $this->input->post('informacion_pre_fecha_fin');
                    $informacion_predios_inversion[0]->save();

                    /*for ($i = 0; $i < 5; $i++) {
                        $j = $i + 1;
                        $ingresos_adicionales[$i] = (isset($ingresos_adicionales[$i]) && $ingresos_adicionales[$i]) ? $ingresos_adicionales[$i] : new IngresosAdicionales;
                        $ingresos_adicionales[$i]->solicitud_id = $solicitud_credito->id;
                        $ingresos_adicionales[$i]->actividad = $this->input->post("ingresos_adicionales_{$j}_1");
                        $ingresos_adicionales[$i]->cantidad = $this->input->post("ingresos_adicionales_{$j}_2");
                        $ingresos_adicionales[$i]->produccion = $this->input->post("ingresos_adicionales_{$j}_3");
                        $ingresos_adicionales[$i]->edad_meses = $this->input->post("ingresos_adicionales_{$j}_4");
                        $ingresos_adicionales[$i]->precio_venta = $this->input->post("ingresos_adicionales_{$j}_5");
                        $ingresos_adicionales[$i]->total_ingresos = $this->input->post("ingresos_adicionales_{$j}_6");
                        $ingresos_adicionales[$i]->nombre_predio = $this->input->post("ingresos_adicionales_{$j}_7");
                        $ingresos_adicionales[$i]->area_pre_inv = $this->input->post("ingresos_adicionales_{$j}_8");
                        $ingresos_adicionales[$i]->tipo_pre_inv = $this->input->post("ingresos_adicionales_{$j}_9");
                        $ingresos_adicionales[$i]->save();
                    }*/

                    /*$descripcion_bienes[0] = (isset($descripcion_bienes[0]) && $descripcion_bienes[0]) ? $descripcion_bienes[0] : new DescripcionBienes;
                    $descripcion_bienes[0]->solicitud_id = $solicitud_credito->id;

                    $descripcion_bienes[0]->marca = $this->input->post("vehiculo_marca");
                    $descripcion_bienes[0]->placa = $this->input->post("vehiculo_placa");
                    $descripcion_bienes[0]->modelo = $this->input->post("vehiculo_modelo");
                    $descripcion_bienes[0]->prenda_favor = $this->input->post("vehiculo_prenda_favor");
                    $descripcion_bienes[0]->valor_deuda = $this->input->post("vehiculo_valor_deuda");
                    $descripcion_bienes[0]->valor_comercial = $this->input->post("vehiculo_valor_comercial");

                    $descripcion_bienes[0]->otros_bienes1 = $this->input->post("otros_bienes1");
                    $descripcion_bienes[0]->otros_bienes2 = $this->input->post("otros_bienes2");
                    $descripcion_bienes[0]->otros_bienes3 = $this->input->post("otros_bienes3");
                    $descripcion_bienes[0]->otros_bienes4 = $this->input->post("otros_bienes4");
                    $descripcion_bienes[0]->otros_cantidad1 = $this->input->post("otros_cantidad1");
                    $descripcion_bienes[0]->otros_cantidad2 = $this->input->post("otros_cantidad2");
                    $descripcion_bienes[0]->otros_cantidad3 = $this->input->post("otros_cantidad3");
                    $descripcion_bienes[0]->otros_cantidad4 = $this->input->post("otros_cantidad4");
                    $descripcion_bienes[0]->otros_valor1 = $this->input->post("otros_valor1");
                    $descripcion_bienes[0]->otros_valor2 = $this->input->post("otros_valor2");
                    $descripcion_bienes[0]->otros_valor3 = $this->input->post("otros_valor3");
                    $descripcion_bienes[0]->otros_valor4 = $this->input->post("otros_valor4");
                    $descripcion_bienes[0]->save();

                    for ($i = 0; $i < 3; $i++) {
                        $j = $i + 1;

                        $descripcion_bienes_inmuebles[$i] = (isset($descripcion_bienes_inmuebles[$i]) && $descripcion_bienes_inmuebles[$i]) ? $descripcion_bienes_inmuebles[$i] : new DescripcionBienesInmuebles;
                        $descripcion_bienes_inmuebles[$i]->solicitud_id = $solicitud_credito->id;
                        $descripcion_bienes_inmuebles[$i]->tipo_inmueble = $this->input->post("descripcion_bien_{$j}_1");
                        $descripcion_bienes_inmuebles[$i]->departamento_id = $this->input->post("descripcion_bien_{$j}_2");
                        $descripcion_bienes_inmuebles[$i]->municipio_id = $this->input->post("descripcion_bien_{$j}_3");
                        $descripcion_bienes_inmuebles[$i]->vereda = $this->input->post("descripcion_bien_{$j}_4");
                        $descripcion_bienes_inmuebles[$i]->direccion = $this->input->post("descripcion_bien_{$j}_5");
                        $descripcion_bienes_inmuebles[$i]->valor_comercial = $this->input->post("descripcion_bien_{$j}_6");
                        if ($this->input->post("descripcion_bien_{$j}_1")) {
                            $descripcion_bienes_inmuebles[$i]->save();
                        }
                    }*/

                    $lista_chequeo = ($lista_chequeo) ? $lista_chequeo : new ListaChequeo;

                    $lista_chequeo->solicitud_id = $solicitud_credito->id;
                    $lista_chequeo->item1 = $this->input->post('lista_chequeo_item1') ? 1: 0;
                    $lista_chequeo->item2 = $this->input->post('lista_chequeo_item2') ? 1: 0;
                    $lista_chequeo->item3 = $this->input->post('lista_chequeo_item3') ? 1: 0;
                    $lista_chequeo->item4 = $this->input->post('lista_chequeo_item4') ? 1: 0;
                    $lista_chequeo->item5 = $this->input->post('lista_chequeo_item5') ? 1: 0;
                    $lista_chequeo->item6 = $this->input->post('lista_chequeo_item6') ? 1: 0;
                    $lista_chequeo->item7 = $this->input->post('lista_chequeo_item7') ? 1: 0;
                    $lista_chequeo->item8 = $this->input->post('lista_chequeo_item8') ? 1: 0;
                    $lista_chequeo->item9 = $this->input->post('lista_chequeo_item9') ? 1: 0;
                    $lista_chequeo->item10 = $this->input->post('lista_chequeo_item10') ? 1: 0;
                    $lista_chequeo->save();
                }

                $connection->commit();
                $this->twiggy->set('notif', array('type' => 'success', 'text' => "Datos guardados correctamente"));
            } catch (Exception $ex) {
                $connection->rollback();
                $this->twiggy->set('notif', array('type' => 'error', 'text' => "Se encontraron errores al procesar el formulario. <br>" . $ex));
            }
        } else if (validation_errors()) {

//          var_dump(validation_errors());
            $this->twiggy->set('notif', array('type' => 'error', 'text' => "Se encontraron errores al procesar el formulario. <br> Revise los recuadros rojos"));
        }

        $this->twiggy->set('url_imprimible', site_url("creditoagropecuario/imprimible/$ruat_id"));

        $this->twiggy->set('combos', $data);
        $this->twiggy->set('solicitud_credito', $solicitud_credito);
        $this->twiggy->set("productor", $productor);
        $this->twiggy->set("contacto", $contacto);
        $this->twiggy->set("renglon", $renglon);
        $this->twiggy->set("finca", $finca);
        $this->twiggy->set("contacto_departamento", $contacto->departamento);
        $this->twiggy->set("contacto_municipio", $contacto->municipio);
        $this->twiggy->register_function('set_value2');


        $this->twiggy->set("conyugue", $conyugue);
        $this->twiggy->set("referencias_familiares", $referencias_familiares);
        $this->twiggy->set("referencias_personales", $referencias_personales);
        $this->twiggy->set("referencias_financieras", $referencias_financieras);
        $this->twiggy->set("referencias_comerciales", $referencias_comerciales);
        $this->twiggy->set("descripcion_inversiones", $descripcion_inversiones);
        $this->twiggy->set("informacion_predios_inversion", $informacion_predios_inversion);
        $this->twiggy->set("ingresos_adicionales", $ingresos_adicionales);
        $this->twiggy->set("descripcion_bienes", $descripcion_bienes);
        $this->twiggy->set("descripcion_bienes_inmuebles", $descripcion_bienes_inmuebles);
        $this->twiggy->set("lista_chequeo", $lista_chequeo);
        
        $this->twiggy->set('preguntas_ingresos', $preguntas_ingresos);
        $this->twiggy->set('preguntas_egresos', $preguntas_egresos);
        $this->twiggy->set('preguntas_activos', $preguntas_activos);
        $this->twiggy->set('preguntas_totales', $preguntas_totales);

        $this->twiggy->template("creditoagropecuario/formulario_captura");
        $this->twiggy->display();
    }

    private function validation_rules() {
        $this->form_validation->set_rules("sc_fecha", ' ', 'required');
        $this->form_validation->set_rules("cod_beneficiario", ' ', 'required');
        $this->form_validation->set_rules("nombre_oficina", ' ', 'required');
        $this->form_validation->set_rules("banco_agrario_departamento", ' ', 'required');
        $this->form_validation->set_rules("banco_agrario_municipio", ' ', 'required|numeric');
        $this->form_validation->set_rules("solicitud_tipo_productor", ' ', 'required|numeric');

        $this->form_validation->set_rules("experiencia", ' ', 'required|numeric');
        if ($this->input->post("experiencia") == 1)
            $this->form_validation->set_rules("calidad_de", ' ', 'required|numeric');

        $this->form_validation->set_rules("conyugue_nombre1", ' ', 'required');
        $this->form_validation->set_rules("conyugue_apellido1", ' ', 'required');
        $this->form_validation->set_rules("conyugue_fecha_nacimiento", ' ', 'required');

        for ($i = 1; $i <= 2; $i++) {
            if (array_key_exists("referencias_fam{$i}", $this->aGruposConDatos)) {
                $this->form_validation->set_rules("referencias_fam{$i}_nombres", ' ', 'required');
                $this->form_validation->set_rules("referencias_fam{$i}_apellido1", ' ', 'required');
                $this->form_validation->set_rules("referencias_fam{$i}_parentesco", ' ', 'required');
                $this->form_validation->set_rules("referencias_fam{$i}_departamento", ' ', 'required|numeric');
                $this->form_validation->set_rules("referencias_fam{$i}_municipio", ' ', 'required|numeric');
            }
        }


        for ($i = 1; $i <= 2; $i++) {
            if (array_key_exists("referencias_per{$i}", $this->aGruposConDatos)) {
                $this->form_validation->set_rules("referencias_per{$i}_nombres", ' ', 'required');
                $this->form_validation->set_rules("referencias_per{$i}_apellido1", ' ', 'required');
                $this->form_validation->set_rules("referencias_per{$i}_parentesco", ' ', 'required');
                $this->form_validation->set_rules("referencias_per{$i}_departamento", ' ', 'required|numeric');
                $this->form_validation->set_rules("referencias_per{$i}_municipio", ' ', 'required|numeric');
            }
        }

        for ($i = 1; $i <= 2; $i++) {
            if (array_key_exists("referencias_fin{$i}", $this->aGruposConDatos)) {
                $this->form_validation->set_rules("referencias_fin{$i}_entidad", ' ', 'required');
                $this->form_validation->set_rules("referencias_fin{$i}_clase", ' ', 'required');
                $this->form_validation->set_rules("referencias_fin{$i}_departamento", ' ', 'required|numeric');
                $this->form_validation->set_rules("referencias_fin{$i}_municipio", ' ', 'required|numeric');
            }
        }

        if (array_key_exists("referencias_com", $this->aGruposConDatos)) {
            $this->form_validation->set_rules("referencias_com_nombre_est", ' ', 'required');
            $this->form_validation->set_rules("referencias_com_departamento", ' ', 'required|numeric');
            $this->form_validation->set_rules("referencias_com_municipio", ' ', 'required|numeric');
        }

        /*for ($j = 1; $j <= 4; $j++) {
            ///si el codigo FINAGRO tiene algo, valido el resto de la fila
            if (array_key_exists("descripcion_inv_{$j}", $this->aGruposConDatos)) {
                for ($i = 1; $i <= 10; $i++) {
                    if (1 === $i || 2 === $i || 3 === $i)
                        $this->form_validation->set_rules("descripcion_inv_{$j}_{$i}", ' ', 'required');
                    else
                        $this->form_validation->set_rules("descripcion_inv_{$j}_{$i}", ' ', 'required|numeric');
                }
            }
        }*/

        $this->form_validation->set_rules("informacion_pre_fuente_hid", ' ', 'required|numeric');
        $this->form_validation->set_rules("informacion_pre_fecha_ini", ' ', 'required');
        $this->form_validation->set_rules("informacion_pre_fecha_fin", ' ', 'required|callback_validar_fecha');

        $this->form_validation->set_rules("forma_llegar_pred", ' ', 'required');


        /*for ($i = 1; $i <= 9; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                if (1 != $i && 3 != $i && 7 != $i && 9 != $i)
                    $this->form_validation->set_rules("ingresos_adicionales_{$j}_{$i}", ' ', 'numeric');
            }
        }*/

        /*$this->form_validation->set_rules("descripcion_inv", ' ', 'required');

        for ($j = 1; $j <= 3; $j++) {
            if (array_key_exists("descripcion_bien_{$j}", $this->aGruposConDatos)) {
                for ($i = 1; $i <= 6; $i++) {
                    if (4 === $i || 5 === $i)
                        $this->form_validation->set_rules("descripcion_bien_{$j}_{$i}", ' ', 'required');
                    else
                        $this->form_validation->set_rules("descripcion_bien_{$j}_{$i}", ' ', 'required|numeric');
                }
            }
        }

        $this->form_validation->set_rules("vehiculo_valor_deuda", ' ', 'numeric');
        $this->form_validation->set_rules("vehiculo_valor_comercial", ' ', 'numeric');

        for ($i = 1; $i <= 4; $i++) {
            $this->form_validation->set_rules("otros_cantidad{$i}", ' ', 'numeric');
            $this->form_validation->set_rules("otros_valor{$i}", ' ', 'numeric');
        }*/


        $this->form_validation->set_rules("experiencia_act", ' ', 'required|numeric');
        $this->form_validation->set_rules("tiempo_permanencia", ' ', 'required|numeric');

        for ($i = 1; $i <= 10; $i++) {
            $this->form_validation->set_rules("lista_chequeo_item{$i}", ' ', 'numeric');
        }
    }

    ///custom validation rule para que la fecha final no sea menor a la inicial
    public function validar_fecha($fecha_fin) {
        $fecha_fin = strtotime($fecha_fin);
        $fecha_ini = strtotime($this->input->post("informacion_pre_fecha_ini"));

        if ($fecha_fin > $fecha_ini) {
            return TRUE;
        } else {
            $this->form_validation->set_message('validar_fecha', 'La fecha final no puede ser menor a la inicial.');
            return FALSE;
        }
    }

    private function validar_input() {

        ///grupos de inputs que necesitan al menos uno de sus componentes para disparar las rules de validacion
        $aGrupos = array(
            'referencias_fam1', 
            'referencias_fam2', 
            'referencias_per1', 
            'referencias_per2',
            'referencias_fin1',
            'referencias_fin2',
            'referencias_com',
            /*'descripcion_inv_1',
            'descripcion_inv_2',
            'descripcion_inv_3',
            'descripcion_inv_4',
            'descripcion_bien_1',
            'descripcion_bien_2',
            'descripcion_bien_3',*/
            );
        
        $this->aGruposConDatos = array();

        foreach ($aGrupos as $sGrupo) {
            
            ///si ya existe en los grupos con al menos un dato, no sigo iterandolo
            if(in_array($sGrupo, $this->aGruposConDatos))
                continue;

            ///comparo el grupo con todos las llaves que vengan por $_POST


            if($this->input->post()){
                foreach ($this->input->post() as $key => $value) {
                
                    ///si el key del post hace parte del grupo, y tiene algun valor, lo guardo en el arreglo
                    if (strpos($key, $sGrupo) !== FALSE && $value)
                        $this->aGruposConDatos[$sGrupo] = $sGrupo;
                }

            }
        }
        
//        var_dump($this->aGruposConDatos);
    }

    public function imprimible($ruat_id = NULL) {

        $ruat = Ruat::find($ruat_id);

        if (!$ruat)
            show_404();

        $productor = $ruat->productor;
        $contacto = $productor->contacto;
        $renglon = $productor->renglon_productivo;

        $solicitud_credito = SolicitudCredito::first(array(
                    'conditions' => array("ruat_id = ?", $ruat_id)
        ));

        $conyugue = $solicitud_credito->conyugue;

        $vtp = VisitaTipoProductor::first(array(
                    'conditions' => array('ruat_id = ?', $ruat_id)
        ));

        ///consulto las preguntas C para cargarlas dinamicamente
        $preguntas_c = TPCPregunta::all(array('order' => 'categoria, ordenamiento'));

        ///consulo las respuestas C
        $respuestas_c = TPCRespuesta::all(array(
                    'conditions' => array('visita_id = ?', $vtp->id)
        ));
        
        ///acomodo las respuestas con la pregunta como llave
        $respuestas_c_aux = array();
        foreach ($respuestas_c as $obj)
            $respuestas_c_aux[$obj->pregunta_c_id] = $obj;

        ///acomodo las preguntas C por categoria
        $preguntas_ingresos = $preguntas_egresos = $preguntas_activos = $preguntas_totales = array();
        foreach ($preguntas_c as $obj) {

            $objAux = $obj->to_array();

            ///Si la pregunta C tiene respuesta, la agrego al objeto de la pregunta
            if (isset($respuestas_c_aux[$obj->id]))
                $objAux['respuesta_bd'] = $respuestas_c_aux[$obj->id]->valor;

            if ($obj->categoria === 'A')
                $preguntas_activos[] = $objAux;
            elseif ($obj->categoria === 'B')
                $preguntas_ingresos[] = $objAux;
            elseif ($obj->categoria === 'C')
                $preguntas_egresos[] = $objAux;
            elseif ($obj->categoria === 'D')
                $preguntas_totales[] = $objAux;
        }

        $this->twiggy->set("productor", $productor);
        $this->twiggy->set("contacto", $contacto);
        $this->twiggy->set("contacto_departamento", $contacto->departamento);
        $this->twiggy->set("contacto_municipio", $contacto->municipio);
        $this->twiggy->set("renglon", $renglon);

        $this->twiggy->set("conyugue", $conyugue);

        $this->twiggy->set('preguntas_ingresos', $preguntas_ingresos);
        $this->twiggy->set('preguntas_egresos', $preguntas_egresos);
        $this->twiggy->set('preguntas_activos', $preguntas_activos);
        $this->twiggy->set('preguntas_totales', $preguntas_totales);

//        echo '<pre>';
//        var_dump($preguntas_egresos);
//        echo '</pre>';

        $this->twiggy->template("creditoagropecuario/formulario_captura_imprimible");
        $this->twiggy->display();
    }

    public function guardar() {
        $input = json_decode(file_get_contents("php://input"));

        if (empty($input->ruat_id)) {
            if ($this->documento_usado($input->productor->numero_documento)) {
                echo json_encode(array('success' => false, 'message' => array('type' => 'error',
                        'text' => 'Fallo al Guardar RUAT. <br> Ya hay un productor registrado con este número de documento')));
                return;
            }

            $ruat = new Ruat;
            $ruat->numero_formulario = $input->numero_formulario;
            $ruat->creador_id = current_user('id');
            $productor = Productor::create((array) $input->productor);
            $ruat->productor_id = $productor->id;
            $ruat->save();
        } else {
            if ($this->documento_usado($input->productor->numero_documento, $input->productor->id)) {
                echo json_encode(array('success' => false, 'message' => array('type' => 'error',
                        'text' => 'Fallo al Guardar RUAT. <br> Hay otro productor registrado con este número de documento')));
                return;
            }
            $ruat = Ruat::find($input->ruat_id);
            $ruat->numero_formulario = $input->numero_formulario;
            $ruat->modificado = time();
            $ruat->modificador_id = current_user('id');
            $ruat->save();
            $productor = $ruat->productor;
            $productor->set_attributes((array) $input->productor);
            $productor->save();
        }

        $input->contacto->productor_id = $productor->id;
        Contacto::create_or_update((array) $input->contacto);

        $econo = $input->economia;
        if (!$econo->usaCredito)
            $econo->credito_id = null;
        if ($econo->credito_id != 7)
            $econo->otro_credito = null;
        unset($econo->usaCredito);
        $econo->productor_id = $productor->id;
        Economia::create_or_update((array) $econo);

        Innovacion::table()->delete(array('ruat_id' => $ruat->id));
        foreach ($input->innovaciones as $innova) {
            if (!$innova->fuente_id)
                continue;
            $innova->ruat_id = $ruat->id;
            if ($innova->fuente_id != 6)
                $innova->otra_fuente = null;
            Innovacion::create((array) $innova);
        }

        Orgasociada::table()->delete(array('ruat_id' => $ruat->id));
        RazonNoPertenecer::table()->delete(array('ruat_id' => $ruat->id));

        foreach ($input->asociacion->cooperativa->filas as $org) {
            $clases = $org->clases;
            $beneficios = $org->beneficios;
            $directivo = $org->directivo;
            unset($org->clases, $org->beneficios, $org->directivo);
            $org->ruat_id = $ruat->id;
            $org->membresia = $org->directivo ? 'Directivo' : 'Participante';

            $orgasociada = Orgasociada::create((array) $org);

            foreach ($clases as $cls)
                OrgasociadaClase::create(array(
                    'orgasociada_id' => $orgasociada->id, 'clase_id' => $cls));

            foreach ($beneficios as $bnf)
                OrgasociadaBeneficio::create(array(
                    'orgasociada_id' => $orgasociada->id, 'beneficio_id' => $bnf));
        }

        foreach ($input->asociacion->cooperativa->razones as $razon)
            RazonNoPertenecer::create(array('ruat_id' => $ruat->id, 'razon_id' => $razon));

        $ruat->orgs_apoyan = json_encode($input->asociacion->cooperativa->orgs_apoyan);

        if ($input->asociacion->otroProductor->asociado) {
            $asoc = $input->asociacion->otroProductor;
            unset($asoc->asociado);
            $per = PersonaAsociada::create_or_update((array) $asoc);
            $ruat->asociado_id = $per->id;
        } else if ($ruat->asociado_id) {
            $id_to_delete = $ruat->asociado_id;
            $ruat->asociado_id = null;
            $ruat->save();
            PersonaAsociada::table()->delete(array('id' => $id_to_delete));
        }

        if ($input->asociacion->sigue->asociado) {
            $asoc = $input->asociacion->sigue;
            unset($asoc->asociado);
            $per = PersonaAsociada::create_or_update((array) $asoc);
            $ruat->seguir_id = $per->id;
        } else if ($ruat->seguir_id) {
            $id_to_delete = $ruat->seguir_id;
            $ruat->seguir_id = null;
            $ruat->save();
            PersonaAsociada::table()->delete(array('id' => $id_to_delete));
        }

        $ruat->save();
        $response = array(
            'success' => true,
            'message' => array('type' => 'success', 'text' => 'Guardado Exitoso <br/> Cargando siguiente sección...'),
            'scope' => $this->cargar($ruat->id)
        );

        echo json_encode($response);
    }

    private function documento_usado($cedula, $productor_id = NULL) {
        $cond = $productor_id ? array("numero_documento = ? and id <> ?", $cedula, $productor_id) : array("numero_documento = ?", $cedula);

        return Productor::exists(array('conditions' => $cond));
    }

    public function cargar($ruat_id, $do_echo = false) {
        $ruat = Ruat::find($ruat_id);
        $output = new StdClass;
        $output->soloLectura = $ruat->soloLectura($this);

        $output->ruat_id = $ruat->id;
        $output->numero_formulario = $ruat->numero_formulario;
        $output->productor = $ruat->productor->to_array();
        $output->productor['fecha_nacimiento'] = $this->datefmt($ruat->productor->fecha_nacimiento);
        $output->contacto = $ruat->productor->contacto->to_array();
        $output->economia = $ruat->productor->economia->to_array();
        $output->economia['usaCredito'] = (bool) ($output->economia['credito_id']);
        $output->asociacion = array(
            'cooperativa' => array('filas' => array()),
            'otroProductor' => array('asociado' => false),
            'sigue' => array('asociado' => false),
        );

        $coops = Orgasociada::find_all_by_ruat_id($ruat->id, array('include' => array('clases', 'beneficios')));
        foreach ($coops as $org) {
            $orgasociada = $org->to_array();
            $orgasociada['directivo'] = $orgasociada['membresia'] == 'Directivo';
            $orgasociada['clases'] = extract_prop($org->clases, 'clase_id');
            $orgasociada['beneficios'] = extract_prop($org->beneficios, 'beneficio_id');
            $output->asociacion['cooperativa']['filas'][] = $orgasociada;
        }


        if (!count($output->asociacion['cooperativa']['filas'])) {
            $output->asociacion['cooperativa']['filas'][] = new StdClass; //filita vacia
            $output->asociacion['cooperativa']['asociado'] = false;
        } else
            $output->asociacion['cooperativa']['asociado'] = true;

        $output->asociacion['cooperativa']['orgs_apoyan'] = json_decode($ruat->orgs_apoyan);
        $output->asociacion['cooperativa']['razones'] = extract_prop(RazonNoPertenecer::find_all_by_ruat_id($ruat->id), 'razon_id');

        if ($ruat->asociado_id) {
            $output->asociacion['otroProductor'] = PersonaAsociada::find($ruat->asociado_id)->to_array();
            $output->asociacion['otroProductor']['asociado'] = true;
        }
        if ($ruat->seguir_id) {
            $output->asociacion['sigue'] = PersonaAsociada::find($ruat->seguir_id)->to_array();
            $output->asociacion['sigue']['asociado'] = true;
        }

        $inno_map = array();
        foreach (Innovacion::find_all_by_ruat_id($ruat->id) as $inno) {
            $inno_map[$inno->tipo_id] = $inno->to_array();
        }

        $output->innovaciones = array();
        $output->realizaInnovacion = false;
        foreach (TipoInnovacion::sorted() as $t) {
            if (isset($inno_map[$t->id])) {
                $output->innovaciones[] = $inno_map[$t->id];
                $output->realizaInnovacion = true;
            } else
                $output->innovaciones[] = array('tipo_id' => $t->id);
        }

        if ($do_echo)
            echo json_encode($output);
        return $output;
    }

    private function datefmt($f) {
        return $f ? $f->format('Y-m-d') : '';
    }

}
