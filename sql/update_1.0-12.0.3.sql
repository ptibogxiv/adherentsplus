/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  quentin
 * Created: 2 oct. 2018
 */
 
ALTER TABLE llx_subscription ADD COLUMN fk_type int(11) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN morphy varchar(3) DEFAULT NULL; 
 
ALTER TABLE llx_adherent_type ADD COLUMN duration varchar(6) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN welcome double(24,8) DEFAULT 0.00000000;

ALTER TABLE llx_adherent_type ADD COLUMN price double(24,8) DEFAULT 0.00000000;

ALTER TABLE llx_adherent_type ADD COLUMN federal double(24,8) DEFAULT 0.00000000;

ALTER TABLE llx_adherent_type ADD COLUMN price_level int(11) DEFAULT 1;

ALTER TABLE llx_adherent_type ADD COLUMN automatic varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN automatic_renew varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN family varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN use_default int(11) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN commitment varchar(6) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN prorata varchar(9) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN prorata_date varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent ADD COLUMN fk_parent int(11) DEFAULT NULL;

ALTER TABLE llx_adherent ADD COLUMN datecommitment DATE NULL ;

ALTER TABLE llx_adherent ADD COLUMN ref varchar(30);