{**
 * @file plugins/generic/objectsForReview/templates/editor/objectsForReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display page for all objects for review for editor management.
 *
 *}
 {if count($assignments) > 0}
	{assign var="pageTitle" value="plugins.generic.objectsForReview.author.myObjectsForReview"}
{else}
	{assign var="pageTitle" value="plugins.generic.objectsForReview.objectsForReview.editor.manageTitle"}
{/if}
{include file="common/header.tpl"}

<div id="objectsForReview">
<ul class="menu">
		<li><a href="{url op="objectsForReview" path="all"}">{translate key="plugins.generic.objectsForReview.editor.assignments"}</a></li>
	{if count($assignments) == 0}
		<li class="current"><a href="{url op="objectsForReview"}">{translate key="plugins.generic.objectsForReview.editor.objectsForReview"}</a></li>
		<li><a href="{url op="objectsForReviewSettings"}">{translate key="plugins.generic.objectsForReview.settings"}</a></li>
	{/if}
</ul>
<br />

<h2>{translate key="plugins.generic.objectsForReview.editor.options"}</h2>

<ul style="">
	<li><a href="#manual">{translate key="plugins.generic.objectsForReview.editor.createManual"}</a></li>
	<li><a href="#upload">{translate key="plugins.generic.objectsForReview.editor.addUpload"}</a></li>
	<li><a href="#edit">{translate key="plugins.generic.objectsForReview.editor.editExisting"}</a></li>
</ul>

<a name="manual"></a>
<h3>{translate key="plugins.generic.objectsForReview.editor.createManual"}</h3>
<p>{translate key="plugins.generic.objectsForReview.author.createMasthead"}</p>
<form id="createObjectForReview" action="{url op="createObjectForReview"}" method="post"><select name="reviewObjectTypeId" class="selectMenu" size="1">{html_options options=$createTypeOptions}</select>&nbsp;<input type="submit" value="{translate key="common.create"}" class="button defaultButton"/></form>

<div class="separator"></div>

<a name="upload"></a>
<h3>{translate key="plugins.generic.objectsForReview.editor.addUpload"}</h3>
<p>{translate key="plugins.generic.objectsForReview.editor.objectForReview.onixFileInstructions"}</p>
<form id="objectForReviewONIX" action="{url op="uploadONIXObjectForReview"}" method="post" enctype="multipart/form-data">
<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.editor.objectType"}</td>
		<td width="80%" class="value"><select name="reviewObjectTypeId" class="selectMenu" size="1">{html_options options=$createTypeOptions}</select></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="onixFile" key="plugins.generic.objectsForReview.editor.objectForReview.onixFile"}</td>
		<td width="80%" class="value"><input type="file" name="onixFile" id="onixFile" class="uploadField" />&nbsp;&nbsp;</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.editor.multipleObjectsInFile"}</td>
		<td width="80%" class="value"><input type="checkbox" name="multiple" id="multiple" value="1" />
		</td>
	</tr>
	<tr>
		<td colspan="2"><input type="submit" value="{translate key="common.upload"}" class="button defaultButton"/></td>
	</tr>
</table>
</form>
</div>

<div class="separator"></div>

<a name="edit"></a>
<h3>{translate key="plugins.generic.objectsForReview.editor.editExisting"}</h3>
{include file="../plugins/generic/objectsForReview/templates/editor/objectsForReviewList.tpl"}

{include file="common/footer.tpl"}
