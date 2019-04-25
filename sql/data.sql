//ALTER TABLE llx_subscription ADD COLUMN fk_type int(11) DEFAULT NULL;

//ALTER TABLE llx_adherent_type ADD COLUMN morphy varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN welcome double(24,8) DEFAULT 0.00000000;

ALTER TABLE llx_adherent_type ADD COLUMN price double(24,8) DEFAULT 0.00000000;

ALTER TABLE llx_adherent_type ADD COLUMN price_level int(11) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN vote varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN automatic varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN automatic_renew varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN family int(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type ADD COLUMN use_default int(11) DEFAULT NULL;

ALTER TABLE llx_adherent ADD COLUMN fk_parent int(11) DEFAULT NULL;

ALTER TABLE llx_adherent ADD COLUMN datecommitment DATE NOT NULL ;

ALTER TABLE llx_adherent ADD COLUMN ref varchar(30);
