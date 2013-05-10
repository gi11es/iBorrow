<?php

/* 
 	Copyright (C) 2007 Gilles Dubuc.
 
 	This file is part of iBorrow.

    iBorrow is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    iBorrow is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with iBorrow.  If not, see <http://www.gnu.org/licenses/>.
*/

$APP_PATH = "http://apps.facebook.com/iborrow/";
$APP_REAL_PATH = "http://iborrow.darumazone.com/";

$APP_ABOUT_PAGE = "http://www.facebook.com/apps/application.php?api_key=3ae82599f92f956d2f9f5f4539086cc5";
$APP_ADD_PAGE = "http://www.facebook.com/add.php?api_key=3ae82599f92f956d2f9f5f4539086cc5";

$TEMPLATES_PATH = "templates/";

$PAGE['INDEX'] = $APP_PATH."index.php";
$PAGE['MY_ITEMS'] = $APP_PATH."my_items.php";
$PAGE['THEIR_ITEMS'] = $APP_PATH."their_items.php";
$PAGE['PREFERENCES'] = $APP_PATH."preferences.php";
$PAGE['DEBUG'] = $APP_PATH."debug.php";
$PAGE['BORROW'] = $APP_PATH."borrow.php";
$PAGE['INVITE'] = $APP_PATH."invite.php";
$PAGE['SEARCH_RESULTS'] = $APP_REAL_PATH."search_results.php";
$PAGE['SHARED_RESULTS'] = $APP_REAL_PATH."shared_results.php";
$PAGE['FRIENDS_SHARED_RESULTS'] = $APP_REAL_PATH."friends_shared_results.php";
$PAGE['REQUESTS'] = $APP_REAL_PATH."requests.php";
$PAGE['BORROWED'] = $APP_REAL_PATH."borrowed.php";
$PAGE['COOL_APPS'] = $APP_PATH."coolapps.php";

$PAGE_CODE['INDEX'] = 0;
$PAGE_CODE['MY_ITEMS'] = 1;
$PAGE_CODE['PREFERENCES'] = 2;
$PAGE_CODE['INVITE'] = 3;
$PAGE_CODE['THEIR_ITEMS'] = 4;
$PAGE_CODE['COOL_APPS'] = 5;

$ITEM_TYPE["DVD"] = "DVD";
$ITEM_TYPE["BOOK"] = "Book";
$ITEM_TYPE["CD"] = "CD";
$ITEM_TYPE["VIDEO_GAME"] = "Video game";
$ITEM_TYPE["OTHER"] = "Other";

$ITEM_TYPE_ID["DVD"] = 0;
$ITEM_TYPE_ID["BOOK"] = 1;
$ITEM_TYPE_ID["CD"] = 2;
$ITEM_TYPE_ID["VIDEO_GAME"] = 3;
$ITEM_TYPE_ID["OTHER"] = 4;

$TEMPLATE["SEARCH_TABLE_TOP"] = $TEMPLATES_PATH."search_table_top.inc.php";
$TEMPLATE["SEARCH_STYLE"] = $TEMPLATES_PATH."search_style.inc.php";

$LOG_LEVEL["TRACE"] = 0;
$LOG_LEVEL["DEBUG"] = 1;
$LOG_LEVEL["INFO"] = 2;
$LOG_LEVEL["ERROR"] = 3;

$TABLE["USER"] = "user";
$TABLE["AMAZON_SHARED_ITEMS"] = "amazon_shared_items";
$TABLE["FREEFORM_SHARED_ITEMS"] = "freeform_shared_items";

$COLUMN["USER_ID"] = "user_id";
$COLUMN_TYPE["USER_ID"] = "INT";
$COLUMN_TYPE_ATTRIBUTES["USER_ID"] = " NOT NULL";

$COLUMN["ITEM_ASIN"] = "item_asin";
$COLUMN_TYPE["ITEM_ASIN"] = "VARCHAR(25)";
$COLUMN_TYPE_ATTRIBUTES["ITEM_ASIN"] = " NOT NULL";

$COLUMN["STATUS"] = "status";
$COLUMN_TYPE["STATUS"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["STATUS"] = " NOT NULL DEFAULT 0";

$COLUMN["LOCALE"] = "locale";
$COLUMN_TYPE["LOCALE"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["LOCALE"] = " NOT NULL DEFAULT 0";

$COLUMN["ITEM_TYPE"] = "item_type";
$COLUMN_TYPE["ITEM_TYPE"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["ITEM_TYPE"] = " NOT NULL DEFAULT 0";

$COLUMN["TIMESTAMP"] = "item_timestamp";
$COLUMN_TYPE["TIMESTAMP"] = "TIMESTAMP";
$COLUMN_TYPE_ATTRIBUTES["TIMESTAMP"] = " NOT NULL DEFAULT CURRENT_TIMESTAMP";

$COLUMN["FREEFORM_ID"] = "freeform_id";
$COLUMN_TYPE["FREEFORM_ID"] = "INT";
$COLUMN_TYPE_ATTRIBUTES["FREEFORM_ID"] = " AUTO_INCREMENT PRIMARY KEY";

$COLUMN["TITLE"] = "title";
$COLUMN_TYPE["TITLE"] = "VARCHAR(255)";
$COLUMN_TYPE_ATTRIBUTES["TITLE"] = " NOT NULL";

$COLUMN["DESCRIPTION"] = "description";
$COLUMN_TYPE["DESCRIPTION"] = "VARCHAR(1500)";
$COLUMN_TYPE_ATTRIBUTES["DESCRIPTION"] = "";

$COLUMN["BORROWER_ID"] = "borrower_id";
$COLUMN_TYPE["BORROWER_ID"] = "INT";
$COLUMN_TYPE_ATTRIBUTES["BORROWER_ID"] = " ";

$COLUMN["BORROWER_MESSAGE"] = "borrower_message";
$COLUMN_TYPE["BORROWER_MESSAGE"] = "VARCHAR(1500)";
$COLUMN_TYPE_ATTRIBUTES["BORROWER_MESSAGE"] = "";

$COLUMN["LENDER_MESSAGE"] = "lender_message";
$COLUMN_TYPE["LENDER_MESSAGE"] = "VARCHAR(1500)";
$COLUMN_TYPE_ATTRIBUTES["LENDER_MESSAGE"] = "";

$COLUMN_TYPE["QUANTITY"] = "TINYINT";

$COLUMN["QUANTITY_DVD"] = "quantity_dvd";
$COLUMN_TYPE["QUANTITY_DVD"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["QUANTITY_DVD"] = " NOT NULL DEFAULT 5";

$COLUMN["QUANTITY_BOOK"] = "quantity_book";
$COLUMN_TYPE["QUANTITY_BOOK"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["QUANTITY_BOOK"] = " NOT NULL DEFAULT 5";

$COLUMN["QUANTITY_CD"] = "quantity_cd";
$COLUMN_TYPE["QUANTITY_CD"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["QUANTITY_CD"] = " NOT NULL DEFAULT 5";

$COLUMN["QUANTITY_GAME"] = "quantity_game";
$COLUMN_TYPE["QUANTITY_GAME"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["QUANTITY_GAME"] = " NOT NULL DEFAULT 5";

$COLUMN["QUANTITY_OTHER"] = "quantity_other";
$COLUMN_TYPE["QUANTITY_OTHER"] = "TINYINT";
$COLUMN_TYPE_ATTRIBUTES["QUANTITY_OTHER"] = " NOT NULL DEFAULT 5";

$COLUMN["SESSION_KEY"] = "session_key";
$COLUMN_TYPE["SESSION_KEY"] = "VARCHAR(80)";
$COLUMN_TYPE_ATTRIBUTES["SESSION_KEY"] = "";

$COLUMN["REQUEST_MESSAGE"] = "request_message";
$COLUMN_TYPE["REQUEST_MESSAGE"] = "VARCHAR(300)";
$COLUMN_TYPE_ATTRIBUTES["REQUEST_MESSAGE"] = "";

$STATUS["DISABLED"] = 0;
$STATUS["SHARED"] = 1;
$STATUS["ACTIVE"] = 1;
$STATUS["REQUESTED"] = 2;
$STATUS["BORROWED"] = 3;

$AMAZON_LOCALE["US"] = 0;
$AMAZON_LOCALE["CA"] = 1;
$AMAZON_LOCALE["UK"] = 2;
$AMAZON_LOCALE["DE"] = 3;
$AMAZON_LOCALE["FR"] = 4;
//$AMAZON_LOCALE["JP"] = 5;

$AMAZON_LOCALE_URL[0] = "amazon.com";
$AMAZON_LOCALE_URL[1] = "amazon.ca";
$AMAZON_LOCALE_URL[2] = "amazon.co.uk";
$AMAZON_LOCALE_URL[3] = "amazon.de";
$AMAZON_LOCALE_URL[4] = "amazon.fr";
//$AMAZON_LOCALE_URL[5] = "amazon.co.jp";

$AMAZON_LOCALE_AWS_URL[0] = "amazonaws.com";
$AMAZON_LOCALE_AWS_URL[1] = "amazonaws.ca";
$AMAZON_LOCALE_AWS_URL[2] = "amazonaws.co.uk";
$AMAZON_LOCALE_AWS_URL[3] = "amazonaws.de";
$AMAZON_LOCALE_AWS_URL[4] = "amazonaws.fr";
//$AMAZON_LOCALE_AWS_URL[5] = "amazonaws.jp";

?>