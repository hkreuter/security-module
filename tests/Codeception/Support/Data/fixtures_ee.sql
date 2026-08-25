SET @@session.sql_mode = '';

REPLACE INTO `oxuser` (`OXID`, `OXACTIVE`, `OXRIGHTS`, `OXSHOPID`, `OXUSERNAME`, `OXPASSWORD`, `OXPASSSALT`, `OXCUSTNR`, `OXUSTID`, `OXCOMPANY`, `OXFNAME`, `OXLNAME`, `OXSTREET`, `OXSTREETNR`, `OXADDINFO`, `OXCITY`, `OXCOUNTRYID`, `OXZIP`, `OXFON`, `OXFAX`, `OXSAL`, `OXBONI`, `OXCREATE`, `OXREGISTER`, `OXPRIVFON`, `OXMOBFON`, `OXBIRTHDATE`)
VALUES ('testuser', 1, 'user', 1, 'some_test_user@oxid-esales.dev', '$2y$10$EzcRW/lp36HO5eYeLKUxv.x3DftKWTJfstwtGowWVr5ePZzAZE9fO', '', 8, '', 'UserCompany šÄßüл', 'UserNamešÄßüл', 'UserSurnamešÄßüл', 'Musterstr.šÄßüл', '1', 'User additional info šÄßüл', 'Musterstadt šÄßüл', 'testcountry_de', '79098', '0800 111111', '0800 111112', 'Mr', 500, '2008-02-05 14:42:42', '2008-02-05 14:42:42', '0800 111113', '0800 111114', '1980-01-01');

REPLACE INTO `oxuser` (`OXID`, `OXACTIVE`, `OXRIGHTS`, `OXSHOPID`, `OXUSERNAME`, `OXPASSWORD`, `OXPASSSALT`, `OXCUSTNR`, `OXFNAME`, `OXLNAME`, `OXCREATE`, `OXREGISTER`)
VALUES ('oxadmintest', 1, 'malladmin', 1, 'admin_test@oxid-esales.dev', '$2y$10$EzcRW/lp36HO5eYeLKUxv.x3DftKWTJfstwtGowWVr5ePZzAZE9fO', '', 9, 'John', 'Doe', '2008-02-05 14:42:42', '2008-02-05 14:42:42');

REPLACE INTO `oxarticles` (`OXID`, `OXMAPID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXARTNUM`, `OXTITLE`, `OXSHORTDESC`, `OXPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXVAT`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXSEARCHKEYS`, `OXISSEARCH`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXSEARCHKEYS_1`, `OXBUNDLEID`, `OXSTOCKTEXT_1`, `OXNOSTOCKTEXT_1`, `OXSORT`, `OXVENDORID`, `OXMANUFACTURERID`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`)
VALUES ('1000', 1, 1, '', 1, '1000', '[DE 4] Test product 0 šÄßüл', 'Test product 0 short desc [DE]', 50, 35, 45, 55, 0, 'kg', 2, NULL, 2, 15, 1, 'In stock [DE]', 'Out of stock [DE]', '0000-00-00', '2008-02-04', '2008-02-04 17:07:48', 1, 2, 2, 'search1000', 1, '', 0, 0, '', 50, 0, '', '', 'Test product 0 [EN] šÄßüл', 'Test product 0 short desc [EN] šÄßüл', 'šÄßüл1000', '', 'In stock [EN] šÄßüл', 'Out of stock [EN] šÄßüл', 0, 'testdistributor', 'testmanufacturer', 1, 1, 'DAY');

UPDATE `oxcountry` SET `OXACTIVE` = 1 , `OXID` = 'testcountry_de' WHERE `OXISOALPHA2` = 'DE';

INSERT INTO `oxarticles2shop` (`OXSHOPID`, `OXMAPOBJECTID`, `OXTIMESTAMP`)
VALUES (1, 1, '2016-07-19 14:38:26');

-- Form Security test data for GetFormSecurityCest

REPLACE INTO `oxarticles` (`OXID`, `OXMAPID`, `OXSHOPID`, `OXPARENTID`, `OXACTIVE`, `OXARTNUM`, `OXTITLE`, `OXSHORTDESC`, `OXPRICE`, `OXPRICEA`, `OXPRICEB`, `OXPRICEC`, `OXTPRICE`, `OXUNITNAME`, `OXUNITQUANTITY`, `OXVAT`, `OXWEIGHT`, `OXSTOCK`, `OXSTOCKFLAG`, `OXSTOCKTEXT`, `OXNOSTOCKTEXT`, `OXDELIVERY`, `OXINSERT`, `OXTIMESTAMP`, `OXLENGTH`, `OXWIDTH`, `OXHEIGHT`, `OXSEARCHKEYS`, `OXISSEARCH`, `OXVARNAME`, `OXVARSTOCK`, `OXVARCOUNT`, `OXVARSELECT`, `OXVARMINPRICE`, `OXVARMAXPRICE`, `OXVARNAME_1`, `OXVARSELECT_1`, `OXTITLE_1`, `OXSHORTDESC_1`, `OXSEARCHKEYS_1`, `OXBUNDLEID`, `OXSTOCKTEXT_1`, `OXNOSTOCKTEXT_1`, `OXSORT`, `OXVENDORID`, `OXMANUFACTURERID`, `OXMINDELTIME`, `OXMAXDELTIME`, `OXDELTIMEUNIT`)
VALUES ('1001', 1, 1, '', 1, '1001', '[DE] Test product 1 šÄßüл', 'Test product 1 short desc [DE]', 100, 70, 90, 110, 0, 'kg', 1, NULL, 1, 10, 1, 'In stock [DE]', 'Out of stock [DE]', '0000-00-00', '2008-02-04', '2008-02-04 17:07:48', 1, 1, 1, 'search1001', 1, '', 0, 0, '', 100, 0, '', '', 'Test product 1 [EN] šÄßüл', 'Test product 1 short desc [EN] šÄßüл', 'šÄßüл1001', '', 'In stock [EN] šÄßüл', 'Out of stock [EN] šÄßüл', 0, 'testdistributor', 'testmanufacturer', 1, 1, 'DAY');

REPLACE INTO `oxcategories` (`OXID`, `OXPARENTID`, `OXLEFT`, `OXRIGHT`, `OXROOTID`, `OXSORT`, `OXACTIVE`, `OXACTIVE_1`, `OXHIDDEN`, `OXSHOPID`, `OXTITLE`, `OXTITLE_1`, `OXDESC`, `OXDESC_1`, `OXLONGDESC`, `OXLONGDESC_1`)
VALUES ('oxrootid', '', 1, 4, 'oxrootid', 0, 1, 1, 0, 1, 'Root', 'Root', '', '', '', ''),
       ('oesm_testcat', 'oxrootid', 2, 3, 'oxrootid', 0, 1, 1, 0, 1, '[DE] Test Kategorie 0 šÄßüл', 'Test category 0 [EN] šÄßüл', '', '', '', '');

REPLACE INTO `oxcategories2shop` (`OXSHOPID`, `OXMAPOBJECTID`)
VALUES (1, 1),
       (1, 2);

REPLACE INTO `oxattribute` (`OXID`, `OXMAPID`, `OXSHOPID`, `OXTITLE`, `OXTITLE_1`, `OXPOS`)
VALUES ('oesm_testattr1', 1, 1, '[DE] Test Attribut 1 šÄßüл', 'Test attribute 1 [EN] šÄßüл', 1);

REPLACE INTO `oxattribute2shop` (`OXSHOPID`, `OXMAPOBJECTID`)
VALUES (1, 1);

REPLACE INTO `oxcategory2attribute` (`OXID`, `OXOBJECTID`, `OXATTRID`, `OXSORT`)
VALUES ('oesm_cat2attr1', 'oesm_testcat', 'oesm_testattr1', 1);

REPLACE INTO `oxobject2category` (`OXID`, `OXOBJECTID`, `OXCATNID`, `OXPOS`, `OXTIME`)
VALUES ('oesm_p1000incat', '1000', 'oesm_testcat', 0, 0),
       ('oesm_p1001incat', '1001', 'oesm_testcat', 0, 0);

REPLACE INTO `oxobject2attribute` (`OXID`, `OXOBJECTID`, `OXATTRID`, `OXVALUE`, `OXVALUE_1`, `OXPOS`)
VALUES ('oesm_1000attr1', '1000', 'oesm_testattr1', '[DE] Attributwert 1 šÄßüл', 'attr value 1 [EN] šÄßüл', 1);
