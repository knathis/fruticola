<?php

class Ruat extends MyModel
{
    static $table_name = "ruat";

    static $belongs_to = array(
        array('productor', 'class_name'=>'Productor', 'foreign_key'=>'productor_id'),
        array('asociado', 'class_name'=>'PersonaAsociada', 'foreign_key'=>'asociado_id'),
        array('seguir', 'class_name'=>'PersonaAsociada', 'foreign_key'=>'seguir_id'),
        array('creador', 'class_name'=>'Usuario', 'foreign_key'=>'creador_id'),
    );

    static $has_one = array(
        array('observacion', 'class_name'=>'Observacion', 'foreign_key' => 'ruat_id'),
        array('innovacion', 'class_name'=>'Innovacion', 'foreign_key' => 'ruat_id'),
        array('asociacion', 'class_name'=>'Orgasociada', 'foreign_key'=> 'ruat_id'),
        array('cosecha', 'class_name'=>'Cosecha', 'foreign_key' => 'ruat_id'),
        array('postcosecha', 'class_name'=>'Postcosecha', 'foreign_key' => 'ruat_id'),
        array('visita_tipo_productor', 'class_name'=>'VisitaTipoProductor', 'foreign_key' => 'ruat_id'),
        array('finca', 'class_name'=>'Finca', 'foreign_key' => 'ruat_id'),
    );

    static $has_many = array(
        array('razones', 'class_name'=>'RazonNoPertenecer', 'foreign_key' => 'ruat_id'),
        array('producto', 'class_name'=>'Producto', 'foreign_key'=> 'ruat_id'),
        array('aprendizaje', 'class_name'=>'AprendizajeRespuesta', 'foreign_key'=> 'ruat_id'),
        array('bpas', 'class_name'=>'BuenasPracticas', 'foreign_key' => 'ruat_id'),
    );

    public function eliminar()
    {
        function cond($field, $value) {
            $res = array('conditions' => array());
            $res['conditions'][$field] = $value;
            return $res;
        }

        //eliminar finca
        if($this->finca) {
            FincaMaquinaria::delete_all(cond('finca_id', $this->finca->id));
            FincaServicio::delete_all(cond('finca_id', $this->finca->id));
            FincaTransporte::delete_all(cond('finca_id', $this->finca->id));
            $this->finca->delete();
        }

        //eliminar orgs asociadas
        $orgs_ids = extract_prop(Orgasociada::find_all_by_ruat_id($this->id), 'id');
        if(count($orgs_ids)) {
            OrgasociadaClase::delete_all(array('conditions' => array('orgasociada_id IN (?)',$orgs_ids)));
            OrgasociadaBeneficio::delete_all(array('conditions' => array('orgasociada_id IN (?)',$orgs_ids)));
        }
        OrgAsociada::delete_all(cond('ruat_id', $this->id));

        //eliminar productos, procesos innovacion, razones no pertenecer
        Producto::delete_all(cond('ruat_id', $this->id));
        Innovacion::delete_all(cond('ruat_id', $this->id));
        RazonNoPertenecer::delete_all(cond('ruat_id', $this->id));

        //eliminar aprendizaje
        AprendizajeRespuesta::delete_all(cond('ruat_id',$this->id));

        //eliminar observacion
        if($this->observacion) $this->observacion->delete();

        //eliminar bpa
        foreach($this->bpas as $bpa) {
            $bpa->eliminar();
        }

        //eliminar cosecha
        if($this->cosecha) $this->cosecha->eliminar();

        //eliminar visita tipo productor
        if($this->visita_tipo_productor) $this->visita_tipo_productor->eliminar();

        //eliminar postcosecha
        if($this->postcosecha) $this->postcosecha->eliminar();

        //eliminar ruat como tal
        $this->delete();

        if($this->asociado) $this->asociado->delete(); //ya se pueden eliminar estos
        if($this->seguir) $this->seguir->delete();

        //elimnar productor

        if($this->productor->contacto) $this->productor->contacto->delete();
        if($this->productor->economia) $this->productor->economia->delete();
        $this->productor->delete();
    }

    public static function puedeCrear() {
        $userid = current_user('id');
        return check_profile(array("Administrador","Coordinador"), false) 
            || in_array($userid, array(198, 199, 8,11,9,5,14,6,13,18, 56));
    }
}