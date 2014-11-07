<?php

/**
 * @file plugins/generic/booksForReview/BookForReviewOrganizationForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewOrganizationForm
 * @ingroup plugins_generic_booksForReview
 *
 * @brief Form for journal managers to modify Book for Review Organization entries
 */


import('lib.pkp.classes.form.Form');

class BookForReviewOrganizationForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** @var $organization object */
	var $organization;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 * @param $organizationId int
	 */
	function BookForReviewOrganizationForm(&$plugin, $journalId, $organizationId = 0) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . '/editor/form/organizationForm.tpl');

		if ($organizationId) {
			$bfrOrgId = DAORegistry::getDAO('BookForReviewOrganizationDAO');
			$this->organization = $bfrOrgId->getOrganization($organizationId);
		}
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		if ($this->organization) {
			$this->_data = array(
				'organization' => $this->organization,
				'name' => $this->organization->getName(),
				'streetAddress' => $this->organization->getStreetAddress(),
				'country' => $this->organization->getCountry(),
				'city' => $this->organization->getCity(),
				'state' => $this->organization->getState(),
				'phone' => $this->organization->getPhone(),
				'fax' => $this->organization->getFax(),
				'url' => $this->organization->getUrl(),
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'organizationId', 'street_address', 'country', 'city', 'state', 'fax', 'url', 'phone'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$interest = $this->getData('interest');
		$organizationId = $this->getData('organizationId');

		$bfrOrgId = DAORegistry::getDAO('BookForReviewOrganizationDAO');

		if ($organizationId > 0) { // Updating existing organization.
			$organization = $bfrOrgId->getOrganization($organizationId);
		} else {
			$organization = $bfrOrgId->newDataObject();
		}

		$organization->setJournalId($journalId);
		$organization->setName($this->getData('name'));
		$organization->setStreetAddress($this->getData('street_address'));
		$organization->setCountry($this->getData('country'));
		$organization->setCity($this->getData('city'));
		$organization->setState($this->getData('state'));
		$organization->setPhone($this->getData('phone'));
		$organization->setFax($this->getData('fax'));
		$organization->setUrl($this->getData('url'));
		$organization->setSequence(REALLY_BIG_NUMBER);

		if ($organizationId > 0) {
			$bfrOrgId->updateOrganization($organization);
		} else {
			$bfrOrgId->insertOrganization($organization);
		}
	}
}

?>
