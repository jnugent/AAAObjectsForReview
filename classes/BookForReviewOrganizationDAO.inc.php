<?php

/**
 * @file plugins/generic/booksForReview/classes/BookForReviewOrganizationDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BookForReviewOrganizationDAO
 * @ingroup plugins_generic_booksForReview
 * @see BookForReviewOrganization
 *
 * @brief Operations for retrieving and modifying BookForReviewOrganization objects.
 */

class BookForReviewOrganizationDAO extends DAO {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	/**
	 * Constructor
	 */
	function BookForReviewOrganizationDAO($parentPluginName){
		$this->parentPluginName = $parentPluginName;
		parent::DAO();
	}

	/**
	 * Retrieve an organization by ID.
	 * @param $organizationId int
	 * @return BookForReviewOrganization
	 */
	function &getOrganization($organizationId) {
		$result =& $this->retrieve(
			'SELECT * FROM books_for_review_organizations WHERE organization_id = ?', $organizationId
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
	 * @return array BookForReviewOrganizations ordered by sequence
	 */
	function &getOrganizations($journalId) {
		$organizations = array();

		$result =& $this->retrieve(
				'SELECT * FROM books_for_review_organizations WHERE journal_id = ? ORDER BY seq',
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
	 * Retrieve all organizations for a book for review.
	 * @param $bookId int
	 * @return array BookForReviewOrganizations ordered by sequence
	 */
	function &getOrganizationsByBookForReview($bookId) {
		$organizations = array();

		$result =& $this->retrieve(
			'SELECT * FROM books_for_review_organizations WHERE book_id = ? ORDER BY seq',
			$bookId
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
	 * Retrieve the IDs of all organizations for a book for review.
	 * @param $bookId int
	 * @return array int ordered by sequence
	 */
	function &getOrganizationIdsByBookForReview($bookId) {
		$organizations = array();

		$result =& $this->retrieve(
			'SELECT organization_id FROM books_for_review_organizations WHERE book_id = ? ORDER BY seq',
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
	 * Internal function to return a BookForReviewOrganization object from a row.
	 * @param $row array
	 * @return BookForReviewOrganization
	 */
	function &_returnOrganizationFromRow(&$row) {
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$bfrPlugin->import('classes.BookForReviewOrganization');

		$organization = new BookForReviewOrganization();
		$organization->setBookId($row['book_id']);
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

		HookRegistry::call('BookForReviewOrganizationDAO::_returnOrganizationFromRow', array(&$organization, &$row));

		return $organization;
	}

	/**
	 * return a new BookForReviewOrganization
	 * @return BookForReviewOrganization
	 */
	function newDataObject() {
		$bfrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$bfrPlugin->import('classes.BookForReviewOrganization');
		$organization = new BookForReviewOrganization();
		return $organization;
	}

	/**
	 * Insert a new BookForReviewOrganization.
	 * @param $organization BookForReviewOrganization
	 */
	function insertOrganization(&$organization) {
		$this->update(
			'INSERT INTO books_for_review_organizations
				(book_id, publisher_name, journal_id, street_address, state, country, phone, fax, url, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $organization->getBookId(),
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
	 * Update an existing BookForReviewOrganization.
	 * @param $organization BookForReviewOrganization
	 */
	function updateOrganization(&$organization) {
		$returner = $this->update(
			'UPDATE books_for_review_organizations
				SET
					book_id = ?,
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
				$organization->getBookId(),
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
			'DELETE FROM books_for_review_organizations WHERE organization_id = ?',
			$params
		);
	}

	/**
	 * Delete organizations by book for review.
	 * @param $bookId int
	 */
	function deleteOrganizationsByBookForReview($bookId) {
		$organizations =& $this->getOrganizationsByBookForReview($bookId);
		foreach ($organizations as $organization) {
			$this->deleteOrganization($organization);
		}
	}

	/**
	 * Sequentially renumber a book for review's organizations in their sequence order.
	 * @param $bookId int
	 */
	function resequenceOrganizations($bookId) {
		$result =& $this->retrieve(
			'SELECT organization_id FROM books_for_review_organizations WHERE book_id = ? ORDER BY seq', $bookId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($organizationId) = $result->fields;
			$this->update(
				'UPDATE books_for_review_organizations SET seq = ? WHERE organization_id = ?',
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
		return $this->getInsertId('books_for_review_organizations', 'organization_id');
	}
}

?>
