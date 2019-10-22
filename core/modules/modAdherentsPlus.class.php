<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014-2015 Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *      \defgroup   member     Module foundation
 *      \brief      Module to manage members of a foundation
 *		\file       htdocs/core/modules/modAdherentEx.class.php
 *      \ingroup    member
 *      \brief      File descriptor or module Member
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Class to describe and enable module Adherent
 */
class modAdherentsPlus extends DolibarrModules
{

    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
    	global $conf;

        $this->db = $db;
        $this->numero = 431499; // 310;

        $this->family = "hr";
        $this->module_position = 20;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
    $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Management Extended of members of a foundation or association Extended";
        $this->version = '10.0.3';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
    $this->editor_name = 'ptibogxiv.net';
    $this->editor_url = 'https://www.ptibogxiv.net';
        $this->special = 0;
        $this->picto='user';

        // Data directories to create when module is enabled
        $this->dirs = array("/adherent/temp");

       // Config pages. Put here list of php page, stored into oblyon/admin directory, to use to setup module.
    $this->config_page_url = array("adherent.php@adherentsplus");


    		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
    'models' => 1,
    'triggers' => 1, 
    'hooks' => array('thirdpartycard', 'takeposfrontend'),
		);
        // Dependencies
        //------------
        $this->depends = array();
        $this->requiredby = array('modMailmanSpip');
        $this->langfiles = array("members","companies");
        $this->need_dolibarr_version = array(6,0);
        // Constants
        //-----------
        $this->const = array();
        $r=0;

        $this->const[$r][0] = "ADHERENT_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "standard";
        $this->const[$r][3] = 'Name of PDF model of member';
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_RESIL";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "Votre adhésion vient d'être résiliée.\r\nNous espérons vous revoir très bientôt";
        $this->const[$r][3] = "Mail de résiliation";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_VALID";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "Votre adhésion vient d'être validée. \r\nVoici le rappel de vos coordonnées (toute information erronée entrainera la non validation de votre inscription) :\r\n\r\n%INFOS%\r\n\r\n";
        $this->const[$r][3] = "Mail de validation";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_VALID_SUBJECT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "Votre adhésion a été validée";
        $this->const[$r][3] = "Sujet du mail de validation";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_RESIL_SUBJECT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "Résiliation de votre adhésion";
        $this->const[$r][3] = "Sujet du mail de résiliation";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_FROM";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "From des mails";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_COTIS";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "Bonjour %FIRSTNAME%,\r\nCet email confirme que votre cotisation a été reçue\r\net enregistrée";
        $this->const[$r][3] = "Mail de validation de cotisation";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAIL_COTIS_SUBJECT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "Reçu de votre cotisation";
        $this->const[$r][3] = "Sujet du mail de validation de cotisation";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_CARD_HEADER_TEXT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "%YEAR%";
        $this->const[$r][3] = "Texte imprimé sur le haut de la carte adhérent";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_CARD_FOOTER_TEXT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "%COMPANY%";
        $this->const[$r][3] = "Texte imprimé sur le bas de la carte adhérent";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_CARD_TEXT";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "%FULLNAME%\r\nID: %ID%\r\n%EMAIL%\r\n%ADDRESS%\r\n%ZIP% %TOWN%\r\n%COUNTRY%";
        $this->const[$r][3] = "Text to print on member cards";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_MAILMAN_ADMINPW";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "Mot de passe Admin des liste mailman";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_BANK_ACCOUNT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "ID du Compte banquaire utilise";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_BANK_CATEGORIE";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "ID de la catégorie bancaire des cotisations";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_ETIQUETTE_TYPE";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "L7163";
        $this->const[$r][3] = "Type of address sheets";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "ADHERENT_ETIQUETTE_TEXT";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "%FULLNAME%\n%ADDRESS%\n%ZIP% %TOWN%\n%COUNTRY%";
        $this->const[$r][3] = "Text to print on member address sheets";
        $this->const[$r][4] = 0;
        $r++;

        // Boxes
        //-------

        $this->boxes[0]['file']='box_adherent_birthdays.php@adherentsplus';
        $this->boxes[0]['note']='';

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'adherent';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $r++;
        $this->rights[$r][0] = 71;
        $this->rights[$r][1] = 'Read members\' card';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 72;
        $this->rights[$r][1] = 'Create/modify members (need also user module permissions if member linked to a user)';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 74;
        $this->rights[$r][1] = 'Remove members';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';

        $r++;
        $this->rights[$r][0] = 76;
        $this->rights[$r][1] = 'Export members';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';

        $r++;
        $this->rights[$r][0] = 75;
        $this->rights[$r][1] = 'Setup types of membership';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'configurer';

        $r++;
        $this->rights[$r][0] = 78;
        $this->rights[$r][1] = 'Read subscriptions';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cotisation';
        $this->rights[$r][5] = 'lire';

        $r++;
        $this->rights[$r][0] = 79;
        $this->rights[$r][1] = 'Create/modify/remove subscriptions';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'cotisation';
        $this->rights[$r][5] = 'creer';

         // New pages on tabs
        // -----------------
		$this->tabs = array(
        'member:-subscription',    
        'member:+subscription:Subscriptions:adherentsplus@adherentsplus:1:/adherentsplus/subscription.php?rowid=__ID__',
    		'member:+linkedmember:LinkedMembers:adherentsplus@adherentsplus:$conf->global->ADHERENT_LINKEDMEMBER:/adherentsplus/linkedmember.php?rowid=__ID__',
				'member:+consumption:Consumptions:adherentsplus@adherentsplus:$conf->global->ADHERENT_CONSUMPTION:/adherentsplus/consumption.php?rowid=__ID__',
        'membertype:+translation:Translation:adherentsplus@adherentsplus:$conf->global->MAIN_MULTILANGS:/adherentsplus/type_translation.php?id=__ID__',
        'membertype:+options:Options:adherentsplus@adherentsplus:1:/adherentsplus/type_options.php?rowid=__ID__',
        'membertype:+package:Package:adherentsplus@adherentsplus:$conf->global->ADHERENT_CONSUMPTION:/adherentsplus/type_package.php?rowid=__ID__'
		);

// Main menu entries
$this->menu = array();			// List of menus to add
$r=0;

    }


    /**
     *		Function called when module is enabled.
     *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    function init($options='')
    {
        global $conf,$langs;

        // Permissions
        $this->remove($options);

        //ODT template
        /*
        $src=DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt';
        $dirodt=DOL_DATA_ROOT.'/doctemplates/orders';
        $dest=$dirodt.'/template_order.odt';

        if (file_exists($src) && ! file_exists($dest))
        {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            dol_mkdir($dirodt);
            $result=dol_copy($src,$dest,0,0);
            if ($result < 0)
            {
                $langs->load("errors");
                $this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
                return 0;
            }
        }*/
        
        

        $sql = array(
            "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type='member' AND entity = ".$conf->entity,
            "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','member',".$conf->entity.")"
        );

        return $this->_init($sql,$options);
    }
    
    private function loadTables()
	{
		return $this->_load_tables('/adherentsplus/sql/');
}
}
