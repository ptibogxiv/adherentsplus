<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
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
 *      \file       htdocs/adherents/linkedmember.php
 *      \ingroup    member
 *      \brief      Tab for note of a member
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

dol_include_once('/adherentsplus/lib/member.lib.php');
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->loadLangs(array('products', 'companies', 'members', 'bills', 'other'));

$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$backtopage=GETPOST('backtopage','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('rowid','int');
$link=GETPOST('link','int');

// Security check
$result=restrictedArea($user,'adherent',$id);

$object = new Adherentplus($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new AdherentTypePlus($db);
    $result=$adht->fetch($object->typeid);
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('adherent', 'membercard', $canvas);
}

$permissionnote=$user->rights->adherent->creer;  // Used by the include of actions_setnotes.inc.php


  if ($action == 'confirm_deleteparent' && $confirm == 'yes' && $user->rights->adherent->creer)
	{
 		$result=$object->delete_parent($link);
		if ($result > 0)
		{

				header("Location: ".$dolibarr_main_url_root.dol_buildpath('/adherentsplus/linkedmember.php?rowid='.$id, 1));
				exit;
		}
		else
		{
			$errmesg=$object->error;
		}
  }

/*
 * View
 */
$title=$langs->trans("Member") . " - " . $langs->trans("LinkedMembers");
$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
llxHeader("",$title,$helpurl);

$form = new Form($db);

if ($conf->global->ADHERENT_LINKEDMEMBER && $action=='deleteparent' && $user->rights->adherent->creer) {
$form = new Form($db);
$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?rowid='.$object->id.'&link='.$link, $langs->trans('Confirm'), $langs->trans('ConfirmDeleteParent'), 'confirm_deleteparent', '', 0, 1);
print $formconfirm;	
}

if ($id)
{
	$head = member_prepare_head($object);

	dol_fiche_head($head, 'linkedmember', $langs->trans("Member"), -1, 'user');

	print "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    $linkback = '<a href="'.dol_buildpath('/adherentsplus/list.php', 1).'">'.$langs->trans("BackToList").'</a>';
	
	dol_banner_tab($object, 'rowid', $linkback);
    
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    // Login
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
    {
        print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
    }

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans("LinkedToDolibarrThirdParty");
			print '</td>';
			if ($action != 'editthirdparty' && $user->rights->adherent->creer) print '<td align="right"></td>';
			print '</tr></table>';
			print '</td><td colspan="2" class="valeur">';
				if ($object->fk_soc)
				{
					$company=new Societe($db);
					$result=$company->fetch($object->fk_soc);
					print $company->getNomUrl(1);
				}
				else
				{
					print $langs->trans("NoThirdPartyAssociatedToMember");
				}
			print '</td></tr>';
		}

    // Company
    print '<tr><td>'.$langs->trans("NextInvoice").'</td><td class="valeur">'.dol_print_date($object->nextinvoice,'day').'</td></tr>';

    // Civility
    print '<tr><td>'.$langs->trans("Commitment").'</td><td class="valeur">';
		if ($object->datecommitment)
		{
			print dol_print_date($object->datecommitment,'day');
			if ($object->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		}
		else
		{
			if (! $adht->subscription)
			{
				print $langs->trans("SubscriptionNotRecorded");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
			else
			{
				print $langs->trans("Free");
			}
		}     
    print '</td>';
    print '</tr>';

    print "</table>";

    print '</div>';


    $cssclass='titlefield';
    $permission = $user->rights->adherent->creer;  // Used by the include of notes.tpl.php
    //include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

    dol_fiche_end();

}

    /*
    * List of consumptions
    */

            print '<table class="noborder" width="100%">'."\n";

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Name")." / ".$langs->trans("Company").'</td>';
            print '<td align="left">'.$langs->trans("Login").'</td>';
            print '<td align="left">'.$langs->trans("Nature").'</td>';
            print '<td align="left">'.$langs->trans("Email").'</td>';
            print '<td align="left">'.$langs->trans("Status").'</td>';
            print '<td align="left">'.$langs->trans("EndSubscription").'</td>';
            print '<td align="right">'.$langs->trans('Action').'</td>';
            print "</tr>\n";

            foreach ($object->linkedmembers as $linkedmember)
            {

            $datefin=$db->jdate($linkedmember->datefin);

		        $adh=new AdherentPlus($db);
		        $adh->lastname=$linkedmember->lastname;
		        $adh->firstname=$linkedmember->firstname;

		        // Lastname
		        print '<tr class="oddeven">';
		        if ($linkedmember->societe != '')
		        {
		            print '<td><a href="card.php?rowid='.$linkedmember->rowid.'">'.img_object($langs->trans("ShowMember"),"user").' '.$adh->getFullName($langs,0,-1,20).' / '.dol_trunc($linkedmember->societe,12).'</a></td>'."\n";
		        }
		        else
		        {
		            print '<td><a href="card.php?rowid='.$linkedmember->rowid.'">'.img_object($langs->trans("ShowMember"),"user").' '.$adh->getFullName($langs,0,-1,32).'</a></td>'."\n";
		        }
                print '<td align="left">'.$linkedmember->login.'</td>';    
                print '<td align="left">'.$adh->getmorphylib($linkedmember->morphy).'</td>';        
                print '<td align="left">'.dol_print_email($linkedmember->email,0,0,1).'</td>';
                print '<td align="left">'.$adh->LibStatut($linkedmember->statut,$linkedmember->subscription,$datefin,2).'</td>';
		        // Date end subscription
		        if ($datefin)
		        {
			        print '<td align="center" class="nowrap">';
		            if ($datefin < dol_now() && $linkedmember->statut > 0)
		            {
		                print dol_print_date($datefin,'day')." ".img_warning($langs->trans("SubscriptionLate"));
		            }
		            else
		            {
		                print dol_print_date($datefin,'day');
		            }
		            print '</td>';
		        }
		        else
		        {
			        print '<td align="left" class="nowrap">';
			        if ($linkedmember->subscription == 'yes')
			        {
		                print $langs->trans("SubscriptionNotReceived");
		                if ($linkedmember->statut > 0) print " ".img_warning();
			        }
			        else
			        {
			            print $langs->trans("SubscriptionNotReceived");
			        }
		            print '</td>';
		        }                
                print '<td align="right"><a href="'. $_SERVER['PHP_SELF'] .'?action=deleteparent&rowid=' . $object->id . '&link=' . $linkedmember->rowid . '" class="deletefilelink">' . img_delete() . '</a></td>';
                print "</tr>";

            }
            print "</table>";

llxFooter();
$db->close();
