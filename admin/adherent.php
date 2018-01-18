<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      J. Fernando Lagrange <fernando@demo-tic.org>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2017      Ari Elbaz (elarifr)	<github@accedinfo.com>
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
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

// require '../../main.inc.php';
// Dolibarr environment
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/adherentsplus/lib/member.lib.php');
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("admin");
$langs->load("adherentsplus@adherentsplus");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');


/*
 * Actions
 */

//
if ($action == 'updateall')
{

    $db->begin();
    $res1=$res2=$res3=$res4=$res5=$res6=$res7=$res8=$res9=$res10=$res11=$res12=$res13=0;
    $res1=dolibarr_set_const($db, 'ADHERENT_LOGIN_NOT_REQUIRED', GETPOST('ADHERENT_LOGIN_NOT_REQUIRED', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res2=dolibarr_set_const($db, 'ADHERENT_MAIL_REQUIRED', GETPOST('ADHERENT_MAIL_REQUIRED', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res3=dolibarr_set_const($db, 'ADHERENT_DEFAULT_SENDINFOBYMAIL', GETPOST('ADHERENT_DEFAULT_SENDINFOBYMAIL', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res4=dolibarr_set_const($db, 'ADHERENT_MULTI_ONETHIRDPARTY', GETPOST('ADHERENT_MULTI_ONETHIRDPARTY', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res8=dolibarr_set_const($db, 'ADHERENT_BANK_USE', GETPOST('ADHERENT_BANK_USE', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res9=dolibarr_set_const($db, 'ADHERENT_SUBSCRIPTION_PRORATA', GETPOST('ADHERENT_SUBSCRIPTION_PRORATA', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res10=dolibarr_set_const($db, 'SOCIETE_SUBSCRIBE_MONTH_START', GETPOST('SOCIETE_SUBSCRIBE_MONTH_START', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res11=dolibarr_set_const($db, 'SOCIETE_SUBSCRIBE_MONTH_PRESTART', GETPOST('SOCIETE_SUBSCRIBE_MONTH_PRESTART', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res12=dolibarr_set_const($db, 'ADHERENT_WELCOME_MONTH', GETPOST('ADHERENT_WELCOME_MONTH', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res13=dolibarr_set_const($db, 'ADHERENT_MEMBER_CATEGORY', implode(",", GETPOST('ADHERENT_MEMBER_CATEGORY', 'array')), 'chaine', 0, '', $conf->entity);
    // Use vat for invoice creation
    if ($conf->facture->enabled)
    {
        $res5=dolibarr_set_const($db, 'ADHERENT_VAT_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_VAT_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
        $res6=dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
        if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
        {
            $res7=dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
        }
    }
    if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0 || $res8 < 0 || $res9 < 0 || $res10 < 0 || $res11 < 0 || $res12 < 0 || $res13 < 0)
    {
        setEventMessages('ErrorFailedToSaveDate', null, 'errors');
        $db->rollback();
    }
    else
    {
        setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
        $db->commit();
    }
}

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));

	if (($constname=='ADHERENT_CARD_TYPE' || $constname=='ADHERENT_ETIQUETTE_TYPE' || $constname=='ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && $constvalue == -1) $constvalue='';
	if ($constname=='ADHERENT_LOGIN_NOT_REQUIRED') // Invert choice
	{
		if ($constvalue) $constvalue=0;
		else $constvalue=1;
	}

	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name','alpha'),GETPOST('value'),'',0,'',$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,GETPOST('name','alpha'),$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersSetup"),$linkback,'title_setup');


$head = member_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Members"), -1, 'user');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updateall">';

print load_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


// Login/Pass required for members
print '<tr class="oddeven"><td>'.$langs->trans("AdherentLoginRequired").'</td><td>';
print $form->selectyesno('ADHERENT_LOGIN_NOT_REQUIRED',(! empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)?$conf->global->ADHERENT_LOGIN_NOT_REQUIRED:0),1);
print "</td></tr>\n";

// Mail required for members
print '<tr class="oddeven"><td>'.$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('ADHERENT_MAIL_REQUIRED',(! empty($conf->global->ADHERENT_MAIL_REQUIRED)?$conf->global->ADHERENT_MAIL_REQUIRED:0),1);
print "</td></tr>\n";

// Send mail information is on by default
print '<tr class="oddeven"><td>'.$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('ADHERENT_DEFAULT_SENDINFOBYMAIL',(! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?$conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL:0),1);
print "</td></tr>\n";

// multimembers for one thirdparty
print '<tr class="oddeven"><td>'.$langs->trans("AdherentMultiOneThirdparty").'</td><td>';
print $form->selectyesno('ADHERENT_MULTI_ONETHIRDPARTY',(! empty($conf->global->ADHERENT_MULTI_ONETHIRDPARTY)?$conf->global->ADHERENT_MULTI_ONETHIRDPARTY:0),1);
print "</td></tr>\n";

// Insert subscription into bank account
print '<tr class="oddeven"><td>'.$langs->trans("MoreActionsOnSubscription").'</td>';
$arraychoices=array('0'=>$langs->trans("None"));
if (! empty($conf->banque->enabled)) $arraychoices['bankdirect']=$langs->trans("MoreActionBankDirect");
if (! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled)) $arraychoices['invoiceonly']=$langs->trans("MoreActionInvoiceOnly");
if (! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled)) $arraychoices['bankviainvoice']=$langs->trans("MoreActionBankViaInvoice");
print '<td>';
print $form->selectarray('ADHERENT_BANK_USE',$arraychoices,$conf->global->ADHERENT_BANK_USE,0);
print '</td>';
print "</tr>\n";

// Use vat for invoice creation
if ($conf->facture->enabled)
{
	print '<tr class="oddeven"><td>'.$langs->trans("VATToUseForSubscriptions").'</td>';
	if (! empty($conf->banque->enabled))
	{
		print '<td>';
		print $form->selectarray('ADHERENT_VAT_FOR_SUBSCRIPTIONS', array('0'=>$langs->trans("NoVatOnSubscription"),'defaultforfoundationcountry'=>$langs->trans("Default")), (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS)?'0':$conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS), 0);
		print '</td>';
	}
	else
	{
		print '<td align="right">';
		print $langs->trans("WarningModuleNotActive",$langs->transnoentities("Module85Name"));
		print '</td>';
	}
	print "</tr>\n";
	
	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS").'</td>';
		print '<td>';
		$form->select_produits($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS');
		print '</td>';
	}
	print "</tr>\n";
}

// type of adhesion flow
print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_SUBSCRIPTION_PRORATA").'</td>';
print '<td>';
print $form->selectarray('ADHERENT_SUBSCRIPTION_PRORATA', array('0'=>$langs->trans("ADHERENT_SUBSCRIBE_NO_MONTH_START"),'1'=>$langs->trans("ADHERENT_SUBSCRIPTION_ANNUAL"),'2'=>$langs->trans("ADHERENT_SUBSCRIPTION_SEM"),'3'=>$langs->trans("ADHERENT_SUBSCRIPTION_QUA"),'4'=>$langs->trans("ADHERENT_SUBSCRIPTION_TRI"),'12'=>$langs->trans("ADHERENT_SUBSCRIPTION_MEN")), (empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA)?'0':$conf->global->ADHERENT_SUBSCRIPTION_PRORATA), 0);
print '</td>';
print "</tr>\n";

if ($conf->global->ADHERENT_SUBSCRIPTION_PRORATA > '0')
	{ 
// Insert subscription into bank account
print '<tr class="oddeven"><td>'.$langs->trans("FiscalMonthStart").'</td>';
print '<td>';
print $formother->select_month($conf->global->SOCIETE_SUBSCRIBE_MONTH_START,'SOCIETE_SUBSCRIBE_MONTH_START',0,1);
//print $form->selectarray('SOCIETE_SUBSCRIBE_MONTH_START', array('1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12'), (empty($conf->global->SOCIETE_SUBSCRIBE_MONTH_START)?'0':$conf->global->SOCIETE_SUBSCRIBE_MONTH_START), 0);
print '</td>';
print "</tr>\n";
}

// presale for next membership
print '<tr class="oddeven"><td>'.$langs->trans("SOCIETE_SUBSCRIBE_MONTH_PRESTART").'</td>';
print '<td>';
print $form->selectarray('SOCIETE_SUBSCRIBE_MONTH_PRESTART', array('0'=>'0','1'=>'1','2'=>'2','3'=>'3','4'=>'4'), (empty($conf->global->SOCIETE_SUBSCRIBE_MONTH_PRESTART)?'0':$conf->global->SOCIETE_SUBSCRIBE_MONTH_PRESTART), 0);
print $langs->trans("monthbefore").'</td>';
print "</tr>\n";

// time before renewing welcome fee
print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_WELCOME_MONTH").'</td>';
print '<td>';
print $form->selectarray('ADHERENT_WELCOME_MONTH', array('0'=>'Uniquement la première fois','1'=>'Exigés 1 mois après la fin d\'adhésion','2'=>'Exigés 2 mois après la fin d\'adhésion','3'=>'Exigés 3 mois après la fin d\'adhésion','4'=>'Exigés 4 mois après la fin d\'adhésion','5'=>'Exigés 5 mois après la fin d\'adhésion','6'=>'Exigés 6 mois après la fin d\'adhésion','12'=>'Exigés 12 mois après la fin d\'adhésion'), (empty($conf->global->ADHERENT_WELCOME_MONTH)?'0':$conf->global->ADHERENT_WELCOME_MONTH), 0);
print '</td>';
print "</tr>\n";

				// Customer
				if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire)) {
					print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_MEMBER_CATEGORY").'</td>';
					print '<td>';
					$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, null, null, null, 1);
					$c = new Categorie($db);
					$cats = $c->containing($conf->global->ADHERENT_MEMBER_CATEGORY, Categorie::TYPE_PRODUCT);
					foreach ($cats as $cat) {
						$arrayselected[] = $cat->id;
            print $cat->id;
					}
					print $form->multiselectarray('ADHERENT_MEMBER_CATEGORY', $cate_arbo, array($conf->global->ADHERENT_MEMBER_CATEGORY), '', 0, '', 0, '90%');
					print "</td></tr>";
				}

print '</table>';

print '<center>';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</center>';

print '</form>';

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array(
		'ADHERENT_CARD_TYPE',
//		'ADHERENT_CARD_BACKGROUND',
		'ADHERENT_CARD_HEADER_TEXT',
		'ADHERENT_CARD_TEXT',
		'ADHERENT_CARD_TEXT_RIGHT',
		'ADHERENT_CARD_FOOTER_TEXT'
		);

print load_fiche_titre($langs->trans("MembersCards"),'','');

$helptext='*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext.='%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
$helptext.='%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
$helptext.='%YEAR%, %MONTH%, %DAY%';

form_constantes($constantes, 0, $helptext);

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array('ADHERENT_ETIQUETTE_TYPE','ADHERENT_ETIQUETTE_TEXT');

print load_fiche_titre($langs->trans("MembersTickets"),'','');

$helptext='*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext.='%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
$helptext.='%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
$helptext.='%YEAR%, %MONTH%, %DAY%';

form_constantes($constantes, 0, $helptext);

print '<br>';


/*
 * Editing global variables not related to a specific theme
 */
$constantes=array(
		'ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT',
		'ADHERENT_AUTOREGISTER_NOTIF_MAIL',
		'ADHERENT_AUTOREGISTER_MAIL_SUBJECT',
		'ADHERENT_AUTOREGISTER_MAIL',
		'ADHERENT_MAIL_VALID_SUBJECT',
		'ADHERENT_MAIL_VALID',
		'ADHERENT_MAIL_COTIS_SUBJECT',
		'ADHERENT_MAIL_COTIS',
		'ADHERENT_MAIL_RESIL_SUBJECT',
		'ADHERENT_MAIL_RESIL',
		'ADHERENT_MAIL_FROM',
		);

print load_fiche_titre($langs->trans("Other"),'','');

$helptext='*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext.='%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
$helptext.='%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
$helptext.='%YEAR%, %MONTH%, %DAY%';

$helptext='*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext.='%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
$helptext.='%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
//$helptext.='%YEAR%, %MONTH%, %DAY%';	// Not supported

form_constantes($constantes, 0, $helptext);

dol_fiche_end();


llxFooter();

$db->close();