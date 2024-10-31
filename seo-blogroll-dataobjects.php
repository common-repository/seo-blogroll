<?php

class SEO_LinkBoxes {
	private $blogRolls;

	public function getBlogrolls() {
		return $this->blogRolls;
	}

	public function addBlogroll(SEO_Blogroll $blogroll) {
		if ($this->blogRolls == null) {
			$this->blogRolls = array();
		}
		array_push($this->blogRolls, $blogroll);
	}

	// set blogrolls to null and returns old values
	public function resetBlogrolls() {
		if ($this->blogRolls == null) {
			return null;
		}

		$oldBlogrolls = array();
		
		foreach($this->getBlogrolls() as $blogroll) {
			array_push($oldBlogrolls, clone $blogroll);
		}
		$this->blogRolls = null;
		return $oldBlogrolls;
	}

	public function howManyBoxes() {
		if ($this->blogRolls != null) {
			return count($this->blogRolls);
		} else {
			return 0;
		}
	}

	public function addEmptyLinks($num = 2) {
		$oldBlogRolls = $this->resetBlogrolls();
		foreach($oldBlogRolls as $seo_blogroll) {
			$seo_blogroll->addEmptyLinks($num);
			$this->addBlogroll($seo_blogroll);
		}
	}

	public function removeBlogroll($order) {
		$oldBlogRolls = $this->resetBlogrolls();
		foreach($oldBlogRolls as $seo_blogroll) {
			if ($seo_blogroll->getOrder() != $order) {
				$this->addBlogroll($seo_blogroll);
			}
		}
	}

	/**
	 * wonder if it works
	 */
	public function sortBoxes() {
		$tmpArr = array();

		foreach ($this->blogRolls as $blogroll) {
			$tmpArr[$blogroll->getOrder()] = $blogroll;
		}
		ksort($tmpArr);
		$this->blogRolls = null;

		foreach($tmpArr as $key => $value) {
			$this->addBlogroll($value);
		}
	}

}
class SEO_Blogroll {
	private $title;
	private $links;
	private $order;

	public function getTitle() {
		return wp_specialchars($this->title);
	}

	public function setTitle($title) {
		$this->title = strip_tags(stripslashes($title));
	}

	public function getLinks() {
		return $this->links;
	}

	public function addLink(SEO_BR_Link $link) {
		if ($this->links == null) {
			$this->links = array();
		}
		if ($link->getId() == null) {
			$link->setId(count($this->links) + 1);
		}
		// if the link text is empty, save it as link url value
		if ($link->getText() == null || trim($link->getText()) == "") {
			$link->setText($link->getUrl());
		}
		array_push($this->links, $link);
	}

	public function getOrder() {
		if ($this->order == null) {
			return 0;
		}
		return wp_specialchars($this->order);
	}

	public function setOrder($order) {
		if (is_numeric($order))
		{
			$this->order = strip_tags(stripslashes($order));
		}
	}

	public function addEmptyLinks($num = 2) {
		for ($i = 0; $i < $num; $i++) {
			$tmpLink = new SEO_BR_Link();
			$this->addLink($tmpLink);
		}
	}

	// please call me before storing data
	public function cleanEmptyLinks() {
		if ($this->links != null)
		{
			$oldList = $this->links;
			$this->links = null;
			foreach($oldList as $link) {
				if ($link->getUrl() != null && trim($link->getUrl()) != "") {
					$this->addLink($link);
				}
			}
		}
	}

	// todo
	public function sortLinks() {

	}

	// set links to null and returns old values
	public function resetLinks() {
		if ($this->links == null) {
			return null;
		}
		$oldLinks = array();
		
		foreach($this->getLinks() as $link) {
			array_push($oldLinks, clone $link);
		}
		$this->links = null;
		return $oldLinks;
	}

	public function setDefaults() {
		$this->setTitle("Blogroll");
		$this->links = null;
		$defLink = new SEO_BR_Link("http://www.francesco-castaldo.com/", "Francesco Castaldo SEO Tips 'n' Tricks", false);
		$this->addLink($defLink);
	}
}

class SEO_BR_Link {
	private $id;
	private $url;
	private $text;
	private $target;
	private $nofollow;

	public function __construct($url = "", $text = "", $nofollow = true, $target = "_blank") {
		$this->setUrl($url);
		$this->setText($text);
		$this->setNofollow($nofollow);
		$this->setTarget($target);
	}

	public function getUrl() {
		return wp_specialchars($this->url);
	}

	public function setUrl($url) {
		$this->url = strip_tags(stripslashes($url));
	}

	public function getId() {
		return wp_specialchars($this->id);
	}

	public function setId($id) {
		$this->id = strip_tags(stripslashes($id));
	}

	public function getText() {
		return wp_specialchars($this->text);
	}

	public function setText($text) {
		$this->text = strip_tags(stripslashes($text));
	}

	public function getTarget() {
		return wp_specialchars($this->target);
	}

	public function setTarget($target) {
		$this->target = strip_tags(stripslashes($target));
	}

	public function isNoFollow() {
		return $this->nofollow;
	}

	public function setNoFollow($nofollow) {
		if ($nofollow == true || $nofollow == "true") {
			$this->nofollow = true;
		} else {
			$this->nofollow = false;
		}
	}

}

?>