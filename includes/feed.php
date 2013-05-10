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

require_once (dirname(__FILE__).'/../../client/facebook.php');
require_once (dirname(__FILE__).'/../settings.php');
require_once (dirname(__FILE__).'/constants.php');
require_once (dirname(__FILE__).'/cachemanager.php');
require_once (dirname(__FILE__).'/amazon.php');
require_once (dirname(__FILE__).'/logmanager.php');

class Feed {
	private $userid;
	private $added_amazon_items = Array();
	private $added_freeform_items = Array();
	
	public static function getFeed($userid) {
		LogManager::trace(__CLASS__, "retrieving feed for userid=".$userid);

		// First try to retrieve the feed from the cache
		$feed = CacheManager::get("Feed-".$userid);
		
		if (!$feed) { // If that fails, create the feed
			LogManager::trace(__CLASS__, "Can't find feed in the cache, creating it for userid=".$userid);
			$feed = new Feed();
			$feed->setUserId($userid);
			CacheManager::set("Feed-".$userid, $feed);
		}
		
		return $feed;
	}
	
	public function setUserId($newid) {
		$this->userid = $newid;
	}
	
	private function saveCache() {
		LogManager::trace(__CLASS__, "updating cache entry of feed with userid=".$this->userid);
		CacheManager::replace("Feed-".$this->userid, $this);
	}
	
	public function addAmazonItem($item) {
		LogManager::trace(__CLASS__, "Adding an amazon item to feed with userid=".$this->userid);
		
		$this->added_amazon_items []= $item;
		$this->saveCache();
	}
	
	public function addFreeformItem($item) {
		LogManager::trace(__CLASS__, "Adding a freeform item to feed with userid=".$this->userid);
		
		$this->added_freeform_items []= $item;
		$this->saveCache();
	}
	
	private function getAddedAmazonByType($item_type) {
		$results = Array();
		
		foreach ($this->added_amazon_items as $item) {
			if ($item["ITEM_TYPE"] == $item_type)
			$results []= $item; 
		}
		
		return $results;
	}
	
	private function getAddedFreeformByType($item_type) {
		$results = Array();
		
		foreach ($this->added_freeform_items as $item) {
			if ($item["ITEM_TYPE"] == $item_type)
			$results []= $item; 
		}
		
		return $results;	
	}
	
	private static function getImage($asin, $locale, $search_type) {
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);

		if (isset($Item->SmallImage->URL))
			return $Item->SmallImage->URL;
		else return false;
	}
	
	public function generateFeed($api_client) {
		global $APP_PATH;
		global $ITEM_TYPE_ID;
		global $PAGE;
		
		$images = Array();
		$images_links = Array();
		$subject = "";
		$amazon_added_dvds = $this->getAddedAmazonByType($ITEM_TYPE_ID["DVD"]);
		$freeform_added_dvds = $this->getAddedFreeformByType($ITEM_TYPE_ID["DVD"]);
		$amazon_added_books = $this->getAddedAmazonByType($ITEM_TYPE_ID["BOOK"]);
		$freeform_added_books = $this->getAddedFreeformByType($ITEM_TYPE_ID["BOOK"]);
		$amazon_added_cds = $this->getAddedAmazonByType($ITEM_TYPE_ID["CD"]);
		$freeform_added_cds = $this->getAddedFreeformByType($ITEM_TYPE_ID["CD"]);
		$amazon_added_games = $this->getAddedAmazonByType($ITEM_TYPE_ID["VIDEO_GAME"]);
		$freeform_added_games = $this->getAddedFreeformByType($ITEM_TYPE_ID["VIDEO_GAME"]);
		$freeform_added_others = $this->getAddedFreeformByType($ITEM_TYPE_ID["OTHER"]);
		
		$dvds = count($amazon_added_dvds) + count($freeform_added_dvds);
		$books = count($amazon_added_books) + count($freeform_added_books);
		$cds = count($amazon_added_cds) + count($freeform_added_cds);
		$games = count($amazon_added_games) + count($freeform_added_games);
		$others = count($freeform_added_others);
		$overall = $dvds + $books + $cds + $games + $others;
		
		LogManager::trace(__CLASS__, "Overall items=$overall for feed with userid=".$this->userid);
		
		if (count($this->added_amazon_items) > 0) {
			$subject = "added $overall item".($overall > 1?"s":"")." <a href=\"".$APP_PATH."\">you can borrow</a>.";
			
			// ****** PICK IMAGES
			
			$amountleft = 4;
			
			if (count($amazon_added_dvds) > 0) {
				$amount = 4; //maximum possible
				if (count($amazon_added_books) > 0) $amount--;
				if (count($amazon_added_cds) > 0) $amount--;
				if (count($amazon_added_games) > 0) $amount--;
				if ($amount > count($amazon_added_dvds)) $amount = count($amazon_added_dvds);
				
				for ($i = 0; $i < $amount; $i++) {
					$amazon_item = $amazon_added_dvds[$i];
					$image = Feed::getImage($amazon_item["ITEM_ASIN"], $amazon_item["LOCALE"], $amazon_item["ITEM_TYPE"]);
					if ($image) {
						$images []= $image;
						$images_links [] = $PAGE['BORROW']."?FRIEND_ID=".$this->userid."&LOCALE=".$amazon_item["LOCALE"]."&SEARCH_TYPE=".$amazon_item["ITEM_TYPE"]."&ITEM_ASIN=".$amazon_item["ITEM_ASIN"];
					}
				}
				
				$amountleft -= $amount;
			}
			
			if (count($amazon_added_books) > 0 && $amountleft > 0) {
				$amount = $amountleft; //maximum possible
				if (count($amazon_added_cds) > 0) $amount--;
				if (count($amazon_added_games) > 0) $amount--;
				if ($amount > count($amazon_added_books)) $amount = count($amazon_added_books);
				
				for ($i = 0; $i < $amount; $i++) {
					$amazon_item = $amazon_added_books[$i];
					$image = Feed::getImage($amazon_item["ITEM_ASIN"], $amazon_item["LOCALE"], $amazon_item["ITEM_TYPE"]);
					if ($image) {
						$images []= $image;
						$images_links [] = $PAGE['BORROW']."?FRIEND_ID=".$this->userid."&LOCALE=".$amazon_item["LOCALE"]."&SEARCH_TYPE=".$amazon_item["ITEM_TYPE"]."&ITEM_ASIN=".$amazon_item["ITEM_ASIN"];
					} 
				}
				
				$amountleft -= $amount;
			}
			
			if (count($amazon_added_cds) > 0 && $amountleft > 0) {
				$amount = $amountleft; //maximum possible
				if (count($amazon_added_games) > 0) $amount--;
				if ($amount > count($amazon_added_cds)) $amount = count($amazon_added_cds);
				
				for ($i = 0; $i < $amount; $i++) {
					$amazon_item = $amazon_added_cds[$i];
					$image = Feed::getImage($amazon_item["ITEM_ASIN"], $amazon_item["LOCALE"], $amazon_item["ITEM_TYPE"]);
					if ($image) {
						$images []= $image;
						$images_links [] = $PAGE['BORROW']."?FRIEND_ID=".$this->userid."&LOCALE=".$amazon_item["LOCALE"]."&SEARCH_TYPE=".$amazon_item["ITEM_TYPE"]."&ITEM_ASIN=".$amazon_item["ITEM_ASIN"];
					}
				}
				
				$amountleft -= $amount;
			}
			
			if (count($amazon_added_games) > 0 && $amountleft > 0) {
				$amount = $amountleft; //maximum possible
				if ($amount > count($amazon_added_games)) $amount = count($amazon_added_games);
				
				for ($i = 0; $i < $amount; $i++) {
					$amazon_item = $amazon_added_games[$i];
					$image = Feed::getImage($amazon_item["ITEM_ASIN"], $amazon_item["LOCALE"], $amazon_item["ITEM_TYPE"]);
					if ($image) {
						$images []= $image;
						$images_links [] = $PAGE['BORROW']."?FRIEND_ID=".$this->userid."&LOCALE=".$amazon_item["LOCALE"]."&SEARCH_TYPE=".$amazon_item["ITEM_TYPE"]."&ITEM_ASIN=".$amazon_item["ITEM_ASIN"];
					}
				}
				
				$amountleft -= $amount;
			}
		} elseif (count($this->added_freeform_items) > 0) {
			$subject = "added $overall item".($overall > 1?"s":"")." <a href=\"".$APP_PATH."\">you can borrow</a>.";
		} else return;
		
		$image1 = $image2 = $image3 = $image4 = null;
		$image1_link = $image2_link = $image3_link = $image4_link = null;
		
		if (isset($images[0])) $image1 = $images[0];
		if (isset($images[1])) $image2 = $images[1];
		if (isset($images[2])) $image3 = $images[2];
		if (isset($images[3])) $image4 = $images[3];
		if (isset($images_links[0])) $image1_link = $images_links[0];
		if (isset($images_links[1])) $image2_link = $images_links[1];
		if (isset($images_links[2])) $image3_link = $images_links[2];
		if (isset($images_links[3])) $image4_link = $images_links[3];

		$api_client->feed_publishActionOfUser($subject, "", $image1, $image1_link, $image2, $image2_link, $image3, $image3_link, $image4, $image4_link);
		
		LogManager::trace(__CLASS__, "Published feed item with userid=".$this->userid." subject=".$subject);
		
		$this->added_amazon_items = Array();
		$this->added_freeform_items = Array();
		$this->saveCache();
	}	
}

?>