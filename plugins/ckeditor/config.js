/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/


CKEDITOR.editorConfig = function(config)
{
    config.toolbar = 'TadToolbar';
    config.toolbar_TadToolbar =
    [
        ['Source','-','Templates','-','Cut','Copy','Paste','PasteText','PasteFromWord'],
        ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
        ['Link','Unlink','Anchor'],
        ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
        '/',
        ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Format','FontSize','-','TextColor','BGColor']
    ];

    config.skin = 'kama'; //office2003 , kama
    config.language = 'zh';
    config.uiColor = '#AADC6E';
    config.contentsCss = 'contents.css';
    config.width = 900;
    config.height = 500;

    config.filebrowserBrowseUrl = '../ckeditor/ckfinder/ckfinder.html';
    config.filebrowserImageBrowseUrl = '../ckeditor/ckfinder/ckfinder.html?Type=Images';
    config.filebrowserFlashBrowseUrl = '../ckeditor/ckfinder/ckfinder.html?Type=Flash';
    config.filebrowserUploadUrl = '../ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
    config.filebrowserImageUploadUrl = '../ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
    config.filebrowserFlashUploadUrl = '../ckeditor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
};
