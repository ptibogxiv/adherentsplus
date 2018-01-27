# adherentsplus
New adherent module for Dolibarr

Ce que fait déjà ce module:
- calcul du coût selon date, droit d'entrée de la cotisation selon mode flux, ou prorata annuel, trimestriel, annuel
- traçabilité du type d'adhésion ds les cotisations liées à l'adhérent
- amélioration des API REST

En cours de réalisation
- adhésion de groupe/famille (adhérent principal qui sera facturé avec adhérents secondaires liés)


Les modifications de la mase de données seront prochainement incluses dans le plugin. En attendant...

For install / Pour installation

Add the following fields in dolibarr database
Il vous faut ajouter les champs suivants dans la base de données:

llx_adherent_type
  `welcome` double(24,8) DEFAULT '0.00000000',
  `price` double(24,8) DEFAULT '0.00000000',
  `price_level` int(11) DEFAULT NULL,
  `vote` varchar(3) DEFAULT NULL,
  `automatic` varchar(3) DEFAULT NULL,
  `family` int(3) DEFAULT NULL,
  
llx_adherent
  `fk_parent` int(11) DEFAULT NULL,

llx_subscription
  `fk_type` int(11) DEFAULT NULL,
