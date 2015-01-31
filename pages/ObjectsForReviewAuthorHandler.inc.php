<?php

/**
 * @file plugins/generic/objectsForReview/pages/ObjectsForReviewAuthorHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewAuthorHandler
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Handle requests for author object for review functions.
 */

import('classes.handler.Handler');

define('OFR_WS_RESPONSE_OK', 200);

class ObjectsForReviewAuthorHandler extends Handler {

	/**
	 * Display objects for review author listing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function objectsForReview($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$user =& $request->getUser();
		$userId = $user->getId();

		// Sort
		$sort = $request->getUserVar('sort');
		$sort = isset($sort) ? $sort : 'title';
		$sortDirection = $request->getUserVar('sortDirection');
		$sortDirection = isset($sortDirection) ? $sortDirection : SORT_DIRECTION_ASC;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$mode = $ofrPlugin->getSetting($journalId, 'mode');

		$ofrPlugin->import('classes.ObjectForReviewAssignment');
		$path = !isset($args) || empty($args) ? null : $args[0];
		switch($path) {
			case 'requested':
				$status = OFR_STATUS_REQUESTED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleRequested';
				break;
			case 'assigned':
				$status = OFR_STATUS_ASSIGNED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleAssigned';
				break;
			case 'mailed':
				$status = OFR_STATUS_MAILED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleMailed';
				break;
			case 'submitted':
				$status = OFR_STATUS_SUBMITTED;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleSubmitted';
				break;
			case 'all':
				default:
				$path = 'all';
				$status = null;
				$pageTitle = 'plugins.generic.objectsForReview.objectForReviewAssignments.pageTitleAll';
		}

		$rangeInfo = Handler::getRangeInfo($request, 'objectForReview');
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$objectForReviewAssignments =& $ofrAssignmentDao->getAllByContextId($journalId, null, null, null, $status, $userId, null, null, $rangeInfo, $sort, $sortDirection);

		$this->setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->assign('mode', $mode);
		$templateMgr->assign('returnPage', $path);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('objectForReviewAssignments', $objectForReviewAssignments);
		$templateMgr->assign('counts', $ofrAssignmentDao->getStatusCounts($journalId, $userId));
		$templateMgr->display($ofrPlugin->getTemplatePath() . 'author' . '/' . 'objectsForReviewAssignments.tpl');
	}

	/**
	 * Author requests an object for review.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function requestObjectForReview($args, &$request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();

		$objectId = !isset($args) || empty($args) ? null : (int) $args[0];
		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'objectsForReview');
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId, $journalId);

		$redirect = true;
		if ($objectForReview->getAvailable()) {
			// Get the requesting user
			$user =& $request->getUser();
			$userId = $user->getId();
			// Ensure there is no assignment for this object and user
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			if ($ofrAssignmentDao->assignmentExists($objectId, $userId)) {
				$request->redirect(null, 'objectsForReview');
			}

			import('classes.mail.MailTemplate');
			$email = new MailTemplate('OFR_OBJECT_REQUESTED');
			$send = $request->getUserVar('send');
			// Author has filled out mail form or decided to skip email
			if ($send && !$email->hasErrors()) {
				// Update object for review as requested
				$ofrAssignment = $ofrAssignmentDao->newDataObject();
				$ofrAssignment->setObjectId($objectId);
				$ofrAssignment->setUserId($userId);
				$ofrAssignment->setStatus(OFR_STATUS_REQUESTED);
				$ofrAssignment->setDateRequested(Core::getCurrentDate());
				$ofrAssignmentDao->insertObject($ofrAssignment);
				$email->send();
				$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_REQUESTED, $request);
			} else {
				$returnUrl = $request->url(null, 'author', 'requestObjectForReview', $objectId);
				$this->_displayEmailForm($email, $objectForReview, $user, $returnUrl, 'OFR_OBJECT_REQUESTED', $request);
				$redirect = false;
			}
		}
		if ($redirect) $request->redirect(null, 'objectsForReview');
	}

	/**
	 * Show form for author to agree to review an object.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function agreeToReviewObject($args, $request) {
		$objectId = (int) $args[0];
		$user = $request->getUser();
		$journal =& $request->getJournal();
		if ($objectId) {
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			$objectForReviewAssignment = $ofrAssignmentDao->getByObjectAndUserId($objectId, $user->getId());
			if ($objectForReviewAssignment) {
				$ofrPlugin =& $this->_getObjectsForReviewPlugin();
				$ofrPlugin->import('classes.form.ObjectForReviewReviewAgreementForm');
				$ofrAgreementForm = new ObjectForReviewReviewAgreementForm($ofrPlugin->getName(), $objectForReviewAssignment->getId(), $objectId);
				$ofrAgreementForm->readInputData();
				if ($ofrAgreementForm->validate()) {
					$ofrAgreementForm->execute();
					$request->redirect(null, 'author', 'objectsForReview');
				} else {
					$this->setupTemplate($request, true);
					$templateMgr =& TemplateManager::getManager($request);
					$templateMgr->assign('pageTitle', 'plugins.generic.objectsForReview.author.agreeToReview');
					$mode = $ofrPlugin->getSetting($journal->getId(), 'mode');
					$templateMgr->assign('mode', $mode);
					$ofrAgreementForm->display($request);
				}
			} else {
				$request->redirect(null, 'objectsForReview');
			}
		}
	}

	/**
	 * Store decline by author to review an object.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function declineToReviewObject($args, $request) {
		$journal =& $request->getJournal();
		$journalId = $journal->getId();
		$user =& $request->getUser();

		$objectId = !isset($args) || empty($args) ? null : (int) $args[0];
		if (!$this->_ensureObjectExists($objectId, $journalId)) {
			$request->redirect(null, 'objectsForReview');
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		$objectForReview =& $ofrDao->getById($objectId);
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$assignment =& $ofrAssignmentDao->getByObjectAndUserId($objectId, $user->getId());

		$redirect = true;
		if ($assignment) {
			import('classes.mail.MailTemplate');
			$email = new MailTemplate('OFR_OBJECT_DECLINED');
			$send = $request->getUserVar('send');
			// Author has filled out mail form or decided to skip email
			if ($send && !$email->hasErrors()) {
				// Update object for review as requested
				$assignment->setStatus(OFR_STATUS_DECLINED);
				$ofrAssignmentDao->updateObject($assignment);
				$email->send();
				$this->_createTrivialNotification(NOTIFICATION_TYPE_OFR_DECLINED, $request);
			} else {
				$returnUrl = $request->url(null, 'author', 'declineToReviewObject', $objectId);
				$this->_displayEmailForm($email, $objectForReview, $user, $returnUrl, 'OFR_OBJECT_DECLINED', $request);
				$redirect = false;
			}
		}
		if ($redirect) $request->redirect(null, 'author', 'objectsForReview');
	}

	/**
	 * Connector from AnthroNet.  Receives a token in the 'token' query string argument
	 * that is used to fetch author information via SOAP, create/or validate the author login,
	 * and redirect to author home page.
	 * @param array $args
	 * @param PKPRequest $request
	 */
	function objectsForReviewLogin($args, $request) {

		$token = $request->getUserVar('token');
		if ($token) {
			$authToken = $this->_doAuthenticate();
			if ($authToken) {
				$user = $this->_doUserRequest($token, $authToken);
				if ($user) {
					$sessionManager =& SessionManager::getManager();
					$sessionManager->regenerateSessionId();
					$session =& $sessionManager->getUserSession();
					$session->setSessionVar('userId', $user->getId());
					$session->setUserId($user->getId());
					$session->setSessionVar('username', $user->getUsername());
					$request->redirect(null, 'objectsForReview'); // place them on the landing page for available objects.
				}
			}
		}
	}

	/**
	 * Ensure that we have a journal, plugin is enabled, and user is author.
	 * @see PKPHandler::authorize()
	 */
	function authorize(&$request, &$args, $roleAssignments) {
		$journal =& $request->getJournal();
		if (!isset($journal)) return false;

		$ofrPlugin =& $this->_getObjectsForReviewPlugin();

		if (!isset($ofrPlugin)) return false;

		if (!$ofrPlugin->getEnabled()) return false;

		if ($request->getRequestedOp() != 'objectsForReviewLogin' && !Validation::isAuthor($journal->getId())) Validation::redirectLogin();

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Setup common template variables.
	 * @param $request PKPRequest
	 * @param $subclass boolean (optional) set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = false) {
		$templateMgr =& TemplateManager::getManager($request);
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url(null, 'author'),
				'user.role.author'
			)
		);
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
		$ofrPlugin =& $this->_getObjectsForReviewPlugin();
		$templateMgr->addStyleSheet($request->getBaseUrl() . '/' . $ofrPlugin->getStyleSheet());
	}

	//
	// Private helper methods
	//
	/**
	 * Get the objectForReview plugin object
	 * @return ObjectsForReviewPlugin
	 */
	function &_getObjectsForReviewPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', OBJECTS_FOR_REVIEW_PLUGIN_NAME);
		return $plugin;
	}

	/** Ensure object for review exists
	 * @param $objectId int
	 * @param $journalId int
	 * @return boolean
	 */
	function _ensureObjectExists($objectId, $journalId) {
		if ($objectId == null) {
			return false;
		}
		$ofrDao =& DAORegistry::getDAO('ObjectForReviewDAO');
		if (!$ofrDao->objectForReviewExists($objectId, $journalId)) {
			return false;
		}
		return true;
	}

	/**
	 * Display email form for the author
	 * @param $email MailTemplate
	 * @param $objectForReview ObjectForReview
	 * @param $user User
	 * @param $returnUrl string
	 * @param $action string
	 * @param $request PKPRequest
	 */
	function _displayEmailForm($email, $objectForReview, $user, $returnUrl, $action, $request) {
		if (!$request->getUserVar('continued')) {
			$editor =& $objectForReview->getEditor();
			$editorFullName = $editor->getFullName();
			$editorEmail = $editor->getEmail();

			if ($action == 'OFR_OBJECT_REQUESTED') {
				$paramArray = array(
					'editorName' => strip_tags($editorFullName),
					'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
					'authorContactSignature' => String::html2text($user->getContactSignature())
				);
			}
			$email->addRecipient($editorEmail, $editorFullName);
			$email->assignParams($paramArray);
		}
		$email->displayEditForm($returnUrl);
	}

	/**
	 * Create trivial notification
	 * @param $notificationType int
	 * @param $request PKPRequest
	 */
	function _createTrivialNotification($notificationType, &$request) {
		$user =& $request->getUser();
		import('classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();
		$notificationManager->createTrivialNotification($user->getId(), $notificationType);
	}

	/**
	 * Do the actual web SOAP service request.
	 * @param $token string
	 * @param $authToken string The token returned from _doAuthenticate
	 * @return boolean|string True for success, an error message otherwise.
	 */
	function _doUserRequest($token, $authToken) {
		// Build the multipart SOAP message from scratch.
		$soapMessage =
		'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.avectra.com/2005/">
			<soapenv:Header>
				<ns:AuthorizationToken>
					<ns:Token>' . $authToken . '</ns:Token>
				</ns:AuthorizationToken>
		</soapenv:Header>
		<soapenv:Body>
			<ns:BNEGetIndividualInformation>
				<ns:SSOToken>' . $token . '</ns:SSOToken>
			</ns:BNEGetIndividualInformation>
		</soapenv:Body>
	</soapenv:Envelope>';

		// Prepare HTTP session.
		$curlCh = curl_init();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_VERBOSE, true);

		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlCh, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		// Make SOAP request.
		curl_setopt($curlCh, CURLOPT_URL, 'https://avectra.aaanet.org/netforumanthrotest/xweb/secure/BNEANTHROWS.asmx');
		$extraHeaders = array(
				'Host: avectra.aaanet.org',
				'SOAPAction: "http://www.avectra.com/2005/BNEGetIndividualInformation"',
				'Content-Type: text/xml;charset=UTF-8',
		);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $extraHeaders);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $soapMessage);

		$result = true;
		$response = curl_exec($curlCh);

		// We do not localize our error messages as they are all
		// fatal errors anyway and must be analyzed by technical staff.
		if ($response === false) {
			$result = 'OJS-OFR: Expected string response.';
		}

		if ($result === true && ($status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE)) != OFR_WS_RESPONSE_OK) {
			$result = 'OJS-OFR: Expected ' . OFR_WS_RESPONSE_OK . ' response code, got ' . $status . ' instead.';
		}

		curl_close($curlCh);

		// Check SOAP response by simple string manipulation rather
		// than instantiating a DOM.
		if (is_string($response)) {

			$request = Application::getRequest();

			/**
			 * The XML returned looks something like this:
			 *
			 * <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
			 * 		<soap:Header><AuthorizationToken xmlns="http://www.avectra.com/2005/"><Token>2a51ca85-d490-4444-802c-d247259d674a</Token></AuthorizationToken></soap:Header>
			 * 		<soap:Body>
			 * 			<BNEGetIndividualInformationResponse xmlns="http://www.avectra.com/2005/">
			 * 				<BNEGetIndividualInformationResult>
			 * 					<Individual xmlns="">
			 * 						<ind_cst_key>2a51ca85-d490-9999-802c-d24XX59d674a</ind_cst_key>
			 * 						<cst_recno>000001</cst_recno>
			 * 						<ind_first_name>John</ind_first_name>
			 * 						<ind_last_name>Public</ind_last_name>
			 * 						<cst_eml_address_dn>user@email.com</cst_eml_address_dn>
			 * 						<InterestCodes>&lt;InterestCode&gt;Art and Material Culture&lt;/InterestCode&gt;</InterestCodes>
			 * 					</Individual>
			 * 				</BNEGetIndividualInformationResult>
			 * 			</BNEGetIndividualInformationResponse>
			 * 		</soap:Body>
			 * </soap:Envelope>
			 */
			$matches = array();
			if (!preg_match('#<faultstring>([^<]*)</faultstring>#', $response)) {

				// Ensure that the user is logged into the AnthroNet portal.
				if (preg_match('#<ind_cst_key>00000000\-0000\-0000\-0000\-000000000000</ind_cst_key>#', $response)) {
					$request->redirect(null, 'user');
				} else {
					$email = $firstName = $lastName = $interestCodes = null;
					$interestCodesArray = array();

					if (preg_match('#<cst_eml_address_dn>(.*?)</cst_eml_address_dn>#', $response, $matches)) {
						$email = $matches[1];
					}
					if (preg_match('#<ind_first_name>(.*?)</ind_first_name>#', $response, $matches)) {
						$firstName = $matches[1];
					}
					if (preg_match('#<ind_last_name>(.*?)</ind_last_name>#', $response, $matches)) {
						$lastName = $matches[1];
					}
					if (preg_match('#<InterestCodes>(.*?)</InterestCodes>#', $response, $matches)) {
						$interestCodes = $matches[1];
						preg_match_all('#&lt;(.*?)&gt;#', $interestCodes, $matches, PREG_PATTERN_ORDER);
						if (is_array($matches[1])) {
							$interestCodesArray = $matches[1];
						}
					}

					$userDao =& DAORegistry::getDAO('UserDAO');
					// see if this user exists already.
					$user = $userDao->getUserByEmail($email);

					if (!$user) {
						$user = new User();

						$userName = Validation::suggestUsername($firstName, $lastName);
						$user->setUsername($userName);
						$user->setFirstName($firstName);
						$user->setLastName($lastName);
						$user->setEmail($email);
						$user->setDateRegistered(Core::getCurrentDate());

						$site =& Request::getSite();
						$availableLocales = $site->getSupportedLocales();

						$locales = array();
						foreach ($this->getData('userLocales') as $locale) {
							if (AppLocale::isLocaleValid($locale) && in_array($locale, $availableLocales)) {
								array_push($locales, $locale);
							}
						}
						$user->setLocales($locales);
						$user->setPassword(Validation::encryptCredentials($userName, Validation::generatePassword()));

						$userDao->insertUser($user);
					}

					$interestManager  = new InterestManager();
					$interestManager->setInterestsForUser($user, $interestCodesArray);

					// enroll as Author, if not already.
					$roleDao =& DAORegistry::getDAO('RoleDAO');
					$journal =& Request::getJournal();
					if (!$roleDao->userHasRole($journal->getId(), $user->getId(), ROLE_ID_AUTHOR)) {
						$role = new Role();
						$role->setJournalId($journal->getId());
						$role->setUserId($user->getId());
						$role->setRoleId(ROLE_ID_AUTHOR);
						$roleDao->insertRole($role);
					}

					return $user;
				}
			} else {
				$result = 'OFR: ' . $status . ' - ' . $matches[1];
			}
		} else {
			$result = 'OJS-OFR: Expected string response.';
		}

		return $result;
	}

	/**
	 * Authenticate against the AnthroNET portal.
	 * @return String the auth token if successful
	 */
	function _doAuthenticate() {
		// Build the multipart SOAP message from scratch.
		$soapMessage =
		'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://www.avectra.com/2005/">
			<soapenv:Header />
			<soapenv:Body>
				<ns:Authenticate>
					<ns:userName>confexxwebuser</ns:userName>
					<ns:password>123</ns:password>
				</ns:Authenticate>
			</soapenv:Body>
		</soapenv:Envelope>';

		// Prepare HTTP session.
		$curlCh = curl_init();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_VERBOSE, true);

		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlCh, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		// Make SOAP request.
		curl_setopt($curlCh, CURLOPT_URL, 'https://avectra.aaanet.org/netforumanthrotest/xweb/secure/BNEANTHROWS.asmx');
		$extraHeaders = array(
				'Host: avectra.aaanet.org',
				'SOAPAction: "http://www.avectra.com/2005/Authenticate"',
				'Content-Type: text/xml;charset=UTF-8',
		);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $extraHeaders);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $soapMessage);

		$result = true;
		$response = curl_exec($curlCh);

		// We do not localize our error messages as they are all
		// fatal errors anyway and must be analyzed by technical staff.
		if ($response === false) {
			$result = 'OJS-OFR: Expected string response.';
		}

		if ($result === true && ($status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE)) != OFR_WS_RESPONSE_OK) {
			$result = 'OJS-OFR: Expected ' . OFR_WS_RESPONSE_OK . ' response code, got ' . $status . ' instead.';
		}

		curl_close($curlCh);

		// Check SOAP response by simple string manipulation rather
		// than instantiating a DOM.
		if (is_string($response)) {
			$matches = array();
			String::regexp_match_get('#<Token>([^<]*)</Token>#', $response, $matches);
			if (!empty($matches)) {
				$result = $matches[1];
			} else {
				$result = 'OFR: ' . $status . ' - ' . $matches[1];
			}
		} else {
			$result = 'OJS-OFR: Expected string response.';
		}

		return $result;
	}
}

?>
