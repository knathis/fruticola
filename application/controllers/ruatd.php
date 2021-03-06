<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class RuatD extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        check_profile(array("Administrador", "Coordinador", "Digitador", "Consultas"));
    }

    public function index($ruat_id = null) {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('observacion');
        $this->form_validation->set_error_delimiters('<div><label class="error">', '</label></div>');

        //$ruat_id = 1; /// sacarlo de session o algo, pendiente definir
        ///Consulto la observacion de este RUAT (Edicion)
        $observacion = Observacion::first(array(
                    'conditions' => array('ruat_id = ?', $ruat_id)
        ));
        
        $ruat = Ruat::find($ruat_id, array('joins' => array('productor')));
        $productor = $ruat->productor;
        

        ///si no existe observacion anterior, creo una nueva
        if (empty($observacion))
            $observacion = new Observacion;

        $upload_result_ruat = $upload_result_documento_identidad = '';
        if ($this->form_validation->run()) {
            $observacion->ruat_id = $ruat_id;
            $observacion->observacion = $this->input->post('observacion') ?: '';
            
            //var_dump($_FILES);
            $ok = true;
            ///Subo el archivo del RUAT
            if(isset($_FILES["archivo_formulario"]) && !empty($_FILES["archivo_formulario"]["name"])) {
                $arr_upload_result = $this->do_upload_ruat($ruat_id);
                if(!isset($arr_upload_result['error']) && isset($arr_upload_result['upload_data']))
                    $observacion->ruta_formulario = 'ruat/'.$arr_upload_result['upload_data']['file_name'];
                else {
                    $upload_result_ruat = $arr_upload_result['error']; 
                    $ok = false;
                }
            }
            
            ///Subo el archivo de la CC
            if(isset($_FILES["archivo_documento_identificacion"]) && !empty($_FILES["archivo_documento_identificacion"]["name"])) {
                $arr_upload_result = $this->do_upload_identificacion($productor->numero_documento);
                if(!isset($arr_upload_result['error']) && isset($arr_upload_result['upload_data'])){
                    $productor->adjunto_cedula = 'documentos_identificacion/'.$arr_upload_result['upload_data']['file_name'];
                    $productor->save();
                } 
                else {
                    $upload_result_documento_identidad = $arr_upload_result['error'];   
                    $ok = false;
                }
            }
            
            $observacion->save();
            
            if($ok) {
                $this->session->set_flashdata("notif", array('type'=>'success', 'text' => 'RUAT guardado exitosamente'));
                redirect("listadoruats");
            }
        }
        
        ///Obtengo los datos del usuario creador del ruat
        $usuaioSesion = $this->ion_auth->user( Ruat::find($ruat_id)->creador_id )->row();
        //$usuaioSesion = $usuarioSesion == null ? $this->ion_auth->user()->row() : $this->ion_auth->user($usuaioSesion)->row();

        //$usuaioSesion = Ruat::find($ruat_id)->creador_id;


        /*echo('<pre>');
        //var_dump(Ruat::find($ruat_id)->creador_id);
        var_dump($usuaioSesion);
        echo('</pre>');
        die;*/
        
        $this->twiggy->register_function('form_open_multipart');
        $this->twiggy->set('soloLectura', Ruat::find($ruat_id)->soloLectura($this));
        $this->twiggy->set('url_ruatc', site_url("ruatc/index/$ruat_id"));
        $this->twiggy->set('observacion', $observacion);
        $this->twiggy->set('upload_result_ruat', $upload_result_ruat);
        $this->twiggy->set('upload_result_documento_identidad', $upload_result_documento_identidad);
        $this->twiggy->set('productor', $productor);
        $this->twiggy->set('usuaioSesion', $usuaioSesion);

        $this->twiggy->set("breadcrumbs", ruat_breadcrumbs(4, $ruat_id));
        $this->twiggy->template("ruat/observaciones");
        $this->twiggy->display();
    }

    private function do_upload_ruat($ruat_id) {
        $config['upload_path'] = './uploads/ruat';
        $config['allowed_types'] = 'pdf|png';
        $config['max_size'] = '10240';/// 10MiB
        $config['overwrite'] = true;/// 10MiB
        $config['file_name'] = $ruat_id;/// 10MiB

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('archivo_formulario')) {
            return array('error' => $this->upload->display_errors('<div><label class="error">', '</label></div>'));
        } else {
            return array('upload_data' => $this->upload->data());
        }
    }
    
    private function do_upload_identificacion($numero_documento) {
        $config['upload_path'] = './uploads/documentos_identificacion';
        $config['allowed_types'] = 'pdf|png';
        $config['max_size'] = '10240';/// 10MiB
        $config['overwrite'] = true;/// 10MiB
        $config['file_name'] = $numero_documento;/// 10MiB

        $this->load->library('upload', $config);
        
        ///machetazo! se estaban guardando los errores de la subida anterior
        $this->upload->error_msg = array();
        if (!$this->upload->do_upload('archivo_documento_identificacion')) {
            return array('error' => $this->upload->display_errors('<div><label class="error">', '</label></div>'));
        } else {
            return array('upload_data' => $this->upload->data());
        }
    }

}

