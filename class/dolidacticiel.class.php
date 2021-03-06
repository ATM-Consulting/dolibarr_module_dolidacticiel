<?php

class TDolidacticiel extends TObjetStd {
/*
 * Gestion des équipements 
 * */
    
    static $level=array(
		0=>'Normal'
		,1=>'Supérieur'
		,2=>'Expert'
	);
    
    function __construct() {
        $this->set_table(MAIN_DB_PREFIX.'dolidacticiel');

        $this->add_champs('mainmenu,action,code', array('type'=>'string', 'index'=>true, 'length'=>100));
		$this->add_champs('prev_code,module_name', array('type'=>'string', 'length'=>100));
        $this->add_champs('cond', array('type'=>'text'));
        $this->add_champs('level',array('type'=>'int', 'index'=>true, 'rules'=>array('min'=>0, 'max'=>2)));
        $this->add_champs('from_atm', array('type'=>'int'));
		
        $this->_init_vars('title,description,rights,mainmenutips,tips');
        
	    $this->start();
	
		$this->setChild('TDolidacticielUser', 'fk_dolidacticiel');
		
    }

	static function getLevelFromUser(&$user) {
		return 2;
	}	
	
	static function getAllByATM(&$PDOdb, &$conf)
	{
		$TRes = array();
		$TIdTest = $PDOdb->ExecuteAsArray("SELECT rowid FROM ".MAIN_DB_PREFIX."dolidacticiel WHERE from_atm = 1");
		
		foreach ($TIdTest as $row)
		{
			$test = new TDolidacticiel;
			$test->load($PDOdb, $row->rowid);
			
			$TRes[] = $test;
		}
		
		return $TRes;
	}
	
	/*
	 * Comme getAll mais renvois aussi les tests non autorisés
	 */
	static function getAllTest(&$PDOdb, &$user, &$conf)
	{
		$level = self::getLevelFromUser($user);
		
		$TIdTest = $PDOdb->ExecuteAsArray("
			SELECT rowid FROM ".MAIN_DB_PREFIX."dolidacticiel 
			WHERE level <= ".$level."
		");
		
		$TRes = array();
		foreach ($TIdTest as $row)
		{
			$test = new TDolidacticiel;
			$test->load($PDOdb, $row->rowid);
			
			$rights = !empty($test->rights) ? eval('return ('.$test->rights.' == 1);') : true;
			if ($rights)
			{
				$test->currentUserAchievement = $test->getUserAchievement($user->id);
			}
			else 
			{
				$test->currentUserAchievement = -1;
			}
			
			$TRes[] = $test;
		}
		
		return $TRes;
	}
	
	/*
	 * Retourne la liste des tests autorisés par l'utilisateur
	 */
	static function getAll(&$PDOdb,&$user,&$conf, $withAchievement=true) 
	{
		$level = self::getLevelFromUser($user);
		
		$TRes = $PDOdb->ExecuteAsArray("SELECT d.rowid 
					FROM ".MAIN_DB_PREFIX."dolidacticiel d 
					LEFT JOIN ".MAIN_DB_PREFIX."dolidacticiel_user du ON (du.fk_dolidacticiel = d.rowid)
					WHERE d.level<=".$level." ");
					
		$Tab=array();
		foreach($TRes as $row) 
		{
			$d = new TDolidacticiel;
			$d->load($PDOdb, $row->rowid);
			
			$rights = !empty($d->rights) ? eval('return ('.$d->rights.' == 1);') : true;
			if($rights === true) 
			{
				if($withAchievement) $d->currentUserAchievement = $d->getUserAchievement($user->id);
				
				$Tab[] = $d;
			}
			
		}	
		
		return $Tab;
	}
	
	
	static function getAllUser(&$PDOdb, &$db, &$conf)
	{
		if (!class_exists('User')) dol_include_once('/user/class/user.class.php');
		
		$TRes = array();
		$TUserId = $PDOdb->ExecuteAsArray('SELECT rowid FROM '.MAIN_DB_PREFIX.'user WHERE statut = 1');
		
		foreach ($TUserId as $obj)
		{
			$user = new User($db);
			$user->fetch($obj->rowid);
			$user->getrights();
			
			$TRes[] = array(
				'user' => $user
				,'dolidacticiel' => TDolidacticiel::getAll($PDOdb, $user, $conf)
			);
		}
		
		return $TRes;
	}
	
	static function testConditions(&$PDOdb,&$user,&$object, $action, $conf)
	{
		$level = self::getLevelFromUser($user);
		
		$TRes = $PDOdb->ExecuteAsArray("SELECT d.rowid 
					FROM ".MAIN_DB_PREFIX."dolidacticiel d 
					LEFT JOIN ".MAIN_DB_PREFIX."dolidacticiel_user du ON (du.fk_dolidacticiel = d.rowid AND du.fk_user=".$user->id.")
					WHERE FIND_IN_SET('".$action."', d.action) AND d.level<=".$level." AND du.achievement IS NULL");

		foreach($TRes as $row) 
		{
			$d = new TDolidacticiel;
			$d->load($PDOdb, $row->rowid);

			$rights = !empty($d->rights) ? eval('return ('.$d->rights.' == 1);') : true;
			$eval = !empty($d->cond) ? eval('return ('.$d->cond.');') : true;

			if($eval === true && $rights === true) 
			{
				$k = $d->addChild($PDOdb, 'TDolidacticielUser');
				//var_dump($d->TDolidacticielUser);
				$d->TDolidacticielUser[$k]->fk_user = $user->id;
				$d->TDolidacticielUser[$k]->achievement=1;
				$d->save($PDOdb);
				
				setEventMessages('GG WP '.$d->code.' : '.$d->title."\n".$d->description, null);
			}
		
		}
	
	}
	
	/*
	 * Permet de vérifier que l'objet est bien associé au bon Tiers/Produit
	 * Exemple voir test C1
	 */
	static function checkStaticId(&$PDOdb, &$object, $table, $value)
	{
		switch ($table) {
			case 'societe':
				$TRes = $PDOdb->ExecuteAsArray('SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE rowid='.($object->socid ? $object->socid : $object->fk_soc).' AND nom="'.$value.'"');
				break;
			case 'product':
				$TRes = $PDOdb->ExecuteAsArray('SELECT rowid FROM '.MAIN_DB_PREFIX.'product WHERE rowid='.$object->id.' AND label="'.$value.'"');
				break;
			default:
				return 0;
				break;
		}
		
		return count($TRes) > 0 ? true: false;
	}
	
	static function getStaticId(&$PDOdb, $table, $field, $value)
	{
		$PDOdb->Execute("SELECT rowid FROM ".MAIN_DB_PREFIX.$table." WHERE ".$field." = '".$value."'");
		if ($row = $PDOdb->Get_line()) return $row->rowid;
		else return '';
	}
	
	function getUserAchievement($fk_user) {
		
		foreach($this->TDolidacticielUser as &$ddu) {
			
			if($ddu->fk_user == $fk_user) {
				return true;
			}
			
		}
		
		return false;
	}
	
	function prevCodeAchievement(&$PDOdb, &$user)
	{
		if (empty($this->prev_code)) return true;
		
		$TCode = explode(',', $this->prev_code);

		foreach ($TCode as $code)
		{
			$prevTest = new TDolidacticiel;
			$prevTest->loadBy($PDOdb, $code, 'code', true);
			
			if (!$prevTest->getUserAchievement($user->id)) return false;
		}

		return true;
	}
	
}

Class TDolidacticielUser extends TObjetStd {
    function __construct() {
        $this->set_table(MAIN_DB_PREFIX.'dolidacticiel_user');
        $this->add_champs('fk_dolidacticiel,fk_user',array('type'=>'int', 'index'=>true));
        $this->add_champs('achievement',array('type'=>'int', 'rules'=>array('in'=>array(0,1))));
        
        $this->_init_vars();
        
        $this->start();
    }
	
}
