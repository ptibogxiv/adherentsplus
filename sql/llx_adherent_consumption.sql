-- ============================================================================
-- Copyright (C) 2017	 Open-DSI 	 <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================


CREATE TABLE llx_adherent_consumption (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  entity integer DEFAULT 1 NOT NULL,         -- multi company id
  fk_adherent integer DEFAULT NULL,
  fk_subscription integer DEFAULT NULL,
  fk_facture integer DEFAULT NULL,
  fk_parent_line integer DEFAULT NULL,
  fk_product integer DEFAULT NULL,
  product_type integer DEFAULT 0,
  label varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  description text COLLATE utf8_unicode_ci DEFAULT NULL,
  qty double DEFAULT NULL,
  remise_percent double DEFAULT 0,
  remise double DEFAULT 0,
  info_bits integer DEFAULT 0,
  rang integer DEFAULT 0,
  fk_unit integer DEFAULT NULL,
  date_start datetime NOT NULL,
  date_end datetime NOT NULL,
  fk_product_fournisseur_price integer DEFAULT NULL,
  fk_user_author integer DEFAULT NULL,
  fk_user_modif integer DEFAULT NULL,
  date_creation datetime NOT NULL,
  tms timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
)ENGINE=InnoDB;
