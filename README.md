# adherentsplus
New adherent module for Dolibarr


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

llx_subscription
  `fk_type` int(11) DEFAULT NULL,
