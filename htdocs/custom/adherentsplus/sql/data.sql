-- <Update sql table for adherentsplus.>
-- Copyright (C) 2017      Ari Elbaz (elarifr)	<github@accedinfo.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.



ALTER TABLE llx_adherent_type ADD welcome double(24,8) DEFAULT '0.00000000';
ALTER TABLE llx_adherent_type ADD price double(24,8) DEFAULT '0.00000000';
ALTER TABLE llx_adherent_type ADD price_level int(11) DEFAULT NULL;
ALTER TABLE llx_adherent_type ADD vote varchar(3) DEFAULT NULL;
ALTER TABLE llx_adherent_type ADD automatic varchar(3) DEFAULT NULL;

ALTER TABLE llx_subscription ADD fk_type int(11) DEFAULT NULL;