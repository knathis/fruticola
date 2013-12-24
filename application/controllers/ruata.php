<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RuatA extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library('ion_auth');
    }

    public function index()
    {
        check_profile($this, 'Administrador');

                        
        $this->load->library('form_validation');
        //poner validaciones aqui. Ejemplo:
        //$this->form_validation->set_rules('name','Nombre', 'required|max_length[50]');
        //-------------------------------------------------------------------------------
        // PARTE 1
        $this->form_validation->set_rules('nombre1','Primer Nombre', 'trim|required');
        $this->form_validation->set_rules('nombre2');
        $this->form_validation->set_rules('apellido1','Primer Apellido', 'trim|required');
        $this->form_validation->set_rules('apellido2');        
        $this->form_validation->set_rules('tipo_documento', 'Tipo de Documento', 'required');
        $this->form_validation->set_rules('numero_documento', 'Numero de Documento','required|is_natural');
        $this->form_validation->set_rules('fecha_nacimiento', 'Fecha de Nacimiento', 'required|callback__date_check');
        $this->form_validation->set_rules('sexo', 'Sexo', 'required');
        $this->form_validation->set_rules('nivel_educativo', 'Nivel Educativo', 'required');
        $this->form_validation->set_rules('tipo_productor', 'Tipo de Productor', 'required');
        $this->form_validation->set_rules('renglon_productivo', 'Renglon Productivo', 'required');

        /*
        //-------------------------------------------------------------------------------------------
        //PARTE 2
        $this->form_validation->set_rules('telefono_fijo');
        $this->form_validation->set_rules('celular');
        $this->form_validation->set_rules('correo');
        $this->form_validation->set_rules('vereda');
        $this->form_validation->set_rules('direccion');

        //------------------------------------------------------------------------------------
        //PARTE 3
        $this->form_validation->set_rules('ingreso_familiar', 'Ingreso Mensual Familiar', 'decimal');
        $this->form_validation->set_rules('personas_cargos', 'Personas a Cargo', 'numeric');
        $this->form_validation->set_rules('ingreso_agropecuaria', 'Ingreso de Actividad Agropecuaria', 'decimal');
        $this->form_validation->set_rules('tipoCredito', 'Procedencia del Crédito', 'required');
        $this->form_validation->set_rules('cual_procedencia', 'Procedencia del Crédito', 'required');
        //------------------------------------------------------------------------------------------------
        //PARTE 4
        $this->form_validation->set_rules('beneficiosSociedad', 'Beneficios de Sociedades','required');
        
        $this->form_validation->set_rules('periodicidad1', 'Periodicidad', 'required');
        $this->form_validation->set_rules('periodicidad2', 'Periodicidad', 'required');
        $this->form_validation->set_rules('periodicidad3', 'Periodicidad', 'required');
        
        
        $this->form_validation->set_rules('clases_organizacion1', 'Clase de Organización', 'required');
        $this->form_validation->set_rules('clases_organizacion2', 'Clase de Organización', 'required');
        $this->form_validation->set_rules('clases_organizacion3', 'Clase de Organización', 'required');

        //----------------------------------------------------------------------------------------------
        //PARTE 5
        $this->form_validation->set_rules('nombre_socio');
        $this->form_validation->set_rules('apellido_socio');
        $this->form_validation->set_rules('vereda_socio');
        $this->form_validation->set_rules('gradoConfianza1', 'Grado de Confianza', 'required');

        $this->form_validation->set_rules('nombre_seguir');
        $this->form_validation->set_rules('apellido_seguir');
        $this->form_validation->set_rules('vereda_seguir');
        $this->form_validation->set_rules('gradoConfianza2', 'Grado de Confianza', 'required');
        */

        if($this->form_validation->run())
        {
            //las validaciones pasaron, aqui iria la logica de insertar en la BD...
            $productor = new Productor;
            $productor->nombre1               = $this->input->post('nombre1');
            $productor->nombre2               = $this->input->post('nombre2');
            $productor->apellido1             = $this->input->post('apellido1');
            $productor->apellido2             = $this->input->post('apellido2');
            $productor->tipo_documento_id     = $this->input->post('tipo_documento');
            $productor->numero_documento      = $this->input->post('numero_documento');
            $productor->fecha_nacimiento      = $this->input->post('fecha_nacimiento');
            $productor->nivel_educativo_id    = $this->input->post('nivel_educativo');
            $productor->tipo_productor_id     = $this->input->post('tipo_productor');
            $productor->renglon_productivo_id = $this->input->post('renglon_productivo');
            $productor->sexo                  = $this->input->post('sexo');
            $productor->save();

        }

        $data = array();
        $data['tiposDocumento'] = assoc(TipoDocumento::sorted());
        $data['nivelesEducativos'] = assoc(NivelEducativo::sorted());
        $data['tiposProductor'] = assoc(TipoProductor::sorted());
        $data['renglonesProductivos'] = assoc(RenglonProductivo::sorted());
        $data['clasesOrganizaciones'] = assoc(ClaseOrganizacion::sorted());
        $data['beneficiosSociedad'] = assoc(TipoBeneficio::sorted());
        $data['tipoCredito'] = assoc(TipoCredito::sorted());
        $data['periodicidad'] = assoc(Periodicidad::sorted());
        $data['tipoConfianza'] = assoc(TipoConfianza::sorted());
        

        //var_dump($tiposDocumento);
        //die();

        $this->twiggy->set($data, NULL);
        $this->twiggy->template("ruat/datos_personales");
        $this->twiggy->display();
    }

    function _date_check($str)
    {
        if(preg_match("/\d{4}-(0[1-9]|1[0-2])-(0[1-9]|1[0-9]|2[0-9]|3(0|1))/",$str))
            return TRUE;
        else
        {
            $this->form_validation->set_message('_date_check','El campo %s debe estar en la forma AAAA-MM-DD');
            return FALSE;
        }
    }
}