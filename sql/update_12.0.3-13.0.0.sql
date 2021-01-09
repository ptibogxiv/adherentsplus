/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  quentin
 * Created: 2 oct. 2018
 */

ALTER TABLE llx_adherent_type MODIFY COLUMN family varchar(3) DEFAULT NULL;

ALTER TABLE llx_adherent_type_package ADD fk_member INT(11) DEFAULT NULL AFTER `fk_type`;

ALTER TABLE llx_adherent_consumption ADD fk_subscription INT(11) DEFAULT NULL AFTER `fk_adherent`;

