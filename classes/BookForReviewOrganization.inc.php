<?php

/**
 * @file plugins/generic/booksForReview/classes/BookForReviewOrganization.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewOrganization
 * @ingroup plugins_generic_booksForReview
 * @see BookForReviewOrganizationDAO
 *
 * @brief Book for review organization metadata class.
 */

class BookForReviewOrganization extends DataObject {

	/**
	 * Constructor.
	 */
	function BookForReviewOrganization() {
		parent::DataObject();
		$this->setId(0);
	}

	/**
	 * Get the organization's complete name.
	 * @return string
	 */
	function getName() {
		return $this->getData('publisherName');
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of organization.
	 * @return int
	 */
	function getId() {
		return $this->getData('organizationId');
	}

	/**
	 * Set ID of organization.
	 * @param $organizationId int
	 */
	function setId($organizationId) {
		return $this->setData('organizationId', $organizationId);
	}

	/**
	 * Get ID of book.
	 * @return int
	 */
	function getBookID() {
		return $this->getData('bookId');
	}

	/**
	 * Set ID of book.
	 * @param $bookId int
	 */
	function setBookId($bookId) {
		return $this->setData('bookId', $bookId);
	}

	/**
	 * Get street address.
	 * @return string
	 */
	function getStreetAddress() {
		return $this->getData('streetAddress');
	}

	/**
	 * Set street address.
	 * @param $streetAddress string
	 */
	function setStreetAddress($streetAddress)
	{
		return $this->setData('streetAddress', $streetAddress);
	}

	/**
	 * Get city.
	 * @return string
	 */
	function getCity() {
		return $this->getData('city');
	}

	/**
	 * Set city.
	 * @param $city string
	 */
	function setCity($city) {
		return $this->setData('city', $city);
	}

	/**
	 * Get state.
	 * @return string
	 */
	function getState() {
		return $this->getData('state');
	}

	/**
	 * Set state.
	 * @param $state string
	 */
	function setState($state) {
		return $this->setData('state', $state);
	}

	/**
	 * Get country.
	 * @return string
	 */
	function getCountry() {
		return $this->getData('country');
	}

	/**
	 * Set country.
	 * @param $country string
	 */
	function setCountry($country) {
		return $this->setData('country', $country);
	}

	/**
	 * Get phone.
	 * @return string
	 */
	function getPhone() {
		return $this->getData('phone');
	}

	/**
	 * Set phone.
	 * @param $phone string
	 */
	function setPhone($phone) {
		return $this->setData('phone', $phone);
	}

	/**
	 * Get fax.
	 * @return string
	 */
	function getFax() {
		return $this->getData('fax');
	}

	/**
	 * Set fax.
	 * @param $fax string
	 */
	function setFax($fax) {
		return $this->setData('fax', $fax);
	}

	/**
	 * Get url.
	 * @return string
	 */
	function getUrl() {
		return $this->getData('url');
	}

	/**
	 * Set url.
	 * @param $url string
	 */
	function setUrl($url) {
		return $this->setData('url', $url);
	}

	/**
	 * Get sequence of organization in organization list.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of organization in organization list.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

}

?>
