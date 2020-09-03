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
 *	\file       htdocs/takepos/takepos_consumption.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter payments
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER', '1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB', '1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

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
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');

$action = GETPOST('action', 'alpha');
$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant
$invoiceid = GETPOST('invoiceid', 'int');
$type = GETPOST('type', 'int');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}

$constforcompanyid = $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]};


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

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

$langs->loadLangs(array("main", "bills", "cashdesk", "members", "products", "adherentsplus@adherentsplus"));

?>
<link rel="stylesheet" href="/takepos/css/pos.css.php">
<?php
if ($conf->global->TAKEPOS_COLOR_THEME == 1) print '<link rel="stylesheet" href="css/colorful.css">';
?>
</head>
<body>
<?php 

if ($action == "change") // change member from POS
{
		$idmember = GETPOST('$idmember', 'int');
    
    $adh = new AdherentPlus($db);
    $adh->fetch('', '', $invoice->socid);
    if (!empty($type) && empty($adh->statut)) {
    $result = $adh->validate($user); 
    }
    if (empty($type)) {
    $result = $adh->resiliate($user);    
    } elseif ($adh->typeid != $type) {
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
$result=$adh->fetch('', '', $invoice->socid, '', '', '', 1);
$adht = new AdherentTypePlus($db);

$result=$adht->fetch($adh->typeid);
  $morehtmlright= dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create');

      print load_fiche_titre($langs->trans("ListOfProductsServices"), $morehtmlright, '');

      print '<div class="div-table-responsive">';
      print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

            print '<tr class="liste_titre">';
            print '<td align="center">'.$langs->trans("Date").'</td>';
            print '<td align="center">'.$langs->trans("Product/Service").'</td>';
            print '<td align="center">'.$langs->trans("Quantity").'</td>';
            print '<td align="right">'.$langs->trans("Price").'</td>';
            print '<td align="right">'.$langs->trans('Invoice').'</td>';
            print "</tr>\n";

            foreach ($adh->consumptions as $consumption)
            {

                print "<tr ".$bc[$var].">";

                print '<td>'.dol_print_date($consumption->date_creation,'dayhour')."</td>\n";
                print '<td align="center">';
                $prodtmp=new Product($db);
                $prodtmp->fetch($consumption->fk_product);
                print $prodtmp->getNomUrl(1);	// must use noentitiesnoconv to avoid to encode html into getNomUrl of product
                print ' - '.$prodtmp->label.'</td>';
                print '<td align="center">'; 
                             
                if ($prodtmp->isService() && $prodtmp->duration_value> 0)
            {
                print $consumption->value." "; 
                if ($prodtmp->duration_value > 1)
                {
                    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                }
                else if ($prodtmp->duration_value > 0)
                {
                    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                }
                print (! empty($prodtmp->duration_unit) && isset($dur[$prodtmp->duration_unit]) ? $langs->trans($dur[$prodtmp->duration_unit]) : '')."</td>\n";                  
            } else {
                print $consumption->qty." </td>\n";  
            }   
        
                print '<td align="right">'.$consumption->amount.'</td>';
                print '<td align="right">'.dol_print_date($consumption->date_validation,'day').'</td>';
                print "</tr>";

            }
            print "</table></div>";
?>
</div>
<?php } else {
print '<center>'.$langs->trans('MembershipNotAllowedForGenericCustomer').'</center>';
 } ?>
</body>
</html>
