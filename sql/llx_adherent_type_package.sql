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


CREATE TABLE llx_adherent_type_package (
  rowid integer AUTO_INCREMENT NOT NULL PRIMARY KEY,
  entity integer NOT NULL DEFAULT 1,
  date_start timestamp NOT NULL DEFAULT current_timestamp(),
  date_end datetime DEFAULT NULL,
  fk_type integer DEFAULT NULL,
  fk_product integer DEFAULT NULL,
  qty double NOT NULL DEFAULT 0,
  tms timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
)ENGINE=InnoDB;