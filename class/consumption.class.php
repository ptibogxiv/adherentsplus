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
	public function fetch($rowid)
	{
		global $conf, $langs;

		$result = 0;
		$error=0;
		$errorflag=0;

		$this->db->begin();
  
    $sql ="SELECT rowid, entity, fk_member, fk_product, fk_facture, label, description, date_creation,";
		$sql.=" qty, tms, fk_facture, date_start, date_end,";
		$sql.=" fk_user_author, fk_user_modif";
		$sql.=" FROM ".MAIN_DB_PREFIX."adherent_consumption";
		$sql.="	WHERE rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->fk_adherent    = $obj->fk_member;
				$this->fk_product     = $obj->fk_product;
				$this->fk_facture     = $obj->fk_facture;
				$this->label           = $obj->label;
				$this->description     = $obj->description;
				$this->qty            = $obj->qty;
				$this->date_start       = $this->db->jdate($obj->date_start);
				$this->date_end         = $this->db->jdate($obj->date_end);
				$this->date_creation  = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_modif  = $obj->fk_user_modif;

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
	public function update($user,$notrigger=0)
	{
		global $conf, $langs;

		$result = 0;
		$error=0;
		$errorflag=0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."subscription SET ";
		$sql .= " fk_adherent = ".$this->fk_adherent.",";
		$sql .= " note=".($this->note ? "'".$this->db->escape($this->note)."'" : 'null').",";
		$sql .= " subscription = '".price2num($this->amount)."',";
		$sql .= " dateadh='".$this->db->idate($this->dateh)."',";
		$sql .= " datef='".$this->db->idate($this->datef)."',";
		$sql .= " datec='".$this->db->idate($this->datec)."',";
		$sql .= " fk_bank = ".($this->fk_bank ? $this->fk_bank : 'null');
    $sql.= " WHERE entity IN (" . getEntity('adherent').")";
    $sql.= " AND fk_member = ".$this->id;
    $sql.= " AND rowid = ".$rowid;

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
	 *	Delete consumption in database
	 *
	 *  @param	int		$rowid		Id of consumption to delete
	 *  @return	int					<0 if KO, 0=nothing to do, >0 if OK
	 */
	public function delete($rowid)
	{
		global $conf, $langs;

		$result = 0;
		$error=0;
		$errorflag=0;

		$this->db->begin();

		// Remove wish
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_consumption WHERE rowid = ".$rowid;
		dol_syslog(get_class($this)."::deleteline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (!$result)
		{
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return $errorflag;
		}
	}


}
