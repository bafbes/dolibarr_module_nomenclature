<?php

class TNomenclature extends TObjetStd
{
    
    function __construct() 
    {
        global $conf; 
        
        $this->set_table(MAIN_DB_PREFIX.'nomenclature');
        $this->add_champs('title');
        $this->add_champs('fk_product',array('type'=>'integer', 'index'=>true));
        $this->add_champs('is_default',array('type'=>'integer', 'index'=>true));
        $this->add_champs('qty_reference',array('type'=>'float','index'=>true));
        
        $this->_init_vars();
        
        $this->start();
		
        $this->setChild('TNomenclatureDet', 'fk_nomenclature');
        if($conf->workstation->enabled) $this->setChild('TNomenclatureWorkstation', 'fk_nomenclature');     
        
        $this->qty_reference = 1;       
        
    }   
    function getDetails($qty_ref = 1) {
        
        $Tab = array();
        
        $coef = 1;
        if($qty_ref != $this->qty_reference) {
            $coef = $qty_ref / $this->qty_reference;
        }
        
        foreach($this->TNomenclatureDet as &$d) {
            
            $qty = $d->qty * $coef;
            $fk_product = $d->fk_product;
            
            $Tab[]=array(
                0=>$fk_product
                ,1=>$qty
                ,'fk_product'=>$fk_product
                ,'qty'=>$qty
            );
            
        }
        
        return $Tab;
        
    }
    static function get(&$PDOdb, $fk_product) 
    {
        $Tab = $PDOdb->ExecuteAsArray("SELECT rowid FROM ".MAIN_DB_PREFIX."nomenclature WHERE fk_product=".(int) $fk_product);
        
        $TNom=array();
        
        foreach($Tab as $row) {
            
            $n=new TNomenclature;
            $n->load($PDOdb, $row->rowid);
            
            $TNom[] = $n;
            
        }
        
        return $TNom;
    }
	
	/*
	 * Return the default TNomenclature object of product or the first of list if not default or false
	 */
	static function getDefaultNomenclature(&$PDOdb, $fk_product, $qty_ref = 0)
	{
		$TNomenclature = new TNomenclature;
		
		$PDOdb->Execute('SELECT rowid FROM '.MAIN_DB_PREFIX.'nomenclature 
		          WHERE fk_product='.(int) $fk_product.' 
		          AND qty_reference<='.$qty_ref.'
		          ORDER BY is_default DESC, qty_reference DESC
		          LIMIT 1');
        $res = $PDOdb->Get_line();
		
		if ($res)
		{
			$TNomenclature->load($PDOdb, $res->rowid);
			
			return $TNomenclature;
		}
		else 
		{
			$Tab = self::get($PDOdb, $fk_product);
			if (count($Tab) > 0)
			{
				return $Tab[0];
			}
		}
		
		return false;
	}
	
    static function resetDefaultNomenclature(&$PDOdb, $fk_product)
	{
		return $PDOdb->Execute('UPDATE '.MAIN_DB_PREFIX.'nomenclature SET is_default = 0 WHERE fk_product = '.(int) $fk_product);
	}
    
}


class TNomenclatureDet extends TObjetStd
{
    
    static $TType=array(
        1=>'Principal'
        ,2=>'Secondaire'
        ,3=>'Consommable'
    );
    
    function __construct() 
    {
        $this->set_table(MAIN_DB_PREFIX.'nomenclaturedet');
        $this->add_champs('fk_product,product_type,fk_nomenclature',array('type'=>'integer', 'index'=>true));
        $this->add_champs('qty',array('type'=>'float'));
        
        $this->_init_vars();
        
        $this->start();
        
        $this->qty=1;
        $this->product_type=1;        
    }   
}


class TNomenclatureWorkstation extends TObjetStd
{
    
    
    function __construct() 
    {
        $this->set_table(MAIN_DB_PREFIX.'nomenclature_workstation');
        $this->add_champs('fk_workstation,fk_nomenclature,rang',array('type'=>'integer', 'index'=>true));
        $this->add_champs('nb_hour,nb_hour_prepare,nb_hour_manufacture',array('type'=>'float'));
        
        $this->_init_vars();
        
        $this->start();
        
        $this->qty=1;
        $this->product_type=1;        
    }   
    function load(&$PDOdb, $id, $annexe = true) {
        parent::load($PDOdb, $id);
        
        if($annexe) {
            $this->workstation = new TWorkstation;
            $this->workstation->load($PDOdb, $this->fk_workstation);    
        }
         
        
    }
    function save(&$PDOdb) {
        
        $this->nb_hour  = $this->nb_hour_prepare+$this->nb_hour_manufacture;
        
        parent::save($PDOdb);    
    }
    
}