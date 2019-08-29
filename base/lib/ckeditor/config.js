/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
  config.extraPlugins = 'embed,embedbase,widget,lineutils,widgetselection,notificationaggregator,notification,toolbar,button,btbutton,glyphicons,colordialog,fontawesome,btgrid';

  //for file browser
  config.filebrowserBrowseUrl = '/base/lib/fileman/index.html?integration=ckeditor';

  //for bootstrap buttons and glyphicons
  config.contentsCss = [
        'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css',
        'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css'
  ];

  //for glyphicons
  config.allowedContent = true;
  config.extraAllowedContent = true;
  config.enterMode = CKEDITOR.ENTER_BR;

  /*config.allowedContent = true;
   config.extraAllowedContent = 'p(*)[*]{*};div(*)[*]{*};li(*)[*]{*};ul(*)[*]{*}';
   CKEDITOR.dtd.$removeEmpty.i = 0;*/
};

CKEDITOR.dtd.$removeEmpty.i = 0;
CKEDITOR.dtd.$removeEmpty.span = 0;
