# adherentsplus
New adherent module for Dolibarr
Integration dans Dolibarr V8 soumise

Ce que fait déjà ce module:
- calcul du coût selon date, droit d'entrée de la cotisation selon mode flux, ou prorata annuel, trimestriel, annuel
- traçabilité du type d'adhésion ds les cotisations liées à l'adhérent
- amélioration des API REST

En cours de réalisation (fonctionement partiel)
- adhésion de groupe/famille (adhérent principal qui sera facturé avec adhérents secondaires liés), 

Les modifications de la base de données seront prochainement incluses dans le plugin. En attendant...

For install / Pour installation
download then unzip then copy in htdocs/custom folder

Add the following fields in dolibarr database
Il vous faut ajouter les champs suivants dans la base de données:

Pour les versions avant la V11 / develop:

 ALTER TABLE `llx_adherent_type` ADD COLUMN `duration` varchar(6) DEFAULT NULL;

Pour les versions avant la V10:

 ALTER TABLE `llx_subscription` ADD COLUMN `fk_type` int(11) DEFAULT NULL;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `morphy` varchar(3) DEFAULT NULL;

pour toutes versions de Dolibarr:

 ALTER TABLE `llx_adherent_type` ADD COLUMN `welcome` double(24,8) DEFAULT 0.00000000;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `price` double(24,8) DEFAULT 0.00000000;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `federal` double(24,8) DEFAULT 0.00000000;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `price_level` int(11) DEFAULT NULL;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `automatic` varchar(3) DEFAULT NULL;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `automatic_renew` varchar(3)   DEFAULT NULL;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `family` int(3)   DEFAULT NULL;
 
 ALTER TABLE `llx_adherent_type` ADD COLUMN `use_default` int(11) DEFAULT NULL;
 
 ALTER TABLE `llx_adherent` ADD COLUMN `fk_parent` int(11) DEFAULT NULL;

 ALTER TABLE `llx_adherent` ADD COLUMN `datecommitment` DATE NOT NULL ;

 ALTER TABLE `llx_adherent` ADD COLUMN `ref` varchar(30);
