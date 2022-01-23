<?php
/* Copyright (C) 2022	    ThibaulT FOUCART	<support@ptibogxiv.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       /adherentsplus/core/triggers/interface_90_modAdherentsPlus_AdherentsPlus.class.php
 *      \ingroup    adherentsplus
 *      \brief      Trigger file for create multicompany data
 */


/**
 *      \class      InterfaceAdherentsplus
 *      \brief      Classe des fonctions triggers des actions personnalisees du module adherentsplus
 */

class InterfaceAdherentsplus extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

	/**
	 *   Constructor
	 *
	 *   @param      DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i','',get_class($this));
		$this->family = "adherentsplus";
		$this->description = "Triggers of the module AdherentsPlus";
		$this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}

	/**
	 * Trigger name
	 *
	 * 	@return		string	Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * 	@return		string	Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Trigger version
	 *
	 * 	@return		string	Version of trigger file
	 */
	public function getVersion()
	{
		global $langs;

		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("Development");
		elseif ($this->version == 'experimental') return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("Unknown");
	}

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string        $action     Event action code
     * @param CommonObject  $object     Object
     * @param User          $user       Object user
     * @param Translate     $langs      Object langs
     * @param Conf          $conf       Object conf
     * @return int                      <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
global $db, $conf, $mysoc, $langs;

// Load translation files required by the page
$langs->loadLangs(array("members", "users", "mails", "other"));

/** Users */
$ok=0; 
  
if ($action == 'BILL_PAYED') {
			dol_syslog(
				"Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
			); 
         
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php'); 
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
      
        $sql1 = "SELECT fk_product, date_start, date_end, total_ttc";               
        $sql1.= " FROM ".MAIN_DB_PREFIX."facturedet";
        $sql1.= " WHERE fk_facture=".$object->id." ";
        
        $result1 = $db->query($sql1);
        if ($result1)
        {
            $num = $db->num_rows($result1);
            $i = 0;

            $var=True;
            while ($i < $num)
            {            
                $objp1 = $db->fetch_object($result1);
                $var=!$var;  
              
if ($objp1->fk_product==$conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS) { 

$member=new AdherentPlus($db);
$member->fetch('','',$object->socid);
$adht = new AdherentTypePlus($db);
$adht->fetch($member->typeid);

if (empty($objp1->date_start)) {
$datefrom = $member->next_subscription_date_start;
} else {
$datefrom = strtotime($objp1->date_start);
}

if (empty($objp1->date_end)) {
$dateto = $member->next_subscription_date_end;
} else {
$dateto = strtotime($objp1->date_end);
}

$d = strftime("%Y",$datefrom);
$f = strftime("%Y",$dateto);
if ($d==$f) {
$season=$d;
}else{
$season=$d."/".$f;
}

if ($member->id >0) {
if (empty($conf->global->MEMBER_NO_DEFAULT_LABEL)) {
$adhesion=$langs->trans("Subscription").' '.$season;
} else{
$adhesion=$conf->global->MEMBER_NO_DEFAULT_LABEL;
}
        $sql2 = "SELECT f.fk_facture,f.fk_paiement,p.rowid, p.fk_bank as bank";               
        $sql2.= " FROM ".MAIN_DB_PREFIX."paiement_facture as f";
        $sql2.= " JOIN ".MAIN_DB_PREFIX."paiement as p on p.rowid=f.fk_paiement";
        $sql2.= " WHERE f.fk_facture=".$object->id." ";
        
		$result2 = $db->query($sql2);
    if ($result2)
		{
			if ($db->num_rows($result2))
			{
				$obj2 = $db->fetch_object($result2);
    $bankkey=$obj2->bank;
    }
    }

$idcot=$member->subscription($datefrom, $objp1->total_ttc, $bankkey, '', $adhesion, '', '', '', $dateto); 

if ($idcot>0) {
$db->begin();
$sql3 = 'UPDATE '.MAIN_DB_PREFIX.'subscription SET fk_bank='.$bankkey;
$sql3.= ' WHERE rowid='.$idcot;
$result3 = $db->query($sql3);
$db->commit();

$invoice = new Facture($db);
$invoice->fetch($object->id);
$invoice->add_object_linked('subscription', $idcot); 
} 

        // Send email
        //if (! $error)
        //{
            // Send confirmation Email
            if ($idcot>0 && $member->email && ! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL))   // $object is 'Adherent'
            {
				$subject = '';
				$msg= '';

				// Send subscription email
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail=new FormMail($db);
				// Set output language
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang(empty($member->thirdparty->default_lang) ? $mysoc->default_lang : $member->thirdparty->default_lang);
				// Load traductions files requiredby by page
				$outputlangs->loadLangs(array("main", "members"));
				// Get email content from template
				$arraydefaultmessage=null;
				$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION;

				if (! empty($labeltouse)) $arraydefaultmessage=$formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

				if (! empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
				{
					$subject = $arraydefaultmessage->topic;
					$msg     = $arraydefaultmessage->content;
				}

				if (empty($labeltouse) || (int) $labeltouse === -1) {
					//fallback on the old configuration.
					setEventMessages('WarningMandatorySetupNotComplete', [], 'errors');
					$error++;
				}
				else {
					$substitutionarray=getCommonSubstitutionArray($outputlangs, 0, null, $member);
					complete_substitutions_array($substitutionarray, $outputlangs, $member);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

					$moreinheader='X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

					$result=$member->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
					if ($result < 0)
					{
						$error++;
						setEventMessages($member->error, $member->errors, 'errors');
					}
				}
            }

       // }
                             
}                    
            } 
$i++;
        }  }
    	} else if ($action == 'MEMBER_VALIDATE' || ($action == 'MEMBER_MODIFY' && !empty($object->statut)) ){
if (! empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->fk_soc)){
  dol_include_once('/adherentsplus/class/adherent_type.class.php');
  $type=new AdherentTypePlus($db);
  $type->fetch($object->typeid);

	$soc = new Societe($db);
	$soc->fetch($object->fk_soc);
	$soc->setPriceLevel($type->price_level, $user);
} 
    	}  else if ($action == 'MEMBER_RESILIATE' || $action == 'MEMBER_DELETE' ){
if (! empty($conf->global->PRODUIT_MULTIPRICES) && !empty($object->fk_soc)){
	$soc = new Societe($db);
	$soc->fetch($object->fk_soc);
	$soc->setPriceLevel('1', $user);
} 
}  else if ($action == 'MEMBER_SUBSCRIPTION_CREATE' && !empty($conf->global->ADHERENT_FEDERAL_PART)){
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
$object = new FactureFournisseur($db);

$object->ref_supplier = $object->note;
$object->socid				= $conf->global->ADHERENT_FEDERAL_PART;
$object->label				= $object->note;
$object->date = $object->datec;
$object->multicurrency_code	= GETPOST('multicurrency_code', 'alpha');
$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');
$object->create($user);

    	}    
              
          
 
		return $ok;
	}
}
