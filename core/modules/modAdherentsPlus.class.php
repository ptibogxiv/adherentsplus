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
        $this->numero = 431499;

        $this->family = "hr";
        $this->module_position = 20;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Management Extended of members";
        $this->version = '18.0.1';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
        $this->editor_name = 'ptibogxiv.eu';
        $this->editor_url = 'https://www.ptibogxiv.eu';
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
        $this->depends = array('modAdherent');
        $this->requiredby = array();
        $this->langfiles = array("adherentsplus@adherentsplus");
        $this->need_dolibarr_version = array(18,0);
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

         // New pages on tabs
        // -----------------
		$this->tabs = array(
        'member:-subscription',    
        'member:+subscription:Subscriptions:adherentsplus@adherentsplus:1:/adherentsplus/subscription.php?rowid=__ID__',
    	'member:+options:Options:adherentsplus@adherentsplus:$conf->global->ADHERENT_CONSUMPTION:/adherentsplus/member_options.php?rowid=__ID__',
    	'member:+consumption:Consumptions:adherentsplus@adherentsplus:$conf->global->ADHERENT_CONSUMPTION:/adherentsplus/consumption.php?rowid=__ID__',
        'member:+linkedmember:LinkedMembers:adherentsplus@adherentsplus:$conf->global->ADHERENT_LINKEDMEMBER:/adherentsplus/linkedmember.php?rowid=__ID__',
        'membertype:+settings:TypeSettings:adherentsplus@adherentsplus:1:/adherentsplus/type_settings.php?rowid=__ID__',
        'membertype:+package:Package:adherentsplus@adherentsplus:$conf->global->ADHERENT_CONSUMPTION:/adherentsplus/type_package.php?rowid=__ID__'
		);

// Main menu entries
$this->menu = array();			// List of menus to add
$r=0;

    // Cronjobs
    $arraydate=dol_getdate(dol_now());
    $datestart=dol_mktime(06, 0, 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);
    $this->cronjobs = array(
        0=>array(
            'label'=>'AutoSubscriptionMember',
            'jobtype'=>'method', 'class'=>'/adherentsplus/class/subscription.class.php',
            'objectname'=>'SubscriptionPlus',
            'method'=>'AutoSubscriptionMember',
            'parameters'=>'3600',
            'comment'=>'AutoSubscriptionMember',
            'frequency'=>6,
            'unitfrequency'=> 3600,
            'priority'=>50,
            'status'=>1,
            'test'=>'isModEnabled("societe")',
            'datestart'=>$datestart
        ),
    );
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
    function init($options='')
    {
        global $db,$conf;
        	
        $sql = array();

// Création extrafield pour choix si frais de port doit apparaitre sur doc.
dol_include_once('/core/class/extrafields.class.php');
$ext = new ExtraFields($db);
$res = $ext->addExtraField("member_beneficiary", 'MemberBeneficiary', 'link', 10, null, 'propaldet', 0, 0, '0', 'a:1:{s:7:"options";a:1:{s:43:"Adherent:adherents/class/adherent.class.php";N;}}', 1, '', '3', '', '', '0', 'adherentsplus@adherentsplus', '1', 0, 4);
$res = $ext->addExtraField("member_beneficiary", 'MemberBeneficiary', 'link', 10, null, 'commandedet', 0, 0, '0', 'a:1:{s:7:"options";a:1:{s:43:"Adherent:adherents/class/adherent.class.php";N;}}', 1, '', '3', '', '', '0', 'adherentsplus@adherentsplus', '1', 0, 4);
$res = $ext->addExtraField("member_beneficiary", 'MemberBeneficiary', 'link', 10, null, 'facturedet', 0, 0, '0', 'a:1:{s:7:"options";a:1:{s:43:"Adherent:adherents/class/adherent.class.php";N;}}', 1, '', '3', '', '', '0', 'adherentsplus@adherentsplus', '1', 0, 4);

        // Permissions
        $this->remove($options);

        $result=$this->load_tables();
        if ($result != 1)
            var_dump($this);

            return $this->_init($sql, $options);
    }

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

    /**
     *		Create tables, keys and data required by module
     * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * 		and create data commands must be stored in directory /mymodule/sql/
     *		This function is called by this->init.
     *
     * 		@return		int		<=0 if KO, >0 if OK
     */
    public function load_tables()
    {
        return $this->_load_tables('/adherentsplus/sql/');
    }
}
