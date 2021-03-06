{**
 * plugins/generic/objectsForReview/templates/editor/listOrganizations.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Books for Review Organization list
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.objectsForReview.editor.listOrganizations"}
{include file="common/header.tpl"}
{/strip}
<div id="objectsForReviewOrganizations">
<div id="description">{translate key="plugins.generic.objectsForReview.editor.listOrganizations.description"}</div>

<div class="separator"></div>

<br />

{include file="common/formErrors.tpl"}

<p><a href="{url op="objectsForReviewManagePublisher"}">{translate key="plugins.generic.objectsForReview.editor.listOrganizations.createNewOrganization"}</a></p>
<table width="100%" class="data">
	{foreach from=$organizations item=organization}
		<tr>
		<td>
			{$organization->getName()|escape}
		</td>
		<td>
		<td><a href="{url op="objectsForReviewManagePublisher" organizationId=$organization->getId()}">{translate key="common.edit"}</a> | <a onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.listOrganizations.confirmDelete"}')" href="{url op="objectsForReviewDeletePublisher" organizationId=$organization->getId()}">{translate key="common.delete"}</a>
		</td>
		</tr>
	{/foreach}
</table>

<br/>
</div>
{include file="common/footer.tpl"}
