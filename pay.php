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
 *	\file       htdocs/takepos/pay.php
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
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant

$invoiceid = GETPOST('invoiceid', 'int');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}

$constforcompanyid = $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]};

/*
 * View
 */

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

$langs->loadLangs(array("main", "bills", "cashdesk", "members", "adherentsplus@adherentsplus"));

$sql = "SELECT code, libelle as label FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
        $paycode = $obj->code;
        if ($paycode == 'LIQ') $paycode = 'CASH';
        if ($paycode == 'CB')  $paycode = 'CB';
        if ($paycode == 'CHQ') $paycode = 'CHEQUE';

        $accountname = "CASHDESK_ID_BANKACCOUNT_".$paycode.$_SESSION["takeposterminal"];
		if (!empty($conf->global->$accountname) && $conf->global->$accountname > 0) array_push($paiements, $obj);
	}
}
?>
<link rel="stylesheet" href="css/pos.css.php">
<?php
if ($conf->global->TAKEPOS_COLOR_THEME == 1) print '<link rel="stylesheet" href="css/colorful.css">';
?>
</head>
<body>

<script>
<?php
$remaintopay = 0;
if ($invoice->id > 0)
{
    $remaintopay = $invoice->getRemainToPay();
}
$alreadypayed = (is_object($invoice) ? ($invoice->total_ttc - $remaintopay) : 0);

if ($conf->global->TAKEPOS_NUMPAD == 0) print "var received='';";
else print "var received=0;";

?>
	var alreadypayed = <?php echo $alreadypayed ?>;

	function addreceived(price)
	{
    	<?php
    	if (empty($conf->global->TAKEPOS_NUMPAD)) print 'received+=String(price);'."\n";
    	else print 'received+=parseFloat(price);'."\n";
    	?>
    	$('.change1').html(pricejs(parseFloat(received), 'MT'));
    	$('.change1').val(parseFloat(received));
		alreadypaydplusreceived=price2numjs(alreadypayed + parseFloat(received));
    	//console.log("already+received = "+alreadypaydplusreceived);
    	//console.log("total_ttc = "+<?php echo $invoice->total_ttc; ?>);
    	if (alreadypaydplusreceived > <?php echo $invoice->total_ttc; ?>)
   		{
			var change=parseFloat(alreadypayed + parseFloat(received) - <?php echo $invoice->total_ttc; ?>);
			$('.change2').html(pricejs(change, 'MT'));
	    	$('.change2').val(change);
	    	$('.change1').removeClass('colorred');
	    	$('.change1').addClass('colorgreen');
	    	$('.change2').removeClass('colorwhite');
	    	$('.change2').addClass('colorred');
		}
    	else
    	{
			$('.change2').html(pricejs(0, 'MT'));
	    	$('.change2').val(0);
	    	if (alreadypaydplusreceived == <?php echo $invoice->total_ttc; ?>)
	    	{
		    	$('.change1').removeClass('colorred');
		    	$('.change1').addClass('colorgreen');
	    		$('.change2').removeClass('colorred');
	    		$('.change2').addClass('colorwhite');
	    	}
	    	else
	    	{
		    	$('.change1').removeClass('colorgreen');
		    	$('.change1').addClass('colorred');
	    		$('.change2').removeClass('colorred');
	    		$('.change2').addClass('colorwhite');
	    	}
    	}
	}

	function reset()
	{
		received=0;
		$('.change1').html(pricejs(received, 'MT'));
		$('.change1').val(price2numjs(received));
		$('.change2').html(pricejs(received, 'MT'));
		$('.change2').val(price2numjs(received));
    	$('.change1').removeClass('colorgreen');
    	$('.change1').addClass('colorred');
    	$('.change2').removeClass('colorred');
    	$('.change2').addClass('colorwhite');
	}

	function Validate(payment)
	{
		var invoiceid = <?php echo ($invoiceid > 0 ? $invoiceid : 0); ?>;
		var amountpayed = $("#change1").val();
		if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
			amountpayed = <?php echo $invoice->total_ttc; ?>;
		}
		console.log("We click on the payment mode to pay amount = "+amountpayed);
		parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay="+payment+"&amount="+amountpayed+"&invoiceid="+invoiceid, function() {
		    if (amountpayed > <?php echo $remaintopay; ?> || amountpayed == <?php echo $remaintopay; ?> || amountpayed==0 ) parent.$.colorbox.close();
			else location.reload();
		});
	}

	function ValidateSumup() {
		console.log("Launch ValidateSumup");
		<?php $_SESSION['SMP_CURRENT_PAYMENT'] = "NEW" ?>
        var invoiceid = <?php echo($invoiceid > 0 ? $invoiceid : 0); ?>;
        var amountpayed = $("#change1").val();
        if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
            amountpayed = <?php echo $invoice->total_ttc; ?>;
        }

        // Starting sumup app
        window.open('sumupmerchant://pay/1.0?affiliate-key=<?php echo $conf->global->TAKEPOS_SUMUP_AFFILIATE ?>&app-id=<?php echo $conf->global->TAKEPOS_SUMUP_APPID ?>&total=' + amountpayed + '&currency=EUR&title=' + invoiceid + '&callback=<?php echo DOL_MAIN_URL_ROOT ?>/takepos/smpcb.php');

        var loop = window.setInterval(function () {
            $.ajax('/takepos/smpcb.php?status').done(function (data) {
                console.log(data);
                if (data === "SUCCESS") {
                    parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay=CB&amount=" + amountpayed + "&invoiceid=" + invoiceid, function () {
                        //parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
                        parent.$.colorbox.close();
                        //parent.setFocusOnSearchField();	// This does not have effect
                    });
                    clearInterval(loop);
                } else if (data === "FAILED") {
                    parent.$.colorbox.close();
                    clearInterval(loop);
                }
            });
        }, 2500);
    }
</script>
<?php //echo $invoice->socid.'/'.$constforcompanyid; ?>
<?php if ($constforcompanyid != $invoice->socid) { 
$adh = new AdherentPlus($db);
$result = $adh->fetch('', '', $invoice->socid);
$adht = new AdherentTypePlus($db);
$result=$adht->fetch($adh->typeid);
?>
<div style="position:relative; padding-top: 10px; left:5%; height:150px; width:91%;">
<center>
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
$action_buttons = array(
	array(
		"function" =>"reset()",
		"span" => "style='font-size: 150%;'",
		"text" => "C",
	    "class" => "poscolorblue"
	),
	array(
		"function" => "parent.$.colorbox.close();",
		"span" => "id='printtext' style='font-weight: bold; font-size: 18pt;'",
		"text" => "X",
	    "class" => "poscolordelete"
	),
);
	
	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.vote, d.statut as status, d.morphy";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql .= " WHERE d.entity IN (".getEntity('member_type').")";

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
      $membertype->fetch_optionals();
      $membertype->subscription_calculator();
      
print '<button type="button" class="calcbutton2" onclick="ValidateSumup();">'.dol_escape_htmltag($membertype->label).' ('.price($membertype->price_prorata).' '.$langs->trans("Currency".$conf->currency).')<br>';
print dol_print_date($membertype->date_begin, 'dayhour').' - '.dol_print_date($membertype->date_end, 'dayhour').'</button>';
			$i++;
		}
		print "</table>";
		print '</div>';

		print '</form>';
	}
	else
	{
		dol_print_error($db);
	}
?>
</div>
<?php } else {
print '<center>'.$langs->trans('MembershipNotAllowedForGenericCustomer').'</center>';
 } ?>
</body>
</html>
