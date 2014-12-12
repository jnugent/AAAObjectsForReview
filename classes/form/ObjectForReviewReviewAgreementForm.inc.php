<?php

/**
 * @file plugins/generic/objectsForReview/classes/form/ObjectForReviewReviewAgreementForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectForReviewReviewAgreementForm
 * @ingroup plugins_generic_objectsForReview
 * @see ObjectForReviewAssignment
 *
 * @brief Object for review agree to review form.
 *
 */

import('lib.pkp.classes.form.Form');

class ObjectForReviewReviewAgreementForm extends Form {
	/** @var string Name of parent plugin */
	var $parentPluginName;

	/** @var int ID of the object for review assignment */
	var $assignmentId;

	/** @var int ID of the object for review assignment */
	var $objectId;

	/**
	 * Constructor
	 * @param $parentPluginName sting
	 * @param $assignmentId int
	 * @param $objectId int
	 */
	function ObjectForReviewReviewAgreementForm($parentPluginName, $assignmentId, $objectId) {
		$this->parentPluginName = $parentPluginName;
		$this->assignmentId = (int) $assignmentId;
		$this->objectId = (int) $objectId;

		$ofrPlugin =& PluginRegistry::getPlugin('generic', $parentPluginName);
		parent::Form($ofrPlugin->getTemplatePath() . 'author/objectForReviewAgreeToReview.tpl');

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'agree', 'required', 'plugins.generic.objectsForReview.author.form.agreeRequired'));
	}

	/**
	 * @see Form::display()
	 */
	function display($request) {
		// get the assignment
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignmentDao->getById($this->assignmentId, $this->objectId);
		// get the object for review
		$objectForReview =& $ofrAssignment->getObjectForReview();
		// get the reviewer
		$reviewer =& $ofrAssignment->getUser();

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('objectForReviewAssignment', $ofrAssignment);
		$templateMgr->assign('readOnly', $ofrAssignment->getAgreedToTerms());
		$templateMgr->assign('objectForReview', $objectForReview);
		parent::display($request);
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'agree',
			)
		);
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		$ofrPlugin->import('classes.ObjectForReviewAssignment');

		$journal =& Request::getJournal();
		$journalId = $journal->getId();

		$ofrAssignemntDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignment =& $ofrAssignemntDao->getById($this->assignmentId, $this->objectId);
		if (isset($ofrAssignment)) {
			$ofrAssignment->setAgreedToTerms(1);
			$ofrAssignemntDao->updateObject($ofrAssignment);
		}
	}

}

?>
