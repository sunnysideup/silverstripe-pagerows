<% if $HasChild %><div class="page-row-outer-space"><% end_if %>
<section
    id="$MoreDetailsRowChildLinkingID"
    class="$HTMLClassNamesAsString row page-row typography $CalculatedBackgroundStyle"
    <% if $MoreDetailsRowParentLinkingID %>data-child-id="$MoreDetailsRowParentLinkingID" <% end_if %>
    <% if $HasBackgroundImage %>style="background-image: url($CalculatedBackgroundImage);" <% end_if %>
>
    <% if $UserCanEditBlock %><div class="edit-page-row-in-cms"><a href="$ContextRelevantCMSEditLink" title="You can edit this $Title.ATT ($singular_name.ATT) in CMS" class="external-link"><span>✎</span></a></div><% end_if %>
