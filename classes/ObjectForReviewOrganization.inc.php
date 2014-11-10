<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewOrganization.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewOrganization
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewOrganizationDAO
 *
 * @brief Object for review organization metadata class.
 */

class ObjectForReviewOrganization extends DataObject {

	/**
	 * Constructor.
	 */
	function ObjectForReviewOrganization() {
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

	/**
	 * Set Name of organization.
	 * @param $name string
	 */
	function setName($name) {
		return $this->setData('publisherName', $name);
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
	 * Get ID of object.
	 * @return int
	 */
	function getObjectId() {
		return $this->getData('objectId');
	}

	/**
	 * Set ID of object.
	 * @param $objectId int
	 */
	function setObjectId($objectId) {
		return $this->setData('objectId', $objectId);
	}

	/**
	 * Get ID of the journal for this organization.
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * Set ID of journal.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
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
