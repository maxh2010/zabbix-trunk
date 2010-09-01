ALTER TABLE sysmaps_elements MODIFY selementid DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY sysmapid DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_off DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_off NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_on DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_on NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_unknown DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_unknown NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_disabled DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_disabled NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_maintenance DEFAULT NULL;
ALTER TABLE sysmaps_elements MODIFY iconid_maintenance NULL;
DELETE FROM sysmaps_elements WHERE sysmapid NOT IN (SELECT sysmapid FROM sysmaps);
UPDATE sysmaps_elements SET iconid_off=NULL WHERE iconid_off=0;
UPDATE sysmaps_elements SET iconid_on=NULL WHERE iconid_on=0;
UPDATE sysmaps_elements SET iconid_unknown=NULL WHERE iconid_unknown=0;
UPDATE sysmaps_elements SET iconid_disabled=NULL WHERE iconid_disabled=0;
UPDATE sysmaps_elements SET iconid_maintenance=NULL WHERE iconid_maintenance=0;
UPDATE sysmaps_elements SET iconid_off=NULL WHERE NOT iconid_off IS NULL AND NOT iconid_off IN (SELECT imageid FROM images WHERE imagetype=1);
UPDATE sysmaps_elements SET iconid_on=NULL WHERE NOT iconid_on IS NULL AND NOT iconid_on IN (SELECT imageid FROM images WHERE imagetype=1);
UPDATE sysmaps_elements SET iconid_unknown=NULL WHERE NOT iconid_unknown IS NULL AND NOT iconid_unknown IN (SELECT imageid FROM images WHERE imagetype=1);
UPDATE sysmaps_elements SET iconid_disabled=NULL WHERE NOT iconid_disabled IS NULL AND NOT iconid_disabled IN (SELECT imageid FROM images WHERE imagetype=1);
UPDATE sysmaps_elements SET iconid_maintenance=NULL WHERE NOT iconid_maintenance IS NULL AND NOT iconid_maintenance IN (SELECT imageid FROM images WHERE imagetype=1);
ALTER TABLE sysmaps_elements ADD CONSTRAINT c_sysmaps_elements_1 FOREIGN KEY (sysmapid) REFERENCES sysmaps (sysmapid) ON DELETE CASCADE;
ALTER TABLE sysmaps_elements ADD CONSTRAINT c_sysmaps_elements_2 FOREIGN KEY (iconid_off) REFERENCES images (imageid);
ALTER TABLE sysmaps_elements ADD CONSTRAINT c_sysmaps_elements_3 FOREIGN KEY (iconid_on) REFERENCES images (imageid);
ALTER TABLE sysmaps_elements ADD CONSTRAINT c_sysmaps_elements_4 FOREIGN KEY (iconid_unknown) REFERENCES images (imageid);
ALTER TABLE sysmaps_elements ADD CONSTRAINT c_sysmaps_elements_5 FOREIGN KEY (iconid_disabled) REFERENCES images (imageid);
ALTER TABLE sysmaps_elements ADD CONSTRAINT c_sysmaps_elements_6 FOREIGN KEY (iconid_maintenance) REFERENCES images (imageid);
