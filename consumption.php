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
 *      \file       htdocs/adherents/note.php
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
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->loadLangs(array('products', 'companies', 'members', 'bills', 'other'));

$action=GETPOST('action','alpha');
$id=GETPOST('rowid','int');

// Security check
$result=restrictedArea($user,'adherent',$id);

$object = new Adherentplus($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new AdherentTypePlus($db);
    $result=$adht->fetch($object->typeid);
}

$permissionnote=$user->rights->adherent->creer;  // Used by the include of actions_setnotes.inc.php

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once



/*
 * View
 */
$title=$langs->trans("Member") . " - " . $langs->trans("Note");
$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
llxHeader("",$title,$helpurl);

$form = new Form($db);

if ($id)
{
	$head = member_prepare_head($object);

	dol_fiche_head($head, 'consumption', $langs->trans("Member"), -1, 'user');

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
            print '<td>'.$langs->trans("Ref").'</td>';
            print '<td align="center">'.$langs->trans("Date").'</td>';
            print '<td align="center">'.$langs->trans("Product/Service").'</td>';
            print '<td align="center">'.$langs->trans("Quantity").'</td>';
            print '<td align="right">'.$langs->trans("Price").'</td>';
            print '<td align="right">'.$langs->trans('DateInvoice').'</td>';
            print "</tr>\n";

            foreach ($object->consumptions as $consumption)
            {

                print "<tr ".$bc[$var].">";

                print '<td>'.$consumption->id.'</td>';
                print '<td align="center">'.dol_print_date($consumption->date_creation,'dayhour')."</td>\n";
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
        
                print '<td align="right">'.$consumption->price.'</td>';
                print '<td align="right">'.dol_print_date($consumption->date_validation,'day').'</td>';
                print "</tr>";

            }
            print "</table>";

llxFooter();
$db->close();
