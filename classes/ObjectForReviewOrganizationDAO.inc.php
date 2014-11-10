<?php

/**
 * @file plugins/generic/objectsForReview/classes/ObjectForReviewOrganizationDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewOrganizationDAO
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewOrganization
 *
 * @brief Operations for retrieving and modifying ObjectForReviewOrganization objects.
 */

class ObjectForReviewOrganizationDAO extends DAO {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function ObjectForReviewOrganizationDAO($parentPluginName){
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Retrieve an organization by ID.
	 * @param $organizationId int
	 * @return ObjectForReviewOrganization
	 */
	function &getOrganization($organizationId) {
		$result =& $this->retrieve(
			'SELECT * FROM objects_for_review_organizations WHERE organization_id = ?', $organizationId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnOrganizationFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}


	/**
	 * Retrieve all organizations for a journal.
	 * @param $journalId int
	 * @return array ObjectForReviewOrganizations ordered by sequence
	 */
	function &getOrganizations($journalId) {
		$organizations = array();

		$result =& $this->retrieve(
				'SELECT * FROM objects_for_review_organizations WHERE journal_id = ? ORDER BY seq',
				$journalId
		);

		while (!$result->EOF) {
			$organizations[] =& $this->_returnOrganizationFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $organizations;
	}

	/**
	 * Retrieve all organizations for a object for review.
	 * @param $objectId int
	 * @return array ObjectForReviewOrganizations ordered by sequence
	 */
	function &getOrganizationsByObjectForReview($objectId) {
		$organizations = array();

		$result =& $this->retrieve(
			'SELECT * FROM objects_for_review_organizations WHERE object_id = ? ORDER BY seq',
			$objectId
		);

		while (!$result->EOF) {
			$organizations[] =& $this->_returnOrganizationFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $organizations;
	}

	/**
	 * Retrieve the IDs of all organizations for a object for review.
	 * @param $objectId int
	 * @return array int ordered by sequence
	 */
	function &getOrganizationIdsByObjectForReview($objectId) {
		$organizations = array();

		$result =& $this->retrieve(
			'SELECT organization_id FROM objects_for_review_organizations WHERE object_id = ? ORDER BY seq',
			$articleId
		);

		while (!$result->EOF) {
			$organizations[] = $result->fields[0];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $organizations;
	}

	/**
	 * Internal function to return a ObjectForReviewOrganization object from a row.
	 * @param $row array
	 * @return ObjectForReviewOrganization
	 */
	function &_returnOrganizationFromRow(&$row) {
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$bfrPlugin->import('classes.ObjectForReviewOrganization');

		$organization = new ObjectForReviewOrganization();
		$organization->setObjectId($row['object_id']);
		$organization->setJournalId($row['journal_id']);
		$organization->setId($row['organization_id']);
		$organization->setName($row['publisher_name']);
		$organization->setStreetAddress($row['street_address']);
		$organization->setState($row['state']);
		$organization->setCity($row['city']);
		$organization->setCountry($row['country']);
		$organization->setPhone($row['phone']);
		$organization->setFax($row['fax']);
		$organization->setUrl($row['url']);
		$organization->setSequence($row['seq']);

		HookRegistry::call('ObjectForReviewOrganizationDAO::_returnOrganizationFromRow', array(&$organization, &$row));

		return $organization;
	}

	/**
	 * return a new ObjectForReviewOrganization
	 * @return ObjectForReviewOrganization
	 */
	function newDataObject() {
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$bfrPlugin->import('classes.ObjectForReviewOrganization');
		$organization = new ObjectForReviewOrganization();
		return $organization;
	}

	/**
	 * Insert a new ObjectForReviewOrganization.
	 * @param $organization ObjectForReviewOrganization
	 */
	function insertOrganization(&$organization) {
		$this->update(
			'INSERT INTO objects_for_review_organizations
				(object_id, publisher_name, journal_id, street_address, state, country, phone, fax, url, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $organization->getObjectId(),
				$organization->getName(),
				(int) $organization->getJournalId(),
				$organization->getStreetAddress(),
				$organization->getState(),
				$organization->getCountry(),
				$organization->getPhone(),
				$organization->getFax(),
				$organization->getUrl(),
				$organization->getSequence()
			)
		);

		$organization->setId($this->getInsertOrganizationId());
		return $organization->getId();
	}

	/**
	 * Update an existing ObjectForReviewOrganization.
	 * @param $organization ObjectForReviewOrganization
	 */
	function updateOrganization(&$organization) {
		$returner = $this->update(
			'UPDATE objects_for_review_organizations
				SET
					object_id = ?,
					publisher_name = ?,
					journal_id = ?,
					street_address = ?,
					state = ?,
					city = ?,
					country = ?,
					phone = ?,
					fax = ?,
					url = ?,
					seq = ?
				WHERE organization_id = ?',
			array(
				$organization->getObjectId(),
				$organization->getName(),
				$organization->getJournalId(),
				$organization->getStreetAddress(),
				$organization->getState(),
				$organization->getCity(),
				$organization->getCountry(),
				$organization->getPhone(),
				$organization->getFax(),
				$organization->getUrl(),
				$organization->getSequence(),
				$organization->getId()
			)
		);
		return $returner;
	}

	/**
	 * Delete an Organization.
	 * @param $organization Organization
	 */
	function deleteOrganization(&$organization) {
		return $this->deleteOrganizationById($organization->getId());
	}

	/**
	 * Delete an organization by ID.
	 * @param $organizationId int
	 */
	function deleteOrganizationById($organizationId) {
		$params = array($organizationId);
		$returner = $this->update(
			'DELETE FROM objects_for_review_organizations WHERE organization_id = ?',
			$params
		);
	}

	/**
	 * Delete organizations by object for review.
	 * @param $objectId int
	 */
	function deleteOrganizationsByObjectForReview($objectId) {
		$organizations =& $this->getOrganizationsByObjectForReview($objectId);
		foreach ($organizations as $organization) {
			$this->deleteOrganization($organization);
		}
	}

	/**
	 * Sequentially renumber a object for review's organizations in their sequence order.
	 * @param $objectId int
	 */
	function resequenceOrganizations($objectId) {
		$result =& $this->retrieve(
			'SELECT organization_id FROM objects_for_review_organizations WHERE object_id = ? ORDER BY seq', $objectId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($organizationId) = $result->fields;
			$this->update(
				'UPDATE objects_for_review_organizations SET seq = ? WHERE organization_id = ?',
				array(
					$i,
					$organizationId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted organization.
	 * @return int
	 */
	function getInsertOrganizationId() {
		return $this->getInsertId('objects_for_review_organizations', 'organization_id');
	}
}

?>
