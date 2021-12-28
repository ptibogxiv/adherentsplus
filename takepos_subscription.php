<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/takepos_subscription.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter payments
 */

$res = 0;
if (! $res && file_exists("../main.inc.php"))
{
	$res = @include "../main.inc.php";
}
if (! $res && file_exists("../../main.inc.php"))
{
	$res = @include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php"))
{
	$res = @include "../../../main.inc.php";
}
if (! $res)
{
	die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';


$action = GETPOST('action', 'alpha');
$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant
$invoiceid = GETPOST('invoiceid', 'int');
$type = GETPOST('type', 'int');

if (empty($user->rights->takepos->run)) {
	$message = null;
	if ($constforcompanyid == $invoice->socid || empty($invoice->socid))  $message = $langs->trans('MembershipNotAllowedForGenericCustomer');
		accessforbidden($message);
	}

$constforcompanyid = $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]};

$form = new Form($db);
$invoice = new Facture($db);
if ($invoiceid > 0)
{
    $invoice->fetch($invoiceid);
}
else
{
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $invoiceid = $obj->rowid;
    }
    if (!$invoiceid)
    {
        $invoiceid = 0; // Invoice does not exist yet
    }
    else
    {
        $invoice->fetch($invoiceid);
    }
}

$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs = array();

//top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

$langs->loadLangs(array("main", "bills", "cashdesk", "members", "banks", "adherentsplus@adherentsplus"));

?>
</head>
<body>
<?php 
		// Confirm validate member
		if ($action == 'valid') {
    
			$adh = new Adherent($db);
      $adh->fetch('', '', $invoice->socid);
      $result = $adh->validate($user);
    
		$adht = new AdherentType($db);
		$adht->fetch($adh->typeid);

		if ($result >= 0 && !count($adh->errors)) {
			// Send confirmation email (according to parameters of member type. Otherwise generic)
			if ($adh->email && GETPOST("send_mail")) {
				$subject = '';
				$msg = '';

				// Send subscription email
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				// Set output language
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang(empty($adh->thirdparty->default_lang) ? $mysoc->default_lang : $adh->thirdparty->default_lang);
				// Load traductions files required by page
				$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
				// Get email content from template
				$arraydefaultmessage = null;
				$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION;

				if (!empty($labeltouse)) {
					$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
				}

				if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
					$subject = $arraydefaultmessage->topic;
					$msg     = $arraydefaultmessage->content;
				}

				if (empty($labeltouse) || (int) $labeltouse === -1) {
					//fallback on the old configuration.
					setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
					$error++;
				} else {
					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $adh);
					complete_substitutions_array($substitutionarray, $outputlangs, $adh);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

					$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

					$result = $adh->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
					if ($result < 0) {
						$error++;
						setEventMessages($adh->error, $adh->errors, 'errors');
					}
				}
			}
    ?>
	    <script>
	    console.log("Reload page invoice.php with place=<?php print $place; ?>");
	    parent.$("#poslines").load("invoice.php?place=<?php print $place; ?>", function() {
	        //parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
			<?php if (!$result) { ?>
				alert('Error failed to update member on draft invoice.');
			<?php } ?>
	        parent.$.colorbox.close(); /* Close the popup */
	    });
	    </script>
    <?php
    exit;
		} else {
			$error++;
			if ($adh->error) {
				setEventMessages($adh->error, $adh->errors, 'errors');
			} else {
				setEventMessages($adh->error, $adh->errors, 'errors');
			}
		}
		$action = '';
} elseif ($action == "resiliate") // resiliate member from POS
{
			$adh = new Adherent($db);
      $adh->fetch('', '', $invoice->socid);
      $result = $adh->resiliate($user); 			
      
      $adht = new AdherentType($db);
			$adht->fetch($adh->typeid);

			if ($result >= 0 && !count($adh->errors)) {
				if ($adh->email) {  //&& GETPOST("send_mail")
					$subject = '';
					$msg = '';

					// Send subscription email
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(empty($adh->thirdparty->default_lang) ? $mysoc->default_lang : $adh->thirdparty->default_lang);
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_CANCELATION;

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
					}

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = $arraydefaultmessage->topic;
						$msg     = $arraydefaultmessage->content;
					}

					if (empty($labeltouse) || (int) $labeltouse === -1) {
						//fallback on the old configuration.
						setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
						$error++;
					} else {
						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $adh);
						complete_substitutions_array($substitutionarray, $outputlangs, $adh);
						$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
						$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

						$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

						$result = $adh->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
						if ($result < 0) {
							$error++;
							setEventMessages($adh->error, $adh->errors, 'errors');
						}
					}
				}
    ?>
	    <script>
	    console.log("Reload page invoice.php with place=<?php print $place; ?>");
	    parent.$("#poslines").load("invoice.php?place=<?php print $place; ?>", function() {
	        //parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
			<?php if (!$result) { ?>
				alert('Error failed to update member on draft invoice.');
			<?php } ?>
	        parent.$.colorbox.close(); /* Close the popup */
	    });
	    </script>
    <?php
    exit; 
			} else {
				$error++;

				if ($adh->error) {
					setEventMessages($adh->error, $adh->errors, 'errors');
				} else {
					setEventMessages($adh->error, $adh->errors, 'errors');
				}
				$action = '';
			}
} elseif ($action == "exclude") // exclude member from POS
{
			$adh = new Adherent($db);
      $adh->fetch('', '', $invoice->socid);
      $result = $adh->exclude($user); 			
      
      $adht = new AdherentType($db);
			$adht->fetch($adh->typeid);

			if ($result >= 0 && !count($adh->errors)) {
				if ($adh->email) {  //&& GETPOST("send_mail")
					$subject = '';
					$msg = '';

					// Send subscription email
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(empty($adh->thirdparty->default_lang) ? $mysoc->default_lang : $adh->thirdparty->default_lang);
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_EXCLUSION;

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
					}

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = $arraydefaultmessage->topic;
						$msg     = $arraydefaultmessage->content;
					}

					if (empty($labeltouse) || (int) $labeltouse === -1) {
						//fallback on the old configuration.
						setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
						$error++;
					} else {
						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $adh);
						complete_substitutions_array($substitutionarray, $outputlangs, $adh);
						$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
						$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnExclude()), $substitutionarray, $outputlangs);

						$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

						$result = $adh->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
						if ($result < 0) {
							$error++;
							setEventMessages($adh->error, $adh->errors, 'errors');
						}
					}
				}
    ?>
	    <script>
	    console.log("Reload page invoice.php with place=<?php print $place; ?>");
	    parent.$("#poslines").load("invoice.php?place=<?php print $place; ?>", function() {
	        //parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
			<?php if (!$result) { ?>
				alert('Error failed to update member on draft invoice.');
			<?php } ?>
	        parent.$.colorbox.close(); /* Close the popup */
	    });
	    </script>
    <?php
    exit;
			} else {
				$error++;

				if ($adh->error) {
					setEventMessages($adh->error, $adh->errors, 'errors');
				} else {
					setEventMessages($adh->error, $adh->errors, 'errors');
				}
				$action = '';
			}
} elseif ($action == "change") // change member from POS
{
		$idmember = GETPOST('$idmember', 'int');
    
    $adh = new AdherentPlus($db);
    $adh->fetch('', '', $invoice->socid);
    if (!empty($type) && empty($adh->statut)) {
    $result = $adh->validate($user); 
    }
    if ($adh->typeid != $type) {
    $adh->typeid = $type;
    $result = $adh->update($user);  
    } else {
    $result = 1;
    }
    
    if (!empty($type) && $result) {
        $membertype = new AdherentTypePlus($db); 
        $membertype->fetch($type);
        $membertype->subscription_calculator($adh->id);
				// Add line to draft invoice
				$idprodsubscription = 0;
        $label = $langs->trans("Subscription").' '.$membertype->season;
				if (!empty($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS) && (!empty($conf->product->enabled) || !empty($conf->service->enabled))) $idprodsubscription = $conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS;

				$vattouse = 0;
				if (isset($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) && $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS == 'defaultforfoundationcountry') {
					$vattouse = get_default_tva($mysoc, $mysoc, $idprodsubscription);
				}

			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facturedet where fk_facture=$invoiceid AND fk_product=$idprodsubscription";
			$resql = $db->query($sql);
			$row = $db->fetch_array($resql);
        if (!empty($row[0])) {
        $result = $invoice->updateline($row[0], $label, $membertype->price_prorata, 1, 0, $membertype->date_begin, $membertype->date_end, $vattouse, '', '', 'TTC', '', 1);
        } else {
        $result = $invoice->addline($label, 0, 1, $vattouse, 0, 0, $idprodsubscription, 0, $membertype->date_begin, $membertype->date_end, 0, 0, '', 'TTC', $membertype->price_prorata, 1);
        }
        
    }
    ?>
	    <script>
	    console.log("Reload page invoice.php with place=<?php print $place; ?>");
	    parent.$("#poslines").load("invoice.php?place=<?php print $place; ?>", function() {
	        //parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
			<?php if (!$result) { ?>
				alert('Error failed to update member on draft invoice.');
			<?php } ?>
	        parent.$.colorbox.close(); /* Close the popup */
	    });
	    </script>
    <?php
    exit;
}

if ($constforcompanyid != $invoice->socid && !empty($invoice->socid)) { 
$adh = new AdherentPlus($db);
$result = $adh->fetch('', '', $invoice->socid);
if (empty($adh->id) && !empty($invoice->socid)) {
$backtopage = dol_buildpath('/adherentsplus/takepos_subscription.php?place='.$place.'&invoiceid='.$invoiceid, 1);
header("Location: ".DOL_URL_ROOT."/adherents/card.php?&action=create&socid=".$invoice->socid."&optioncss=print&backtopage=".$backtopage);
exit;
}
$adht = new AdherentTypePlus($db);
$result=$adht->fetch($adh->typeid);
?>
<div style="position:relative; padding-top: 10px; left:5%; height:120px; width:92%;">
<center>
<div class="paymentbordline paymentbordlinetotal">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans("Status"); ?>: </font><span id="totaldisplay" class="colorwhite"><?php 
echo $adh->getLibStatut(0); ?></span></font></span></center>
</div>
<div class="paymentbordline paymentbordlinetotal">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans("Type"); ?>: </font><span id="totaldisplay" class="colorwhite"><?php 
echo $adht->libelle; ?></span></font></span></center>
</div>
<div class="paymentbordline paymentbordlinetotal">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans("SubscriptionEndDate"); ?>: </font><span id="totaldisplay" class="colorwhite"><?php 
	if ($adh->datefin)
	{
	    echo dol_print_date($adh->datefin, 'day');
	    if ($adh->hasDelay()) {
	        echo " ".img_warning($langs->trans("Late"));
	    }
	}
	else
	{
	    if (!$adht->subscription)
	    {
	        echo $langs->trans("SubscriptionNotRecorded");
	        if ($adh->statut > 0) echo " ".img_warning($langs->trans("Late")); // Display a delay picto only if it is not a draft and is not canceled
	    }
	    else
	    {
	        echo $langs->trans("SubscriptionNotReceived");
	        if ($adh->statut > 0) echo " ".img_warning($langs->trans("Late")); // Display a delay picto only if it is not a draft and is not canceled
	    }
	} ?></span></font></span></center>
</div>
<div class="paymentbordline paymentbordlinereceived">
    <center><span class="takepospay"><font color="white"><?php echo $langs->trans("Commitment"); ?>: </font><span id="totaldisplay" class="colorwhite"><?php 
    		if ($adh->datecommitment)
		{
			echo dol_print_date($adh->datecommitment,'day');
			if ($adh->hasDelay()) {
				echo " ".img_warning($langs->trans("Late"));
			}
		} else {
echo $langs->trans("None");
    }
    ?></span></font></span></center>
    </div>
</center>
</div>

<div style="position:absolute; left:5%; height:52%; width:92%;">
<?php
if ($user->rights->adherent->cotisation->creer && $adh->statut != -2) {
	$sql = "SELECT d.rowid as rowid, d.libelle as label, d.subscription, d.vote, d.morphy, d.tms as tms";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql .= " WHERE d.entity IN (".getEntity('member_type').")";
	$sql .= " AND d.statut = '1'";  
  $sql .= " ORDER BY ".(!empty($conf->global->TAKEPOS_SORTPRODUCTFIELD)?$conf->global->TAKEPOS_SORTPRODUCTFIELD:'rowid')." ASC";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;
    
		while ($i < $num) {
			$objp = $db->fetch_object($result);
      $membertype = new AdherentTypePlus($db); 
      $membertype->fetch($objp->rowid);
      $membertype->subscription_calculator($adh->id);
if (empty($membertype->morphy) || ($membertype->morphy == $adh->morphy)) {
if ($adh->datefin < $membertype->date_end) {    
print '<button type="button" class="';
if ($membertype->id == $adh->typeid) { 
print "calcbutton poscolorblue";
} else {
print "calcbutton poscolordelete";
}
print '" onclick="location.href=\'takepos_subscription.php?action=change&idmember='.$adh->id.'&type='.$membertype->id.'&invoiceid='.$invoiceid.'&place='.urlencode($place).'\'" >'.dol_escape_htmltag($membertype->label).'<br><small>';
print '('.price($membertype->price_prorata).' '.$langs->trans("Currency".$conf->currency);
if ($membertype->price_prorata != $membertype->nextprice) { print ' '.$langs->trans("then").' '.price($membertype->nextprice).' '.$langs->trans("Currency".$conf->currency); }
print ')<br>';
print dol_print_date($membertype->date_begin, 'day').' - '.dol_print_date($membertype->date_end, 'day');
print '</small></button>';
} else {
print '<button type="button" class="';
if ($membertype->id == $adh->typeid) { 
print "calcbutton poscolorblue";
} else {
print "calcbutton poscolordelete";
}
print '" disabled="disabled">'.dol_escape_htmltag($membertype->label).'<br><small>';
print '('.price($membertype->price_prorata).' '.$langs->trans("Currency".$conf->currency);
if ($membertype->price_prorata != $membertype->nextprice) { print ' '.$langs->trans("then").' '.price($membertype->nextprice).' '.$langs->trans("Currency".$conf->currency); }
print ')<br>';
print $langs->trans("Disabled");
print '</small></button>';
}
}
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}

				// Resiliate
				if (Adherent::STATUS_VALIDATED == $adh->statut) {
					if ($user->rights->adherent->supprimer) {
						print '<button type="button" class="calcbutton2" onclick="location.href=\'takepos_subscription.php?action=resiliate&idmember='.$adh->id.'&type=0&invoiceid='.$invoiceid.'&place='.urlencode($place).'\'">'.$langs->trans("Resiliate").'</button>';
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Resiliate").'</span>'."\n";
					}
				}
        
				// Exclude
				if (Adherent::STATUS_VALIDATED == $adh->statut) {
					if ($user->rights->adherent->supprimer) {
						print '<button type="button" class="calcbutton2" onclick="location.href=\'takepos_subscription.php?action=exclude&idmember='.$adh->id.'&type=0&invoiceid='.$invoiceid.'&place='.urlencode($place).'\'">'.$langs->trans("Exclude").'</button>';
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Exclude").'</span>'."\n";
					}
				} 
}
				// Reactivate
				if (Adherent::STATUS_RESILIATED == $adh->statut || Adherent::STATUS_EXCLUDED == $adh->statut) {
					if ($user->rights->adherent->creer) {
						print '<button type="button" class="calcbutton2" onclick="location.href=\'takepos_subscription.php?action=valid&idmember='.$adh->id.'&type=0&invoiceid='.$invoiceid.'&place='.urlencode($place).'\'">'.$langs->trans("Reenable").'</button>';
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Reenable").'</span>'."\n";
					}
				}
if (!empty($adh->id)) {
?>
<div>
<?php
        $sql = "SELECT d.rowid, d.firstname, d.lastname, d.societe, d.fk_adherent_type as type,";
        $sql .= " c.rowid as crowid, c.subscription,";
        $sql .= " c.datec, c.fk_type as cfk_type,";
        $sql .= " c.dateadh as dateh,";
        $sql .= " c.datef,";
        $sql .= " c.fk_bank,";
        $sql .= " b.rowid as bid,";
        $sql .= " ba.rowid as baid, ba.label, ba.bank, ba.ref, ba.account_number, ba.fk_accountancy_journal, ba.number, ba.currency_code";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank = b.rowid";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
        $sql .= " WHERE d.rowid = c.fk_adherent AND d.rowid=".$adh->id;
        $sql .= " ORDER BY c.datef DESC LIMIT 4";

        $result = $db->query($sql);
        if ($result)
        {
            $subscriptionstatic = new Subscription($db);

            $num = $db->num_rows($result);

            print '<br><table class="centpercent"><tr><td>';
            $adh->fetch('', '', $invoice->socid);
            print $form->showphoto('memberphoto', $adh, 0, 0, 0, 'photoref', 'small', 1, 0, 1);	
            print '</td><td><table class="noborder centpercent">'."\n";

            print '<tr class="liste_titre">';
            print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
            print_liste_field_titre('DateCreation', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre('Type', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre('DateStart', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre('DateEnd', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre('Amount', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
            if (!empty($conf->banque->enabled))
            {
            	print_liste_field_titre('Account', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
            }
            print "</tr>\n";

            $accountstatic = new Account($db);

            $i = 0;
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);

                $adh->id = $objp->rowid;
                $adh->typeid = $objp->type;

                $subscriptionstatic->ref = $objp->crowid;
                $subscriptionstatic->id = $objp->crowid;

                $typeid = ($objp->cfk_type > 0 ? $objp->cfk_type : $adh->typeid);
                if ($typeid > 0)
                {
                    $adht->fetch($typeid);
                }

                print '<br><tr class="oddeven">';
                print '<td>'.$objp->crowid.'</td>';
                print '<td class="center">'.dol_print_date($db->jdate($objp->datec), 'dayhour')."</td>\n";
                print '<td class="center">';
                if ($typeid > 0) {
                    print $adht->label;
                }
                print '</td>';
                print '<td class="center">'.dol_print_date($db->jdate($objp->dateh), 'dayhour')."</td>\n";
                print '<td class="center">'.dol_print_date($db->jdate($objp->datef), 'dayhour')."</td>\n";
                print '<td class="right">'.price($objp->subscription).'</td>';
				if (!empty($conf->banque->enabled))
				{
					print '<td class="right">';
					if ($objp->bid)
					{
						$accountstatic->label = $objp->label;
						$accountstatic->id = $objp->baid;
						$accountstatic->number = $objp->number;
						$accountstatic->account_number = $objp->account_number;
						$accountstatic->currency_code = $objp->currency_code;

						if (!empty($conf->accounting->enabled) && $objp->fk_accountancy_journal > 0)
						{
							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);

							$accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
						}

                        $accountstatic->ref = $objp->ref;
                        print $accountstatic->label;
                    }
                    else
                    {
                        print '&nbsp;';
                    }
                    print '</td>';
                }
                print "</tr>";
                $i++;
            }

            if (empty($num)) {
                $colspan = 6;
                if (!empty($conf->banque->enabled)) $colspan++;
                print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
            }

        }
        else
        {
            dol_print_error($db);
        }
            print "</table></td></tr></table>";
?>
</div><?php } ?></div>
<?php } else {
accessforbidden($langs->trans('MembershipNotAllowedForGenericCustomer'));
 } ?>
</body>
</html>
