<?php
/**
 * UPTP - (PNFI Sección 1236) 
 *
 * Descripcion: Controlador que se encarga de la gestión de las profesiones de la empresa
 *
 * @category    
 * @package     Controllers 
 * @author      ALexis Borges (tuaalexis@gmail.com)
 * @copyright   Copyright (c) 2014 UPTP - (PNFI Team) (https://github.com/ArrozAlba/SASv2)
 * 
 *Load::models('solicitudes/factura', 'solicitudes/factura_dt');
 *Load::models('config/tiposolicitud');
 *Load::models('proveedorsalud/proveedor');
 *Load::models('proveedorsalud/servicio');
 *Load::models('proveedorsalud/medicoreembolso');
 *Load::models('proveedorsalud/especialidad');
 *Load::models('beneficiarios/titular');
 *Load::models('beneficiarios/beneficiario', 'solicitudes/solicitud_servicio', 'solicitudes/hequipo');
 *Load::models('config/patologia', 'solicitudes/solicitud_servicio_patologia', 'solicitudes/solicitud_servicio_factura');
*/
 Load::models('equipo/equipo');

class EquipoController extends BackendController {
    /**
     * Constante para definir el tipo de solicitud
     */
    const TPS = 7;
    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Solicitudes';
    }
    /**
     * Método principal
     */
    public function index() {
        DwRedirect::toAction('index');
    }
    /**
     * Método para buscar
     */
    public function buscar($field='nombre1', $value='none', $order='order.id.asc', $page=1) {        
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;
        $value = strtoupper($value);
        $equipo = new Hequipo();
        $equipos = $equipo->getAjaxReembolsos($field, $value, $order, $page);        
        if(empty($equipos->items)) {
            DwMessage::info('No se han encontrado registros');
        }
        $this->equipos = $equipos;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda de Reemnbolsos del sistema';        
    }    


    /**
     * Método para listar
     */
    public function listar($order='order.nombre.asc', $page='pag.1') { 
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        #$solicitud_equipo = new SolicitudServicio();        
        #$this->solicitud_equipos = $solicitud_equipo->getListadoSolicitudServicio($order, $page);
        $this->order = $order;        
        $this->page_title = 'Listado de Equipos y Maquinarias';
    }
    /**
     * Método para registro
     */
    public function registro($order='order.titular.asc', $page='pag.1') { 
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $solicitud_equipo = new Hequipo();        
        $this->solicitud_equipos = $solicitud_equipo->getListadoHReembolso($order, $page);
        $this->order = $order;        
        $this->page_title = 'Registro de Solicitudes de Reembolso';
    }
    /**
     * Método para aprobacion
     */
    public function aprobacion($order='order.nombre.asc', $page='pag.1') { 
    		$page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        	$solicitud_equipo = new SolicitudServicio();        
        	$this->solicitud_equipos = $solicitud_equipo->getListadoAprobacionReembolso($order, $page);
        	$this->order = $order;        
        	$this->page_title = 'Aprobación de Solicitudes de Atención Primaria';
    }
    /**
     * Método para contabilizar
     */
    public function contabilizar($order='order.nombre.asc', $page='pag.1') { 
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $solicitud_equipo = new SolicitudServicio();        
        $this->solicitud_equipos = $solicitud_equipo->getListadoContabilizarSolicitudServicio($order, $page);
        $this->order = $order;        
        $this->page_title = 'Contabilizar Solicitudes de Atención Primaria';
    }
    /**
     * Método para agregar
     */
    public function agregar() {
        //cierre del condicional del Input(post)
        $this->page_title = 'Agregar Equipo Maquinaria';
    }//CIERRE DE la funcion agregar

    /**
    * Método para cargar las facturas en caso que de los reeembolso tengan mas de una
    */
    public function facturar($key){
        if(!$id = DwSecurity::isValidKey($key, 'upd_solicitud_servicio', 'int')) {
            return DwRedirect::toAction('registro');
        }
        $solicitud_servicio = new SolicitudServicio();
        $obj = new SolicitudServicioPatologia();
        //$factura = new Factura();
        $factura_dt = new FacturaDt();
        $this->sol =  $obj->getInformacionSolicitudServicioPatologia($id);
        if(!$solicitud_servicio->getInformacionSolicitudServicio($id)) {            
            DwMessage::get('id_no_found');
            return DwRedirect::toAction('registro');
        }
        if(Input::hasPost('factura')) {
            ActiveRecord::beginTrans();
            $factu = Factura::setFactura('create', Input::post('factura'));
            if($factu){
                if(FacturaDt::setFacturaDt(Input::post('descripcion'), Input::post('cantidad'), Input::post('monto'), Input::post('exento'), $factu->id)) {
                    $solfactura = SolicitudServicioFactura::setSolicitudServicioFactura($factu->id, $id);
                    if($solfactura){
                        if(Input::post('multifactura')){ //para saber si va a cargar multiples facturas sobre esa solicitud 
                            $solser = $solicitud_servicio->getInformacionSolicitudServicio($id);
                            $solser->estado_solicitud="G"; //estado G parcialmente facturada 
                            $solser->save();
                            ActiveRecord::commitTrans();
                            DwMessage::valid('Se ha cargado la factura exitosamente!');
                            $key_upd = DwSecurity::getKey($id, 'upd_solicitud_servicio'); 
                            return DwRedirect::toAction('facturar/'.$key_upd);   //retorna a la misma visata de facturacion 
                        }
                        else{
                            $solser = $solicitud_servicio->getInformacionSolicitudServicio($id);
                            $solser->estado_solicitud="F";
                            $solser->save();
                            ActiveRecord::commitTrans();
                            DwMessage::valid('Se ha cargado la factura exitosamente!');
                          return DwRedirect::toAction('facturacion');     
                        }

                    }
                    else{
                        ActiveRecord::rollbackTrans();
                        DwMessage::error('No se pudo enviar a cargar multiples facturas!');
                    }

                }
                else{
                    ActiveRecord::rollbackTrans();
                    DwMessage::error('Los detalles de la Factura no se han cargado correctamente Intente de nuevo!');
                }
            }
            else{
                ActiveRecord::rollbackTrans();
                DwMessage::error('La Factura ha dao peos!');
            }
        }
        $this->solicitud_servicio = $solicitud_servicio;
        $this->page_title = 'Cargar Facturas a la solicitud';        
    }
    
  
    /**
    *Metodo para aprobar las solicitudes (Cambiar de Estatus)
    */

    public function reversar_aprobacion($key){
    	if(!$id = DwSecurity::isValidKey($key, 'upd_solicitud_servicio', 'int')) {
            return DwRedirect::toAction('aprobacion');
        } 
        //Mejorar esta parte  implementando algodon de seguridad
        $solicitud_equipo = new SolicitudServicio();
        $sol = $solicitud_equipo->getInformacionSolicitudServicio($id);
        $sol->estado_solicitud="R";
        $sol->save();
        return DwRedirect::toAction('aprobacion');
    }
    /**
     * Método para formar el reporte en pdf 
     */
    public function reporte_equipo($id) { 
        View::template(NULL);       
       // if(!$id = DwSecurity::isValidKey($key, 'upd_solicitud_servicio', 'int')) {
       //     return DwRedirect::toAction('aprobacion');
       // }

        //Mejorar esta parte  implementando algodon de seguridad
        $solicitud_equipo = new SolicitudServicio();
                if(!$sol = $solicitud_equipo->getReporteSolicitudServicio($id)) {
            DwMessage::get('id_no_found');
        };
        $this->fecha_sol = $solicitud_equipo->fecha_solicitud;
        $this->nombres = strtoupper($solicitud_equipo->nombre1." ".$solicitud_equipo->nombre2);
        $this->apellidos = strtoupper($solicitud_equipo->apellido1." ".$solicitud_equipo->apellido2);
        $this->cedula = $solicitud_equipo->cedula;
        $this->telefono = $solicitud_equipo->telefono;
        $this->celular = $solicitud_equipo->celular;
        $this->nacionalidad = $solicitud_equipo->nacionalidad;        
        $this->sexo = $solicitud_equipo->sexo;  
        $this->idtitular = $solicitud_equipo->idtitular;
        $this->bene = $solicitud_equipo->beneficiario_id;
        $this->medico = strtoupper($solicitud_equipo->nombrem1." ".$solicitud_equipo->nombrem2." ".$solicitud_equipo->apellidom1." ".$solicitud_equipo->apellidom2);
        $this->clinica = strtoupper($solicitud_equipo->proveedor);
        $this->servicio = strtoupper($solicitud_equipo->servicio);
        $this->direccion = $solicitud_equipo->direccionp;

        //llamada a otra funcion, ya que no logre un solo query para ese reportee! :S
        $titular = new titular();
        $datoslaborales = $titular->getInformacionLaboralTitular($this->idtitular);
        $this->upsa = $titular->sucursal;
        $this->direccionlaboral = strtoupper($titular->direccion);
        $this->municipio_laboral = strtoupper($titular->municipios);
        $this->estado_laboral = strtoupper($titular->estados);
        $this->pais_laboral = strtoupper($titular->paiss);
        $this->cargo = strtoupper($titular->cargo);
        //instanciando la clase beneficiario 
        
        $beneficiarios = new beneficiario();
        $beneficiarios->getInformacionbeneficiario($this->bene);
        $this->nombresb = strtoupper($beneficiarios->nombre1." ".$beneficiarios->nombre2);
        $this->apellidosb = strtoupper($beneficiarios->apellido1." ".$beneficiarios->apellido2);
        $this->cedulab = $beneficiarios->cedula;
        $this->parentesco = $beneficiarios->parentesco;
    }
    /**
     * Método para editar
     */
    public function editar($key) {        
        if(!$id = DwSecurity::isValidKey($key, 'upd_solicitud_servicio', 'int')) {
            return DwRedirect::toAction('registro');
        }        
        
        $solicitud_equipo = new SolicitudServicio();
        if(!$solicitud_equipo->getInformacionSolicitudServicio($id)) {            
            DwMessage::get('id_no_found');
            return DwRedirect::toAction('registro');
        }
        
        if(Input::hasPost('solicitud_equipo') && DwSecurity::isValidKey(Input::post('solicitud_servicio_id_key'), 'form_key')) {
            if(SolicitudServicio::setSolicitudServicio('update', Input::post('solicitud_equipo'), array('id'=>$id))){
                DwMessage::valid('La solicitud se ha actualizado correctamente!');
                return DwRedirect::toAction('contabilizar');
            }
        } 
        $this->solicitud_equipo = $solicitud_equipo;
        $this->page_title = 'Actualizar solicitud';        
    }
    
    /**
     * Método para eliminar
     */
    public function eliminar($key) {         
        if(!$id = DwSecurity::isValidKey($key, 'del_solicitud_servicio', 'int')) {
            return DwRedirect::toAction('listar');
        }        
        
        $solicitud_equipo = new SolicitudServicio();
        if(!$solicitud_equipo->getInformacionSolicitudServicio($id)) {            
            DwMessage::get('id_no_found');
            return DwRedirect::toAction('listar');
        }                
        try {
            if(SolicitudServicio::setSolicitudServicio('delete', array('id'=>$solicitud_equipo->id))) {
                DwMessage::valid('La solicitud se ha eliminado correctamente!');
            }
        } catch(KumbiaException $e) {
            DwMessage::error('Esta solicitud no se puede eliminar porque se encuentra relacionada con otro registro.');
        }
        
        return DwRedirect::toAction('listar');
    }
}

