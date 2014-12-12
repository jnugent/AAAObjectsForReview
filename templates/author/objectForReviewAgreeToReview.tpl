{**
 * @file plugins/generic/objectsForReview/templates/author/objectForReviewAgreeToReview.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display page for author review confirmation and terms.
 *
 *}
{assign var="pageTitle" value="$pageTitle"}
{include file="common/header.tpl"}

<div id="authorObjectForReviewAgree">
	<p>
		{translate key="plugins.generic.objectsForReview.author.agreeToReviewTerms"}
	</p>
	<form method="POST" action="{url page="author" op="agreeToReviewObject" path=$objectForReviewAssignment->getObjectId()}">
		<input type="checkbox" name="agree" {if $readOnly}disabled="disabled" checked="checked"{/if}/>
		{if !$readOnly}
			<input type="submit" class="button" value="{translate key="plugins.generic.objectsForReview.author.agree"}" />
			<input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" />
		{/if}
	</form>
</div>

{include file="common/footer.tpl"}
