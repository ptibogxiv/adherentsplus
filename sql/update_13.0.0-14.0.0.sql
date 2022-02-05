/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  quentin
 * Created: 2 oct. 2018
 */


UPDATE llx_adherent_type as A SET amount=price WHERE price > 0;
ALTER TABLE llx_adherent_type DROP COLUMN price;
