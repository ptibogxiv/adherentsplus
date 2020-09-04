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
  
    $sql ="SELECT rowid, entity, fk_member, fk_product, fk_facture, product_type, label, description, date_creation,";
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
				$this->product_type   = $obj->product_type;
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
	 *  Update a line in database
	 *
	 *  @param    	int				$rowid            	Id of line to update
	 *  @param    	string			$desc             	Description of line
	 *  @param    	float			$pu               	Unit price
	 *  @param    	float			$qty              	Quantity
	 *  @param    	float			$remise_percent   	Percent of discount
	 *  @param    	float			$txtva           	Taux TVA
	 * 	@param		float			$txlocaltax1		Local tax 1 rate
	 *  @param		float			$txlocaltax2		Local tax 2 rate
	 *  @param    	string			$price_base_type	HT or TTC
	 *  @param    	int				$info_bits        	Miscellaneous informations on line
	 *  @param    	int				$date_start        	Start date of the line
	 *  @param    	int				$date_end          	End date of the line
	 * 	@param		int				$type				Type of line (0=product, 1=service)
	 * 	@param		int				$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param		int				$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 *  @param		int				$fk_fournprice		Id of origin supplier price
	 *  @param		int				$pa_ht				Price (without tax) of product when it was bought
	 *  @param		string			$label				Label
	 *  @param		int				$special_code		Special code (also used by externals modules!)
	 *  @param		array			$array_options		extrafields array
	 * 	@param 		string			$fk_unit 			Code of the unit to use. Null to use the default one
	 *  @param		double			$pu_ht_devise		Amount in currency
	 * 	@param		int				$notrigger			disable line update trigger
	 *  @return   	int              					< 0 if KO, > 0 if OK
	 */
	public function updateconsumption($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0.0, $txlocaltax2 = 0.0, $price_base_type = 'HT', $info_bits = 0, $date_start = '', $date_end = '', $type = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $special_code = 0, $array_options = 0, $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0)
	{
		global $conf, $mysoc, $langs, $user;

		dol_syslog(get_class($this)."::updateline id=$rowid, desc=$desc, pu=$pu, qty=$qty, remise_percent=$remise_percent, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, price_base_type=$price_base_type, info_bits=$info_bits, date_start=$date_start, date_end=$date_end, type=$type, fk_parent_line=$fk_parent_line, pa_ht=$pa_ht, special_code=$special_code");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		if ($this->statut == Commande::STATUS_DRAFT)
		{
			// Clean parameters
			if (empty($qty)) $qty = 0;
			if (empty($info_bits)) $info_bits = 0;
			if (empty($txtva)) $txtva = 0;
			if (empty($txlocaltax1)) $txlocaltax1 = 0;
			if (empty($txlocaltax2)) $txlocaltax2 = 0;
			if (empty($remise_percent)) $remise_percent = 0;
			if (empty($special_code) || $special_code == 3) $special_code = 0;

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$remise_percent = price2num($remise_percent);
			$qty = price2num($qty);
			$pu = price2num($pu);
			$pa_ht = price2num($pa_ht);
			$pu_ht_devise = price2num($pu_ht_devise);
			$txtva = price2num($txtva);
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);

			$this->db->begin();

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

			// Clean vat code
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg))
			{
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Anciens indicateurs: $price, $subprice (a ne plus utiliser)
			$price = $pu_ht;
			if ($price_base_type == 'TTC')
			{
				$subprice = $pu_ttc;
			} else {
				$subprice = $pu_ht;
			}
			$remise = 0;
			if ($remise_percent > 0)
			{
				$remise = round(($pu * $remise_percent / 100), 2);
				$price = ($pu - $remise);
			}

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new OrderLine($this->db);
			$line->fetch($rowid);
			$line->fetch_optionals();

			if (!empty($line->fk_product))
			{
				$product = new Product($this->db);
				$result = $product->fetch($line->fk_product);
				$product_type = $product->type;

				if (!empty($conf->global->STOCK_MUST_BE_ENOUGH_FOR_ORDER) && $product_type == 0 && $product->stock_reel < $qty)
				{
					$langs->load("errors");
					$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnOrder', $product->ref);
					$this->errors[] = $this->error;
					dol_syslog(get_class($this)."::addline error=Product ".$product->ref.": ".$this->error, LOG_ERR);
					$this->db->rollback();
					return self::STOCK_NOT_ENOUGH_FOR_ORDER;
				}
			}

			$staticline = clone $line;

			$line->oldline = $staticline;
			$this->line = $line;
			$this->line->context = $this->context;

			// Reorder if fk_parent_line change
			if (!empty($fk_parent_line) && !empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
			{
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			$this->line->id = $rowid;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty = $qty;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx         = $txtva;
			$this->line->localtax1_tx   = $txlocaltax1;
			$this->line->localtax2_tx   = $txlocaltax2;
			$this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice       = $subprice;
			$this->line->info_bits      = $info_bits;
			$this->line->special_code   = $special_code;
			$this->line->total_ht       = $total_ht;
			$this->line->total_tva      = $total_tva;
			$this->line->total_localtax1 = $total_localtax1;
			$this->line->total_localtax2 = $total_localtax2;
			$this->line->total_ttc      = $total_ttc;
			$this->line->date_start     = $date_start;
			$this->line->date_end       = $date_end;
			$this->line->product_type   = $type;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->skip_update_total = $skip_update_total;
			$this->line->fk_unit        = $fk_unit;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			// TODO deprecated
			$this->line->price = $price;
			$this->line->remise = $remise;

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$this->line->array_options[$key] = $array_options[$key];
				}
			}

			$result = $this->line->update($user, $notrigger);
			if ($result > 0)
			{
				// Reorder if child line
				if (!empty($fk_parent_line)) $this->line_order(true, 'DESC');

				// Mise a jour info denormalisees
				$this->update_price(1);

				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->line->error;

				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = get_class($this)."::update consumption status makes operation forbidden";
			$this->errors = array('OrderStatusMakeOperationForbidden');
			return -2;
		}
	}
  
	/**
	 *  Fonction qui supprime le souhait
	 *
	 *  @param	int		$rowid		Id of member to delete
	 *	@param	User		$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return	int					<0 if KO, 0=nothing to do, >0 if OK
	 */
	public function delete($rowid, $user, $notrigger = 0)
	{
		global $conf, $langs;

		$result = 0;
		$error=0;
		$errorflag=0;

		// Check parameters
		if (empty($rowid)) $rowid=$this->id;

		$this->db->begin();

		  // Remove wish
		  $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_consumption WHERE rowid = ".$rowid;
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag=-5;
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
