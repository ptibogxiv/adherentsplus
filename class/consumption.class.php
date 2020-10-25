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
	public $table_element='adherent_consumption';
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
	 *	Function who permitted cretaion of the consumption
     *
     *	@param	User	$user			User that create
     *	@param  bool 	$notrigger 		false=launch triggers after, true=disable triggers
     *	@return	int						<0 if KO, Id subscription created if OK
     */
    public function create($user, $notrigger = false)
    {
        global $conf, $langs;

        $error = 0;

        $now = dol_now();

        $this->date_creation = $now;
        if (empty($this->date_start)) $this->date_start = $this->date_creation;
        if (empty($this->date_end)) $this->date_end = $this->date_start; 
        if (empty($this->entity)) $this->entity = $conf->entity;

        $this->db->begin();
    
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_consumption (entity, fk_adherent, fk_product, qty, remise_percent, date_creation, date_start, date_end, fk_user_author)";
    $sql .= " VALUES (".$this->entity.", '".$this->db->escape($this->fk_adherent)."', '".$this->db->escape($this->fk_product)."', '".$this->db->escape($this->qty)."', '0',";
    $sql .= " '".$this->db->idate($this->date_creation)."',";
    $sql .= " ".($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "null").",";  	
		$sql .= " ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null").",";
    $sql .= " ".$user->id.")";

        dol_syslog(get_class($this)."::create", LOG_ERR);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
        }

        if (!$error && !$notrigger) {
        require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
        $member = new Adherent($this->db);
        $result = $member->fetch($this->fk_adherent);
        
        	$this->context = array('member'=>$member);
        	// Call triggers
            $result = $this->call_trigger('MEMBER_CONSUMPTION_CREATE', $user);
            if ($result < 0) { $error++; }
            // End call triggers
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return $this->id;
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
  
    $sql ="SELECT t.rowid, t.entity, t.fk_adherent, t.fk_product, t.fk_facture, t.date_creation";
		$sql.=" , t.qty, t.tms, t.fk_facture, t.date_start, t.date_end, t.fk_user_author, t.fk_user_modif";
		$sql.=" , p.ref as ref, p.label as label, p.description as description, p.fk_product_type";
		$sql.=" FROM ".MAIN_DB_PREFIX."adherent_consumption as t";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";     
		$sql.="	WHERE t.rowid=".$rowid;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref;
				$this->entity         = $obj->entity;   
				$this->fk_adherent    = $obj->fk_adherent;
				$this->fk_product     = $obj->fk_product;
				$this->product_type   = $obj->fk_product_type;
				$this->fk_facture     = $obj->fk_facture;
				$this->label          = $obj->label;
				$this->description    = $obj->description;
				$this->qty            = $obj->qty;
				$this->date_start       = $this->db->jdate($obj->date_start);
				$this->date_end         = $this->db->jdate($obj->date_end);
				$this->date_creation  = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_modif  = $obj->fk_user_modif;
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


	/**
	 *	Update consumption
	 *
	 *	@param	User	$user			User who updated
	 *	@param 	int		$notrigger		0=Disable triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
    public function update($user, $notrigger = 0)
    {
        global $langs;
        $error = 0;

        if (isset($this->fk_facture) && !empty($this->fk_facture)) {
            $error++;
            $this->error = $langs->trans("ConsumptionAlreadyBilled");
            return 0;
        }

        $this->db->begin();

		    $sql = "UPDATE ".MAIN_DB_PREFIX."adherent_consumption SET";
		    $sql .= " qty = ".$this->qty.",";
        if (!empty($this->fk_product)) $sql .= " fk_product ='".$this->fk_product."',";
        if (!empty($this->fk_facture)) $sql .= " fk_facture ='".$this->fk_facture."',";
        if (!empty($this->date_start)) $sql.= " date_start='".$this->db->idate($this->date_start)."',";
        if (!empty($this->date_end)) $sql.= " date_end='".$this->db->idate($this->date_end)."',";
        $sql .= " fk_user_modif = ".$user->id;
        $sql .= " WHERE fk_facture IS NULL AND fk_adherent = ".$this->fk_adherent;
        $sql .= " AND rowid = ".$this->id;

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
            $member = new Adherent($this->db);
            $result = $member->fetch($this->fk_adherent);

            if (!$error && !$notrigger) {
            	$this->context = array('member'=>$member);
            	// Call triggers
                $result = $this->call_trigger('MEMBER_CONSUMPTION_MODIFY', $user);
                if ($result < 0) { $error++; } //Do also here what you must do to rollback action if trigger fail
                // End call triggers
            }
        } else {
            $error++;
            $this->error = $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

  
	/**
	 *  Fonction qui supprime le souhait
	 *
	 *	@param	User		$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return	int					<0 if KO, 0=nothing to do, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
    {
        global $langs;
        $error = 0;

        if (!empty($this->fk_facture)) {
            $error++;
            $this->errors = $langs->trans("ConsumptionAlreadyBilled");
            return 0;
        }

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Call triggers
                $result = $this->call_trigger('MEMBER_CONSUMPTION_DELETE', $user);
                if ($result < 0) { $error++; } // Do also here what you must do to rollback action if trigger fail
                // End call triggers
            }
        }

		if (!$error )
		{
			      // Delete object
			      $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_consumption WHERE entity = ".$this->entity." AND rowid = ".$this->id;
            dol_syslog(get_class($this)."::delete rowid=".$this->id, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql) {
                $num = $this->db->affected_rows($resql);
                if ($num) {
                        $this->db->commit();
                        return 1;
                } else {
                    $this->db->commit();
                    return 0;
                }
            } else {
                $error++;
                $this->error = $this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }


}
