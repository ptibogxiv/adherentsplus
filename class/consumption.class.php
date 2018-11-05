<?php
/* Copyright (C) 2018 Thibault FOUCART <support@ptibogxiv.net>
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
 *		\file 		htdocs/adherents/class/consumption.class.php
 *		\ingroup	member
 *		\brief		File of class to manage consumptions of foundation members
 */

//require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 *	Class to manage consumptions of foundation members
 */
class Consumption extends CommonObject
{
	public $element='consumption';
	public $table_element='consumption';
  public $picto='payment';

	var $date_creation;				// Date creation
	var $date_validation;				// Date modification
	var $fk_invoice;				// Subscription start date (date subscription)
	var $fk_product;				// Subscription end date
	var $fk_adherent;
  var $qty;
  var $value;
  var $unit;


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Function who permitted cretaion of the subscription
	 *
	 *	@param	int		$userid		userid de celui qui insere
	 *	@return	int					<0 if KO, Id subscription created if OK
	 */
	function create($userid)
	{
		global $langs;
		$now=dol_now();

		// Check parameters
		if ($this->datef <= $this->dateh)
		{
			$this->error=$langs->trans("ErrorBadValueForDate");
			return -1;
		}
    
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."subscription (fk_adherent, fk_type, datec, dateadh, datef, subscription, note)";
        if ($this->fk_type == NULL) {
dol_include_once('/adherentsplus/class/adherent.class.php');
		$member=new AdherentPlus($this->db);
		$result=$member->fetch($this->fk_adherent);
    $type=$member->typeid;
    }else {
    $type=$this->fk_type;
    }
    $sql.= " VALUES (".$this->fk_adherent.", '".$type."', '".$this->db->idate($now)."',";
		$sql.= " '".$this->db->idate($this->dateh)."',";
		$sql.= " '".$this->db->idate($this->datef)."',";
		$sql.= " ".$this->amount.",";
		$sql.= " '".$this->db->escape($this->note)."')";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
		    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."subscription");
		    return $this->id;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Method to load a consumption
	 *
	 *  @param	int		$rowid		Id consumption
	 *  @return	int					<0 if KO, =0 if not found, >0 if OK
	 */
	function fetch($rowid)
	{
    $sql = "SELECT c.rowid,c.entity,c.date_creation,c.fk_member,c.fk_product,c.qty";    
    $sql.= " FROM ".MAIN_DB_PREFIX."adherent_consumption as c";
    $sql.= " WHERE c.rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->fk_member      = $obj->fk_member;
				$this->date_creation  = $this->db->jdate($obj->date_creation);
				$this->fk_product     = $obj->fk_product;
        $prodtmp=new Product($this->db);
        $prodtmp->fetch($obj->fk_product);
        $this->label          = $obj->label;
        $this->qty            = $obj->qty;

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Update subscription
	 *
	 *	@param	User	$user			User who updated
	 *	@param 	int		$notrigger		0=Disable triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function update($user,$notrigger=0)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."subscription SET ";
		$sql .= " fk_adherent = ".$this->fk_adherent.",";
		$sql .= " note=".($this->note ? "'".$this->db->escape($this->note)."'" : 'null').",";
		$sql .= " subscription = '".price2num($this->amount)."',";
		$sql .= " dateadh='".$this->db->idate($this->dateh)."',";
		$sql .= " datef='".$this->db->idate($this->datef)."',";
		$sql .= " datec='".$this->db->idate($this->datec)."',";
		$sql .= " fk_bank = ".($this->fk_bank ? $this->fk_bank : 'null');
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$member=new AdherentPlus($this->db);
			$result=$member->fetch($this->fk_adherent);
			$result=$member->update_end_date($user);

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
	 *	Delete a subscription
	 *
	 *	@param	User	$user		User that delete
	 *	@return	int					<0 if KO, 0 if not found, >0 if OK
	 */
	function delete($user)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."consumption WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowSubscription").': '.$this->ref;

        $link = '<a href="'.dol_buildpath('/adherentsplus/subscription/card.php?rowid='.$this->id.'', 1).'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='payment';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$link.$this->ref.$linkend;
		return $result;
	}


	/**
	 *  Retourne le libelle du statut d'une adhesion
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string				Label
	 */
	function getLibStatut($mode=0)
	{
	    return '';
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int			$statut      			Id statut
	 *  @return string      						Label
	 */
	function LibStatut($statut)
	{
	    global $langs;
	    $langs->load("members");
	    return '';
	}

    /**
     *  Load information of the subscription object
	 *
     *  @param	int		$id       Id subscription
     *  @return	void
     */
	function info($id)
	{
		$sql = 'SELECT c.rowid, c.datec,';
		$sql.= ' c.tms as datem';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'subscription as c';
		$sql.= ' WHERE c.rowid = '.$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}
}
