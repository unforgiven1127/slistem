var divSelected = '';
var isHtml = 0;

function switchDesc()
{
   $('.descriptionSection').toggle(0);
   $('#is_html').val($('#sdescription_html:visible').length);

   return false;
 };

var errorList = '';
var noticeList = '';
var actionCallback = '';

$(document).ready(function()
{
    divSelected = $('#doc-folders > div:first-child').attr('folderpk');

    $('input[name=folderfk]').val(divSelected);

    $('.browse-button').click(function(){
        $(this).parent().parent().parent().find('input[name=document]').click();
    });

   $('.fast-upload-form').fileupload({
        /*url: '/empty.php5',*/
        dataType: 'json',
        dropZone: $(this),
        sequentialUploads : true,
        add: function (e, data) {

            var loadingDiv = $(this).parent().children('.loading-files');
            loadingDiv.fadeIn();

            var ul = loadingDiv.children('ul');
            var tpl = $('<li class="working"><input type="text" value="0" data-width="16" data-height="16" data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span></li>');

            tpl.find('p').text(data.files[0].name)
                         .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

            data.context = tpl.appendTo(ul);

            tpl.find('input').knob();
            tpl.find('span').click(function()
            {
              if(tpl.hasClass('working'))
              {
                jqXHR.abort();
              }

              tpl.fadeOut(function()
              {
                tpl.remove();
              });
            });

            var jqXHR = data.submit();
        },

        progress: function(e, data){
            var progress = parseInt(data.loaded / data.total * 100, 10);
            data.context.find('input').val(progress).change();
        },

        progressall: function(e, data)
        {
          var progressall = parseInt(data.loaded / data.total * 100, 10);
          if (progressall==100)
            {
              if (errorList.length!=0)
                goPopup.setErrorMessage(errorList, true);

              if (noticeList.length!=0)
                goPopup.setNotice(noticeList, {delay: 1000}, true, true);

              noticeList = errorList = '';

              if (actionCallback.length!=0)
                eval(actionCallback);

              $(this).parent().children('.loading-files').fadeOut();
            }
        },
        done: function(e, data)
        {
          if(!data.error)
          {
            data.context.removeClass('working');
            data.context.fadeOut();
          }
        },
        success: function(oJsonData)
        {
          if (oJsonData.error)
            errorList += oJsonData.error+'<br />';

          if (oJsonData.notice)
            noticeList += oJsonData.notice+'<br />';

          if (oJsonData.action)
            actionCallback = oJsonData.action;
        }
    });

    // Prevent the default action when a file is dropped on the window
    $(document).on('drop dragover', function (e) {
        e.preventDefault();
    });

    // Helper function that formats the file sizes
    function formatFileSize(bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }

        if (bytes >= 1000000000) {
            return (bytes / 1000000000).toFixed(2) + ' GB';
        }

        if (bytes >= 1000000) {
            return (bytes / 1000000).toFixed(2) + ' MB';
        }

        return (bytes / 1000).toFixed(2) + ' KB';
    }

});



/*
 * jQuery File Upload Plugin JS Example 8.8.2
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, regexp: true */
/*global $, window, blueimp */

$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: 'server/php/'
    });

    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
        'option',
        'redirect',
        window.location.href.replace(
            /\/[^\/]*$/,
            '/cors/result.html?%s'
        )
    );

    if (window.location.hostname === 'blueimp.github.io') {
        // Demo settings:
        $('#fileupload').fileupload('option', {
            url: '//jquery-file-upload.appspot.com/',
            // Enable image resizing, except for Android and Opera,
            // which actually support image resizing, but fail to
            // send Blob objects via XHR requests:
            disableImageResize: /Android(?!.*Chrome)|Opera/
                .test(window.navigator.userAgent),
            maxFileSize: 5000000,
            acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
        });
        // Upload server status check for browsers with CORS support:
        if ($.support.cors) {
            $.ajax({
                url: '//jquery-file-upload.appspot.com/',
                type: 'HEAD'
            }).fail(function () {
                $('<div class="alert alert-danger"/>')
                    .text('Upload server currently unavailable - ' +
                            new Date())
                    .appendTo('#fileupload');
            });
        }
    } else {
        // Load existing files:
        $('#fileupload').addClass('fileupload-processing');
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: $('#fileupload').fileupload('option', 'url'),
            dataType: 'json',
            context: $('#fileupload')[0]
        }).always(function () {
            $(this).removeClass('fileupload-processing');
        }).done(function (result) {
            $(this).fileupload('option', 'done')
                .call(this, null, {result: result});
        });
    }

});
