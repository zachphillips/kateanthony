/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

Array.max=function(a){return Math.max.apply(Math,a)};Array.min=function(a){return Math.min.apply(Math,a)};
(function(a){function i(b,c){isNaN(c)&&(c=0);var d=j=="list"?s:t,g=c*d,f=j=="list"&&e[k].content_type!="image"?!1:!0;typeof h[b]=="undefined"&&(h[b]={});typeof h[b][c]=="undefined"?a.ee_filebrowser.endpoint_request("directory_contents",{directory_id:b,limit:d,offset:g},function(m){var m=m.files,n=0,e=[];a.each(h[b],function(a){e[n]=a;n+=1});n>3&&(c<Array.min(e)?delete h[b][Array.max(e)]:c>Array.max(e)&&delete h[b][Array.min(e)]);typeof h[b][c]=="undefined"&&(h[b][c]=m);o(h[b][c],b,g,d,f)}):o(h[b][c],
b,g,d,f)}function o(b,c,d,g,e){var f=a("#tableView").detach(),h=a("#viewSelectors").detach();f.find("tbody").empty();a("#file_chooser_body").empty().append(f);a("#file_chooser_footer").empty().append(h);j!="list"?(a("#tableView").hide(),a.tmpl("thumb",b).appendTo("#file_chooser_body"),a("a.file_chooser_thumbnail:nth-child(9n+2)").addClass("first"),a("a.file_chooser_thumbnail:nth-child(9n+1)").addClass("last"),a("a.file_chooser_thumbnail:gt(26)").addClass("last_row")):(a("#tableView").show(),a.tmpl("fileRow",
b).appendTo("#tableView tbody"));a.ee_filebrowser.directory_info(c,!1,function(){u(c,d,g,e)})}function u(b,c,d,g){typeof g=="undefined"&&(g=!1);for(var g=l[b].file_count,b=Math.ceil(g/d),f=a("<select />",{id:"current_page",name:"current_page"}),e=0;e<b;e++)f.append(a("<option />",{value:e,text:"Page "+(e+1)}));c={pages_total:g,pages_from:c+1,pages_to:c+d>g?g:c+d,pages_current:Math.floor(c/d)+1,pagination_needed:b>1?!0:!1,dropdown:f.wrap("<div />").parent().html(),previous:EE.filebrowser.previous,
next:EE.filebrowser.next};a("#paginationLinks, #pagination_meta").remove();a.tmpl("pagination",c).appendTo("#file_chooser_footer").find("#view_type").val(j).change(function(){a("#file_chooser_body").removeClass("list thumb").addClass(this.value);h={};j=this.value;i(a("#dir_choice").val())}).end().find("select[name=current_page]").val(c.pages_current-1).change(function(){i(a("#dir_choice").val(),a(this).val())}).end().find("a.previous").click(function(a){a.preventDefault();p(-1)}).end().find("a.next").click(function(a){a.preventDefault();
p(1)}).end();v(b)}function p(b){typeof b=="undefined"&&(b=0);var c=a("#current_page").val(),b=parseInt(c,10)+b;a("#current_page").val(b);i(a("#dir_choice").val(),b)}function v(b){a("#file_chooser_footer #paginationLinks a").removeClass("visualEscapism");a("#current_page").val()==0?a("#file_chooser_footer #paginationLinks .previous").addClass("visualEscapism"):a("#current_page").val()==b-1&&a("#file_chooser_footer #paginationLinks .next").addClass("visualEscapism")}function w(){f.dialog({width:968,
height:615,resizable:!1,position:["center","center"],modal:!0,draggable:!0,title:EE.filebrowser.window_title,autoOpen:!1,zIndex:99999,open:function(){a("#dir_choice").val()}});a("#dir_choice").change(function(){i(this.value,0)});a.template("fileRow",a("<tbody />").append(a("#rowTmpl").remove().attr("id",!1)));a.template("noFilesRow",a("#noFilesRowTmpl").remove());a.template("pagination",a("#paginationTmpl").remove());a.template("thumb",a("#thumbTmpl").remove());a("#upload_form",f).submit(a.ee_filebrowser.upload_start);
a("#file_chooser_body",f).addClass(j)}var f,k="",j="list",s=15,t=36,q=0,l={},h={},e={},r;a.ee_filebrowser=function(){a.ee_filebrowser.endpoint_request("setup",function(b){dir_files_structure={};dir_paths={};f=a(b.manager).appendTo(document.body);for(var c in b.directories)q||(q=c),dir_files_structure[c]="";w();typeof a.ee_fileuploader!="undefined"&&a.ee_fileuploader({type:"filebrowser",open:function(){a.ee_fileuploader.set_directory_id(a("#dir_choice").val())},close:function(){a("#file_uploader").removeClass("upload_step_2").addClass("upload_step_1");
a("#fileChooser").size()&&a.ee_filebrowser.reload_directory(a("#dir_choice").val())},trigger:"#fileChooser #upload_form input"})})};a.ee_filebrowser.endpoint_request=function(b,c,d){typeof d=="undefined"&&a.isFunction(c)&&(d=c,c={});c=a.extend(c,{action:b});a.ajax({url:EE.BASE+"&"+EE.filebrowser.endpoint_url,type:"GET",dataType:"json",data:c,cache:!1,success:function(a){typeof d=="function"&&d.call(this,a)}})};a.ee_filebrowser.add_trigger=function(b,c,d,g){g?e[c]=d:a.isFunction(c)?(g=c,c="userfile",
e[c]={content_type:"any",directory:"all"}):a.isFunction(d)&&(g=d,e[c]={content_type:"any",directory:"all"});a(b).click(function(){var b=this;k=c;e[k].directory!="all"?(a("#dir_choice",f).val(e[k].directory),a("#dir_choice_form",f).hide()):(a("#dir_choice",f).val(),a("#dir_choice_form",f).show());i(a("#dir_choice").val());f.dialog("open");r=function(a){g.call(b,a,c)};return!1})};a.ee_filebrowser.get_current_settings=function(){return e[k]};a.ee_filebrowser.placeImage=function(b){a.ee_filebrowser.endpoint_request("file_info",
{file_id:b},function(b){a.ee_filebrowser.clean_up(b,"")});return!1};a.ee_filebrowser.clean_up=function(b,c){a("#page_0 .items").html(c);f.dialog("close");r(b);l={};h={}};a.ee_filebrowser.reload_directory=function(b){a.ee_filebrowser.directory_info(b,!0,function(){i(b,0)})};a.ee_filebrowser.directory_info=function(b,c,d){typeof c=="undefined"&&(c=!1);typeof l[b]=="undefined"||c==!0?a.ee_filebrowser.endpoint_request("directory_info",{directory_id:b},function(a){l[b]=a;typeof d=="function"&&d.call(this,
a)}):typeof d=="function"&&d.call(this,l[b])}})(jQuery);