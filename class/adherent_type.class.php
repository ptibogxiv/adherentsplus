<?php
/* Copyright (C) 2002		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2016		Charlie Benke			<charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/adherents/class/adherent_type.class.php
 *	\ingroup    member
 *	\brief      File of class to manage members types
 *	\author     Rodolphe Quiedeville
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class AdherentTypePlus extends CommonObject
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'adherent_type';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'adherent_type';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'group';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string
	 * @deprecated Use label
	 * @see $label
	 */
	public $libelle;

	/**
     * @var string Adherent type label
     */
    public $label;

    /**
     * @var string Adherent type nature
     */
    public $morphy;

	/**
	 * @var bool
	 * @deprecated Use subscription
	 * @see subscription
	 */
	public $cotisation;
	/**
	 * @var int Subsription required (0 or 1)
	 * @since 5.0
	 */
	public $subscription;
	/** @var string Public note */
	public $note;
	public $description;
	/** @var bool Can vote*/
	public $vote;
	/** @var bool Email sent during validation */
	public $mail_valid;
  public $welcome;
	public $price;
  public $price_level;
	public $automatic;
  public $automatic_renew;
	public $family;
  public $statut;
  public $duration;
  
  public $multilangs=array();

    /*
    * Service expiration
    */
    public $duration_value;

    /**
     * Exoiration unit
     */
    public $duration_unit;
  
	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->statut = 1;
	}
  
  
    /**
     *    Load array this->multilangs
     *
     * @return int        <0 if KO, >0 if OK
     */
    public function getMultiLangs()
    {
        global $langs;

        $current_lang = $langs->getDefaultLang();

        $sql = "SELECT lang, label, description, email";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type_lang";
        $sql.= " WHERE fk_type=".$this->id;

        $result = $this->db->query($sql);
        if ($result) {
            while ($obj = $this->db->fetch_object($result))
            {
                //print 'lang='.$obj->lang.' current='.$current_lang.'<br>';
                if ($obj->lang == $current_lang)  // si on a les traduct. dans la langue courante on les charge en infos principales.
                {
                    $this->label        = $obj->label;
                    $this->description    = $obj->description;
                    $this->email        = $obj->email;
                }
                $this->multilangs["$obj->lang"]["label"]        = $obj->label;
                $this->multilangs["$obj->lang"]["description"]    = $obj->description;
                $this->multilangs["$obj->lang"]["email"]        = $obj->email;
            }
            return 1;
        }
        else
        {
            $this->error="Error: ".$this->db->lasterror()." - ".$sql;
            return -1;
        }
    }
  
    /**
     *    Update or add a translation for a product
     *
     * @param  User $user Object user making update
     * @return int        <0 if KO, >0 if OK
     */
    public function setMultiLangs($user)
    {
        global $conf, $langs;

        $langs_available = $langs->get_available_languages(DOL_DOCUMENT_ROOT, 0, 2);
        $current_lang = $langs->getDefaultLang();

        foreach ($langs_available as $key => $value)
        {
            if ($key == $current_lang) {
                $sql = "SELECT rowid";
                $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type_lang";
                $sql.= " WHERE fk_type=".$this->id;
                $sql.= " AND lang='".$key."'";

                $result = $this->db->query($sql);

                if ($this->db->num_rows($result)) // if there is already a description line for this language
                {
                    $sql2 = "UPDATE ".MAIN_DB_PREFIX."adherent_type_lang";
                    $sql2.= " SET ";
                    $sql2.= " label='".$this->db->escape($this->label)."',";
                    $sql2.= " description='".$this->db->escape($this->description)."'";
                    if (! empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) { $sql2.= ", email='".$this->db->escape($this->other)."'";
                    }
                    $sql2.= " WHERE fk_type=".$this->id." AND lang='".$this->db->escape($key)."'";
                }
                else
                {
                    $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."adherent_type_lang (fk_type, lang, label, description";
                    if (! empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) { $sql2.=", email";
                    }
                    $sql2.= ")";
                    $sql2.= " VALUES(".$this->id.",'".$this->db->escape($key)."','". $this->db->escape($this->label)."',";
                    $sql2.= " '".$this->db->escape($this->description)."'";
                    if (! empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) { $sql2.= ", '".$this->db->escape($this->other)."'";
                    }
                    $sql2.= ")";
                }
                dol_syslog(get_class($this).'::setMultiLangs key = current_lang = '.$key);
                if (! $this->db->query($sql2)) {
                    $this->error=$this->db->lasterror();
                    return -1;
                }
            }
            elseif (isset($this->multilangs[$key])) {
                $sql = "SELECT rowid";
                $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type_lang";
                $sql.= " WHERE fk_type=".$this->id;
                $sql.= " AND lang='".$key."'";

                $result = $this->db->query($sql);

                if ($this->db->num_rows($result)) // if there is already a description line for this language
                {
                    $sql2 = "UPDATE ".MAIN_DB_PREFIX."adherent_type_lang";
                    $sql2.= " SET ";
                    $sql2.= " label='".$this->db->escape($this->multilangs["$key"]["label"])."',";
                    $sql2.= " description='".$this->db->escape($this->multilangs["$key"]["description"])."'";
                    if (! empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) { $sql2.= ", email='".$this->db->escape($this->multilangs["$key"]["other"])."'";
                    }
                    $sql2.= " WHERE fk_type=".$this->id." AND lang='".$this->db->escape($key)."'";
                }
                else
                {
                    $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."adherent_type_lang (fk_type, lang, label, description";
                    if (! empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) { $sql2.=", email";
                    }
                    $sql2.= ")";
                    $sql2.= " VALUES(".$this->id.",'".$this->db->escape($key)."','". $this->db->escape($this->multilangs["$key"]["label"])."',";
                    $sql2.= " '".$this->db->escape($this->multilangs["$key"]["description"])."'";
                    if (! empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) { $sql2.= ", '".$this->db->escape($this->multilangs["$key"]["other"])."'";
                    }
                    $sql2.= ")";
                }

                // We do not save if main fields are empty
                if ($this->multilangs["$key"]["label"] || $this->multilangs["$key"]["description"]) {
                    if (! $this->db->query($sql2)) {
                        $this->error=$this->db->lasterror();
                        return -1;
                    }
                }
            }
            else
            {
                // language is not current language and we didn't provide a multilang description for this language
            }
        }

        // Call trigger
        $result = $this->call_trigger('MEMBER_TYPE_SET_MULTILANGS', $user);
        if ($result < 0) {
            $this->error = $this->db->lasterror();
            return -1;
        }
        // End call triggers

        return 1;
    }
  
       /**
     *    Delete a language for this product
     *
     * @param string $langtodelete Language code to delete
     * @param User   $user         Object user making delete
     *
     * @return int                            <0 if KO, >0 if OK
     */
    public function delMultiLangs($langtodelete, $user)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_type_lang";
        $sql.= " WHERE fk_type=".$this->id." AND lang='".$this->db->escape($langtodelete)."'";

        dol_syslog(get_class($this).'::delMultiLangs', LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            // Call trigger
            $result = $this->call_trigger('ADHERENT_TYPE_DEL_MULTILANGS', $user);
            if ($result < 0) {
                $this->error = $this->db->lasterror();
                dol_syslog(get_class($this).'::delMultiLangs error='.$this->error, LOG_ERR);
                return -1;
            }
            // End call triggers
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this).'::delMultiLangs error='.$this->error, LOG_ERR);
            return -1;
        }
    }


	/**
	 *  Fonction qui permet de creer le status de l'adherent
	 *
	 *  @param	User		$user			User making creation
	 *  @param	int		$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return	int						>0 if OK, < 0 if KO
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error=0;

		$this->statut=(int) $this->statut;
		$this->label=trim($this->label);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_type (";
		$sql.= "libelle";
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->label)."'";
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog("Adherent_type::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent_type");

			$result = $this->update($user, 1);
			if ($result < 0)
			{
				$this->db->rollback();
				return -3;
			}

			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('MEMBER_TYPE_CREATE', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Updating the type in the database
	 *
	 *  @param	User	$user			Object user making change
	 *  @param	int		$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return	int						>0 if OK, < 0 if KO
	 */
	public function update($user, $notrigger = 0)
	{
    global $langs, $conf, $hookmanager;

		$error=0;

		$this->label=trim($this->label);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent_type ";
		$sql.= "SET ";
    $sql.= "statut = ".$this->statut.",";
    $sql.= "morphy = '".$this->morphy."',";
    $sql.= "libelle = '".$this->db->escape($this->label) ."',";
    $sql.= "subscription = '".$this->subscription."',";
    $sql.= "welcome = '".$this->welcome."',";
    $sql.= "price = '".$this->price."',";
    $sql.= "price_level = '".$this->price_level."',";
    $sql.= "duration = '" . $this->db->escape($this->duration_value . $this->duration_unit) ."',";
    $sql.= "note = '".$this->db->escape($this->note)."',";
    $sql.= "vote = '".$this->vote."',";
    $sql.= "automatic = '".$this->automatic."',";
    $sql.= "automatic_renew = '".$this->automatic_renew."',";
    $sql.= "family = '".$this->family."',";
    $sql.= "mail_valid = '".$this->db->escape($this->mail_valid)."'";
    $sql.= " WHERE rowid =".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{

                $this->description = $this->db->escape($this->note);

                // Multilangs
                if (! empty($conf->global->MAIN_MULTILANGS)) {
                    if ($this->setMultiLangs($user) < 0) {
                           $this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
                           return -2;
                    }
                }
    
			$action='update';
      
if (! empty($conf->global->PRODUIT_MULTIPRICES)){  
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php'; 
        $sql = "SELECT s.rowid as id, s.price_level";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	      $sql.= " WHERE s.entity IN (".getEntity('adherent').")";
	      $sql.= " AND s.rowid IN (SELECT a.fk_soc FROM ".MAIN_DB_PREFIX."adherent as a WHERE a.fk_adherent_type =".$this->id.")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {     $objp = $this->db->fetch_object($resql);
                    	$soc = new Societe($this->db);
                      $soc->fetch($objp->id);
                      $soc->set_price_level($this->price_level, $user);
                    $i++;
                }
            }
        }
}      

			// Actions on extra fields
			if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('MEMBER_TYPE_MODIFY', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
				return -$error;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Function to delete the member's status
	 *
	 *  @return		int		> 0 if OK, 0 if not found, < 0 if KO
	 */
	public function delete()
	{
		global $user;

		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_type";
		$sql.= " WHERE rowid = ".$this->id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			// Call trigger
			$result=$this->call_trigger('MEMBER_TYPE_DELETE', $user);
			if ($result < 0) { $error++; $this->db->rollback(); return -2; }
			// End call triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Function that retrieves the status of the member
	 *
	 *  @param 		int		$rowid			Id of member type to load
	 *  @return		int						<0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
        global $langs, $conf;
  
        $sql = "SELECT d.rowid, d.tms as datem, d.libelle as label, d.statut as status, d.morphy, d.subscription, d.welcome, d.price, d.price_level, d.duration, d.automatic, d.automatic_renew, d.family, d.mail_valid, d.note, d.vote";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
        $sql .= " WHERE d.rowid = ".$rowid;

        dol_syslog("Adherent_type::fetch", LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                if (empty($obj->duration)) $obj->duration="1y"; 

                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->welcome        = $obj->welcome;
                $this->price          = $obj->price;
                $this->price_level    = $obj->price_level;
                $this->label          = $obj->label;
                $this->libelle        = $obj->label;	// For backward compatibility
                $this->statut         = $obj->status;
                $this->status         = $obj->status;
                $this->morphy         = $obj->morphy;
                $this->duration       = $obj->duration;
                $this->duration_value = substr($obj->duration, 0, dol_strlen($obj->duration)-1);
                $this->duration_unit  = substr($obj->duration, -1);
                $this->subscription   = $obj->subscription;
                $this->automatic      = $obj->automatic;
                $this->automatic_renew= $obj->automatic_renew;
                $this->family         = $obj->family;
                $this->mail_valid     = $obj->mail_valid;
                $this->note           = $obj->note;
                $this->description    = $obj->note;  
                $this->vote           = $obj->vote;
                $this->status         = $obj->status;
                $this->date_modification			= $this->db->jdate($obj->datem);
                
                // multilangs
                if (! empty($conf->global->MAIN_MULTILANGS)) {
                    $this->getMultiLangs();
                }
                
	if (! empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->price_level)) $this->price_level=1;
                 
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of members' type
	 *
	 *  @return 	array	List of types of members
	 */
	public function liste_array()
	{
        // phpcs:enable
		global $conf,$langs;

		$adherenttypes = array();

		$sql = "SELECT rowid, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type";
		$sql.= " WHERE entity IN (".getEntity('member_type').")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);

			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);

					$adherenttypes[$obj->rowid] = $langs->trans($obj->label);
					$i++;
				}
			}
		}
		else
		{
			print $this->db->error();
		}
		return $adherenttypes;
	}

    /**
     *  Return list of members' type
     *
     *  @return 	array	List of types of members
     */
    function liste_array_alt()
    {
        global $conf,$langs;

        $adherenttypes = array();

        $sql = "SELECT rowid, libelle as label, welcome, price, morphy, automatic, automatic_renew, use_default, note";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type";
	      $sql.= " WHERE entity IN (".getEntity('adherent').")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);
                    $adherenttypes[$obj->rowid][note] = $langs->trans($obj->note);
                    $adherenttypes[$obj->rowid][label] = $langs->trans($obj->label);
                    $adherenttypes[$obj->rowid][price] = $obj->price;
                    $adherenttypes[$obj->rowid][morphy] = $obj->morphy;
                    $adherenttypes[$obj->rowid][welcome] = $obj->welcome;
                    $adherenttypes[$obj->rowid][automatic] = $obj->automatic;
                    $adherenttypes[$obj->rowid][automatic_renew] = $obj->automatic_renew;
                    $adherenttypes[$obj->rowid][use_default] = $obj->use_default;
                    $adherenttypes[$obj->rowid][rowid] = $obj->rowid;
                    $i++;
                }
            }
        }
        else
        {
            print $this->db->error();
        }
        return $adherenttypes;
    }


    /**
     *  Return clicable name (with picto eventually)
     *
     *  @param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     *  @param		int		$maxlen			length max label
     *  @param		int  	$notooltip		1=Disable tooltip
     *  @return		string					String with URL
     */
    public function getNomUrl($withpicto = 0, $maxlen = 0, $notooltip = 0)
    {
        global $langs;

        $result='';
        $label=$langs->trans("ShowTypeCard", $this->label);

        $linkstart = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?rowid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= ($maxlen?dol_trunc($this->label, $maxlen):$this->label);
        $result .= $linkend;

        return $result;
    }

    /**
     *     getLibStatut
     *
     *     @return string     Return status of a type of member
     */
    public function getLibStatut()
    {
        return '';
    }
    
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param		array	$info		Info array loaded by _load_ldap_info
	 *	@param		int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *									1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *									2=Return key only (uid=qqq)
	 *	@return		string				DN
	 */
	public function _load_ldap_dn($info, $mode = 0)
	{
        // phpcs:enable
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_MEMBERS_TYPES."=".$info[$conf->global->LDAP_KEY_MEMBERS_TYPES].",".$conf->global->LDAP_MEMBER_TYPE_DN;
		if ($mode==1) $dn=$conf->global->LDAP_MEMBER_TYPE_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_MEMBERS_TYPES."=".$info[$conf->global->LDAP_KEY_MEMBERS_TYPES];
		return $dn;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Initialize the info array (array of LDAP values) that will be used to call LDAP functions
	 *
	 *	@return		array		Tableau info des attributs
	 */
	public function _load_ldap_info()
	{
        // phpcs:enable
		global $conf,$langs;

		$info=array();

		// Object classes
		$info["objectclass"]=explode(',', $conf->global->LDAP_MEMBER_TYPE_OBJECT_CLASS);

		// Champs
		if ($this->label && ! empty($conf->global->LDAP_MEMBER_TYPE_FIELD_FULLNAME)) $info[$conf->global->LDAP_MEMBER_TYPE_FIELD_FULLNAME] = $this->label;
		if ($this->note && ! empty($conf->global->LDAP_MEMBER_TYPE_FIELD_DESCRIPTION)) $info[$conf->global->LDAP_MEMBER_TYPE_FIELD_DESCRIPTION] = dol_string_nohtmltag($this->note, 0, 'UTF-8', 1);
		if (! empty($conf->global->LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS))
		{
			$valueofldapfield=array();
			foreach($this->members as $key=>$val)    // This is array of users for group into dolibarr database.
			{
				$member=new AdherentPlus($this->db);
				$member->fetch($val->id, '', '', '', false, false);
				$info2 = $member->_load_ldap_info();
				$valueofldapfield[] = $member->_load_ldap_dn($info2);
			}
			$info[$conf->global->LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS] = (!empty($valueofldapfield)?$valueofldapfield:'');
		}
		return $info;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	public function initAsSpecimen()
	{
		global $conf, $user, $langs;

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'MTSPEC';
		$this->specimen=1;

		$this->label='MEMBERS TYPE SPECIMEN';
		$this->note='This is a note';
		$this->mail_valid='This is welcome email';
		$this->subscription=1;
		$this->vote=0;

		$this->statut=1;

		// Members of this member type is just me
		$this->members=array(
			$user->id => $user
		);
	}
    
	/**
	 *	Return translated label by the nature of a adherent (physical or moral)
	 *
	 *	@param	string		$morphy		Nature of the adherent (physical or moral)
	 *	@return	string					Label
	 */
	function getmorphylib($morphy='')
	{
		global $langs;
		if ($morphy == 'phy') { return $langs->trans("Physical"); }
		elseif ($morphy == 'mor') { return $langs->trans("Moral"); } 
    else return $langs->trans("Physical & Morale");
		//return $morphy;
	} 
  
	/**
	 *     getMailOnValid
	 *
	 *     @return string     Return mail content of type or empty
	 */
	public function getMailOnValid()
	{
		global $conf;

		if (! empty($this->mail_valid) && trim(dol_htmlentitiesbr_decode($this->mail_valid)))
		{
			return $this->mail_valid;
		}

		return '';
	}  

	/**
	 *     getMailOnSubscription
	 *
	 *     @return string     Return mail content of type or empty
	 */
	public function getMailOnSubscription()
	{
		global $conf;

		// mail_subscription not  defined so never used
		if (! empty($this->mail_subscription) && trim(dol_htmlentitiesbr_decode($this->mail_subscription)))  // Property not yet defined
		{
			return $this->mail_subscription;
		}

		return '';
	}

	/**
	 *     getMailOnResiliate
	 *
	 *     @return string     Return mail model content of type or empty
	 */
    public function getMailOnResiliate()
    {
        global $conf;

        // NOTE mail_resiliate not defined so never used
        if (! empty($this->mail_resiliate) && trim(dol_htmlentitiesbr_decode($this->mail_resiliate)))  // Property not yet defined
        {
            return $this->mail_resiliate;
        }

        return '';
    }
}