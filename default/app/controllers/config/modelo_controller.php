<?php
/**
 * Dailyscript - Web | App | Media
 *
 * Descripcion: Controlador que se encarga de la gestión de las modelos de la empresa
 *
 * @category    
 * @package     Controllers 
 * @author      Iván D. Meléndez (ivan.melendez@dailycript.com.co)
 * @copyright   Copyright (c) 2013 Dailyscript Team (http://www.dailyscript.com.co)
 */

Load::models('config/modelo');

class ModeloController extends BackendController {
    
    /**
     * Método que se ejecuta antes de cualquier acción
     */
    protected function before_filter() {
        //Se cambia el nombre del módulo actual
        $this->page_module = 'Configuraciones';
    }
    
    /**
     * Método principal
     */
    public function index() {
        DwRedirect::toAction('listar');
    }

    /**
     * Método para buscar
     */
    public function buscar($field='sucursal', $value='none', $order='order.id.asc', $page=1) {        
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $field = (Input::hasPost('field')) ? Input::post('field') : $field;
        $value = (Input::hasPost('field')) ? Input::post('value') : $value;
        $value = strtoupper($value);
        $modelo = new Modelo();
        $modelos = $modelo->getAjaxModelos($field, $value, $order, $page);
        if(empty($modelos->items)) {
            DwMessage::info('No se han encontrado registros');
        }
        $this->modelos = $modelos;
        $this->order = $order;
        $this->field = $field;
        $this->value = $value;
        $this->page_title = 'Búsqueda de modelos del sistema';        
    }
    
    /**
     * Método para listar
     */
    public function listar($order='order.nombre.asc', $page='pag.1') { 
        $page = (Filter::get($page, 'page') > 0) ? Filter::get($page, 'page') : 1;
        $modelo = new Modelo();        
        $this->modelos = $modelo->getListadoModelo($order, $page);
        $this->order = $order;        
        $this->page_title = 'Listado de Modelos';
    }
    
    /**
     * Método para agregar
     */
    public function agregar() {
    //    $empresa = Session::get('empresa', 'config');
        if(Input::hasPost('modelo')) {
            if(Modelo::setModelo('create', Input::post('modelo'))) {
                DwMessage::valid('La modelo se ha registrado correctamente!');
                return DwRedirect::toAction('listar');
            }            
        } 
        $this->page_title = 'Agregar Modelo';
    }
     /**
     * Método para obtener modelos
     */
    
        //accion que busca en las modelos y devuelve el json con los datos
    public function autocomplete() {
        View::template(NULL);
        View::select(NULL);
        if (Input::isAjax()) { //solo devolvemos los estados si se accede desde ajax 
            $busqueda = Input::post('busqueda');
            $modelos = Load::model('config/modelo')->obtener_modelos($busqueda);
            die(json_encode($modelos)); // solo devolvemos los datos, sin template ni vista
            //json_encode nos devolverá el array en formato json ["aragua","carabobo","..."]
        }
    }        
    /**
     * Método para editar
     */
    public function editar($key) {        
        if(!$id = DwSecurity::isValidKey($key, 'upd_modelo', 'int')) {
            return DwRedirect::toAction('listar');
        }        
        
        $modelo = new Modelo();
        if(!$modelo->getInformacionModelo($id)) {            
            DwMessage::get('id_no_found');
            return DwRedirect::toAction('listar');
        }
        
        if(Input::hasPost('modelo') && DwSecurity::isValidKey(Input::post('modelo_id_key'), 'form_key')) {
            if(Modelo::setModelo('update', Input::post('modelo'))){
                DwMessage::valid('La modelo se ha actualizado correctamente!');
                return DwRedirect::toAction('listar');
            }
        } 
        //$this->ciudades = Load::model('params/ciudad')->getCiudadesToJson();
        $this->modelo = $modelo;
        $this->page_title = 'Actualizar modelo';        
    }
    
    /**
     * Método para eliminar
     */
    public function eliminar($key) {         
        if(!$id = DwSecurity::isValidKey($key, 'del_modelo', 'int')) {
            return DwRedirect::toAction('listar');
        }        
        
        $modelo = new Modelo();
        if(!$modelo->getInformacionModelo($id)) {            
            DwMessage::get('id_no_found');
            return DwRedirect::toAction('listar');
        }                
        try {
            if(Modelo::setModelo('delete', array('id'=>$modelo->id))) {
                DwMessage::valid('La modelo se ha eliminado correctamente!');
            }
        } catch(KumbiaException $e) {
            DwMessage::error('Esta modelo no se puede eliminar porque se encuentra relacionada con otro registro.');
        }
        
        return DwRedirect::toAction('listar');
    }
}
