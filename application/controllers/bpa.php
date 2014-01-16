<?php defined('BASEPATH') OR exit('No direct script access allowed');

class BPA extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        check_profile($this, "Administrador", "Coordinador", "Digitador");
    }

    public function index($ruat_id)
    {
        //check_profile($this,"Administrador");
        //$ruat_id = 1;

        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div><label class="error">', '</label></div>');

        $data = array();
        $preguntasB = BpaPregunta::sortedB();
        $preguntasC = BpaPregunta::sortedC();

        $this->form_validation->set_rules('conclusionB', 'Conclusión', 'required');
        $this->form_validation->set_rules('recomendacionFinal', 'Recomendación', 'required');
        $this->form_validation->set_rules('fecha', 'Fecha', 'required');


        $datosBPA = BuenasPracticas::find_by_ruat_id($ruat_id);
        $existePreviamente = false;
        if($datosBPA) {
            $datosBPA = $datosBPA;
            $existePreviamente = true;
            $arreglo = $datosBPA->to_array();
            $arreglo['fecha'] = $datosBPA->fecha_visita->format("Y-m-d");
            $this->twiggy->set("datosBPA", $arreglo);

            $this->twiggy->set('soloLectura', $datosBPA->soloLectura($this));
        }
        else {
            $datosBPA = array('fecha' => date('Y-m-d'), 'conclusion' => '', 'recomendacion' => '', 'nivel_bpa' => 0);
            $this->twiggy->set("datosBPA", $datosBPA);
        }

        

        //$preguntasB = BpaPregunta::find_all_by_seccion('B');
        foreach($preguntasB as $preg) {
            $this->form_validation->set_rules("recomendacion".$preg->id);
        }

        //$preguntasC = BpaPregunta::find_all_by_seccion('C');
        foreach($preguntasC as $preg) {
            $this->form_validation->set_rules("observacion".$preg->id);
        }



        if ($this->form_validation->run()) {

            //$bpa = $bpa_id ? BuenasPracticas::find($bpa_id) : new BuenasPracticas();
            
            if($existePreviamente){// existe
                //var_dump($datosBPA);
                $bpa = $datosBPA;
            }else{
                $bpa = new BuenasPracticas();
                $bpa->ruat_id = $ruat_id;
                $bpa->creador_id = current_user('id');
            }

            $bpa->fecha_visita = $this->input->post('fecha');
            $bpa->conclusion = $this->input->post('conclusionB');
            $bpa->nivel_bpa = $this->input->post('valorFinal');
            $bpa->recomendacion = $this->input->post('recomendacionFinal');
            $bpa->save();

            $conteoB = count($preguntasB);
            $conteoC = count($preguntasC);
            $conteo = $conteoB + $conteoC;

            $cont = 0;

            $idsB = array();
            foreach($preguntasB as $pregunta){
                array_push($idsB, $pregunta->id);
            }

            $respuestasB = BpaRespuesta::all(array(
                    'conditions' => array('bpa_id = ? AND pregunta_id in (?)', $bpa->id, $idsB), 'order' => 'id'));
            for($i = 1;$i<=$conteo;$i++){
                if($this->input->post('sino'.$i) === FALSE && $this->input->post('recomendacion'.$i) === FALSE){
                    continue;
                }

                $bpaR;
                if(count($respuestasB)>0){
                    $bpaR = $respuestasB[$cont];
                    $cont++;
                    //echo "paso";
                }else{
                    $bpaR = new BpaRespuesta();
                    $bpaR->bpa_id = $bpa->id;
                }
                
                $bpaR->pregunta_id = $i;
                if($this->input->post('sino'.$i) == 'on'){
                    $bpaR->puntaje = 1;
                }else{
                    $bpaR->puntaje = 0;
                }
                
                $bpaR->observacion = $this->input->post('recomendacion'.$i);

                $bpaR->save();
            }

            $cont = 0;
            $idsC = array();
            foreach($preguntasC as $pregunta){
                array_push($idsC, $pregunta->id);
            }

            $respuestasC = BpaRespuesta::all(array(
                    'conditions' => array('bpa_id = ? AND pregunta_id in (?)', $bpa->id, $idsC), 'order' => 'id'));

            for($i = 1;$i<=$conteo;$i++){
                if($this->input->post('valor'.$i) === FALSE){
                    continue;
                }

                $bpaR;
                if(count($respuestasC)>0){
                    $bpaR = $respuestasC[$cont];
                    $cont++;
                }else{
                    $bpaR = new BpaRespuesta();
                    $bpaR->bpa_id = $bpa->id;

                }
                $bpaR->pregunta_id = $i;
                $bpaR->puntaje = $this->input->post('valor'.$i);
                $bpaR->observacion = $this->input->post('observacion'.$i);
                $bpaR->save();
            }
            $this->session->set_flashdata("notif", array('type'=>'success', 'text' => 'Formulario BPA guardado exitósamente'));
            redirect('listadoruats');
        }else if(validation_errors()){
            $this->twiggy->set('notif',array('type'=>'error', 'text'=> "Debe rellenar como mínimo la fecha, conclusión y recomendacion final. <br> Revise los recuadros rojos"));
        }

        
        if($existePreviamente){
            $idsB = extract_prop($preguntasB, "id");
            $idsC = extract_prop($preguntasC, "id");
            $respuestasB = BpaRespuesta::all(array(
                    'conditions' => array('bpa_id = ? AND pregunta_id in (?)', $datosBPA->id, $idsB), 'order' => 'id'));
            $respuestasC = BpaRespuesta::all(array(
                    'conditions' => array('bpa_id = ? AND pregunta_id in (?)', $datosBPA->id, $idsC), 'order' => 'id'));
            $this->twiggy->set('existe', 'yes');
            //$this->twiggy->set('datosBpa', $datosBPA);
            $this->twiggy->set('respuestasB', $respuestasB);
            $this->twiggy->set('respuestasC', $respuestasC);    
        }else{
            $this->twiggy->set('existe', 'not');

        }

        $ruatNumFormulario = Ruat::find($ruat_id)->numero_formulario;
        $this->twiggy->set('numForm', $ruatNumFormulario);
        $this->twiggy->set('preguntasB', $preguntasB);
        $this->twiggy->set('preguntasC', $preguntasC);
        $this->twiggy->set('tamaño', count($preguntasC)+count($preguntasB));

        
        
        $this->twiggy->template("bpa/bpa");
        $this->twiggy->display();
    }

    public function guardar() //si viene como parametro: $ruat_id
    {
        $input = json_decode(file_get_contents("php://input"));
        echo "me llego ";
        var_dump($input);

        //$finca = Finca::find_by_ruat_id($ruat_id);
        //if(!$finca) 
        
    }
}